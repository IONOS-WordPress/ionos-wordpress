#!/usr/bin/env bash

#
# script is not intended to be executed directly. use `pnpm exec ...` instead or call it as package script.
#
# this script is used to start the persistent wordpress-alpine development container
#

# bootstrap the environment
source "$(realpath $0 | xargs dirname)/includes/_bootstrap.sh"
source "$(realpath $0 | xargs dirname)/includes/_docker-mounts.sh"

# (re)build the project (this also (re)builds the wordpress-alpine image locally whenever its
# Dockerfile/entrypoint changed, via scripts/build.sh's docker-package build dispatch)
if [[ "${BUILD_UP_TO_DATE:-}" == '1' ]]; then
  # skip building if BUILD_UP_TO_DATE is set to 1
  ionos.wordpress.log_warn "skip (re)building : BUILD_UP_TO_DATE=1 detected"
else
  pnpm build
fi

readonly CORE_DIR="$(ionos.wordpress.core_dir "$WORDPRESS_VERSION")"
readonly STACK_DIR="${MNT_HOME}/dev"

# derived rather than hardcoded: DOCKER_USERNAME/DOCKER_REPOSITORY (see
# ionos.wordpress.docker_image_name_for_package) let a developer retag the image
# scripts/build.sh produces - a hardcoded name here would silently fall out of sync
# and `docker run` would fail with a confusing Docker Hub "pull access denied"
readonly WORDPRESS_ALPINE_IMAGE="$(ionos.wordpress.docker_image_name_for_package '@ionos-wordpress/wordpress-alpine'):latest"

# MariaDB's datadir. Kept in a named docker volume rather than the container's writable
# layer, so recreating the container (the only way to pick up a rebuilt wordpress-alpine
# image, since env vars and bind mounts are baked in at `docker run` time) no longer
# throws away the local database along with it. destroy.sh removes this volume, so
# `pnpm destroy` keeps its current meaning of a full reset.
#
# A named volume rather than a bind mount under ${STACK_DIR}: docker seeds a fresh named
# volume from the image's own /data, ownership included, so mariadbd keeps writing as the
# `mysql` uid. A host bind mount would start out owned by the host user, need a chown to
# `mysql` inside the container, and then be awkward for the host user to clean up again.
#
# Derived from CONTAINER_NAME so a second stack (different CONTAINER_NAME) gets its own
# database instead of silently sharing this one.
readonly DB_VOLUME_NAME="${CONTAINER_NAME}-data"

# env vars and bind mounts are baked into a container at `docker run` time and this
# script otherwise just `docker start`s an already existing container - so a changed
# WORDPRESS_VERSION would be silently ignored, leaving the container serving the core
# dir it was originally created for. recreate it instead (must happen before the
# overlay dirs below are (re)created, since destroy.sh removes them).
CONTAINER_WORDPRESS_VERSION="$(docker inspect "$CONTAINER_NAME" --format '{{range .Config.Env}}{{println .}}{{end}}' 2>/dev/null | sed -n 's/^WORDPRESS_VERSION=//p' || true)"
if [[ -n "$CONTAINER_WORDPRESS_VERSION" ]] && [[ "$CONTAINER_WORDPRESS_VERSION" != "$WORDPRESS_VERSION" ]]; then
  ionos.wordpress.log_warn "container '${CONTAINER_NAME}' was created for WORDPRESS_VERSION='${CONTAINER_WORDPRESS_VERSION}' but is now configured as '${WORDPRESS_VERSION}' - recreating it (this wipes the database and ${MNT_HOME}/dev : uploads and wp-config.php)"
  "$(realpath $0 | xargs dirname)/destroy.sh"
fi

# build the --volume argument list for `docker run`, dynamically discovering the
# monorepo's wp-plugin/wp-theme/wp-mu-plugin packages (shared with scripts/test.sh).
VOLUME_ARGS=()
ionos.wordpress.build_wp_volume_args "$STACK_DIR" "$CORE_DIR"

if [[ -n "${AFTER_START:-}" ]]; then
  VOLUME_ARGS+=(--volume "$(realpath "$AFTER_START"):/after-start.sh")
fi

if ionos.wordpress.container_exists "$CONTAINER_NAME"; then
  # container already exists (running or stopped) - (re)start it as-is, matching
  # today's idempotent `pnpm start` behavior. Volume/env changes only take effect
  # after `pnpm destroy` recreates the container.
  docker start "$CONTAINER_NAME" >/dev/null
else
  # --add-host host.docker.internal:host-gateway resolves the image's
  # xdebug.client_host to the docker host, so xdebug can reach the IDE's
  # listener on port 9003 (see packages/docker/wordpress-alpine/Dockerfile).
  docker run \
    --detach \
    --tty \
    --interactive \
    --name "$CONTAINER_NAME" \
    --hostname "$CONTAINER_NAME" \
    --publish "${HTTP_PORT}:80" \
    --publish "${SSH_PORT}:22" \
    --add-host "host.docker.internal:host-gateway" \
    --env WORDPRESS_VERSION="$WORDPRESS_VERSION" \
    --env WP_PASSWORD="$WP_PASSWORD" \
    --env HTTP_PORT="$HTTP_PORT" \
    --env AFTER_START="${AFTER_START:-}" \
    --env HOST_UID="$(id -u)" \
    --env HOST_GID="$(id -g)" \
    --volume "${DB_VOLUME_NAME}:/data" \
    "${VOLUME_ARGS[@]}" \
    "$WORDPRESS_ALPINE_IMAGE" >/dev/null
fi

# (re)generate .vscode/launch.json so the xdebug pathMappings match the packages
# currently bind-mounted into the container
./packages/docker/wordpress-alpine/scripts/_generate-vscode-launch.sh

# An HTTP 200 alone does not mean the container is done with itself: docker-entrypoint.sh
# starts httpd well before it runs the AFTER_START script, so without this wait `pnpm start`
# could hand back a site whose brand options, plugin activations, front page and admin
# password were still being written underneath it. The entrypoint touches this marker as its
# very last statement (and clears it on boot, so a `docker start` of an existing container
# cannot serve the previous run's marker).
#
# Generous budget: on a cold start everything before the marker includes downloading or
# cloning WordPress core, installing it, and AFTER_START's plugin activation sweep plus two
# rewrite flushes - the 60s that used to cover the whole boot is not enough for that.
ionos.wordpress.log_info "waiting for container ${CONTAINER_NAME} to finish its startup ..."
ENTRYPOINT_COMPLETE=
MARKER_UNSUPPORTED=
for _ in $(seq 1 180); do
  if docker exec "$CONTAINER_NAME" test -f /run/entrypoint-complete 2>/dev/null; then
    ENTRYPOINT_COMPLETE=1
    break
  fi

  # A container created from an image predating the marker can never satisfy the check. Rather
  # than time out for three minutes, fall back to the old HTTP-only behaviour - `docker start`
  # on a long-lived dev container re-runs whatever entrypoint that container was created with,
  # so this stays reachable even with an up-to-date image. `docker exec true` first: an exec
  # failing only because the container has not come up yet must not be read as "unsupported".
  if docker exec "$CONTAINER_NAME" true 2>/dev/null &&
    ! docker exec "$CONTAINER_NAME" grep -q entrypoint-complete /docker-entrypoint.sh 2>/dev/null; then
    MARKER_UNSUPPORTED=1
    ionos.wordpress.log_warn \
      "container ${CONTAINER_NAME} predates the startup marker - recreate it via 'pnpm destroy'"
    break
  fi

  sleep 1
done
if [[ -z "$ENTRYPOINT_COMPLETE" ]] && [[ -z "$MARKER_UNSUPPORTED" ]]; then
  ionos.wordpress.log_error "container ${CONTAINER_NAME} did not finish starting up - see 'pnpm logs'"
  exit 1
fi

ionos.wordpress.log_info "waiting for http://localhost:${HTTP_PORT}/ to come up ..."
HTTP_CODE=000
for i in $(seq 1 60); do
  HTTP_CODE="$(curl -s -o /dev/null -w '%{http_code}' "http://localhost:${HTTP_PORT}/" || true)"
  [[ "$HTTP_CODE" == '200' ]] && break
  sleep 1
done
if [[ "$HTTP_CODE" != '200' ]]; then
  ionos.wordpress.log_error "wordpress did not become reachable within the timeout (last http code: $HTTP_CODE) - see 'pnpm logs'"
  exit 1
fi

echo "You can access the wordpress site at http://localhost:${HTTP_PORT}"
