#!/usr/bin/env bash

#
# script is not intended to be executed directly. use `pnpm exec ...` instead or call it as package script.
#
# this script is used to start the persistent wp-alpine development container
#

# bootstrap the environment
source "$(realpath $0 | xargs dirname)/includes/bootstrap.sh"
source "$(realpath $0 | xargs dirname)/includes/_docker-mounts.sh"

# (re)build the project (this also (re)builds the wp-alpine image locally whenever its
# Dockerfile/entrypoint changed, via scripts/build.sh's docker-package build dispatch)
if [[ "${BUILD_UP_TO_DATE:-}" == '1' ]]; then
  # skip building if BUILD_UP_TO_DATE is set to 1
  ionos.wordpress.log_warn "skip (re)building : BUILD_UP_TO_DATE=1 detected"
else
  pnpm build
fi

readonly VERSION_DIR="$(ionos.wordpress.wordpress_version_dir "$WORDPRESS_VERSION")"
readonly CORE_DIR="${MNT_HOME}/wordpress-core/${VERSION_DIR}"
readonly STACK_DIR="${MNT_HOME}/dev"

# build the --volume argument list for `docker run`, dynamically discovering the
# monorepo's wp-plugin/wp-theme/wp-mu-plugin packages (shared with scripts/test.sh).
VOLUME_ARGS=()
ionos.wordpress.build_wp_volume_args "$STACK_DIR" "$CORE_DIR"

if [[ -n "${AFTER_START:-}" ]]; then
  VOLUME_ARGS+=(--volume "$(realpath "$AFTER_START"):/after-start.sh")
fi

if docker ps -a --filter "name=${CONTAINER_NAME}" --format '{{.Names}}' | grep -qx "$CONTAINER_NAME"; then
  # container already exists (running or stopped) - (re)start it as-is, matching
  # today's idempotent `pnpm start` behavior. Volume/env changes only take effect
  # after `pnpm destroy` recreates the container.
  docker start "$CONTAINER_NAME" >/dev/null
else
  docker run \
    --detach \
    --tty \
    --interactive \
    --name "$CONTAINER_NAME" \
    --hostname "$CONTAINER_NAME" \
    --publish "${HTTP_PORT}:80" \
    --publish "${SSH_PORT}:22" \
    --env WORDPRESS_VERSION="$WORDPRESS_VERSION" \
    --env WP_PASSWORD="$WP_PASSWORD" \
    --env HTTP_PORT="$HTTP_PORT" \
    --env AFTER_START="${AFTER_START:-}" \
    "${VOLUME_ARGS[@]}" \
    ionos-wordpress/wp-alpine:latest >/dev/null
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
