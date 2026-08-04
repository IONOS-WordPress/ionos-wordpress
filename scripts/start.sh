#!/usr/bin/env bash

#
# script is not intended to be executed directly. use `pnpm exec ...` instead or call it as package script.
#
# this script is used to start the persistent wp-alpine development container
#

# bootstrap the environment
source "$(realpath $0 | xargs dirname)/includes/bootstrap.sh"

# (re)build the project (this also (re)builds the wp-alpine image locally whenever its
# Dockerfile/entrypoint changed, via scripts/build.sh's docker-package build dispatch)
if [[ "${BUILD_UP_TO_DATE:-}" == '1' ]]; then
  # skip building if BUILD_UP_TO_DATE is set to 1
  ionos.wordpress.log_warn "skip (re)building : BUILD_UP_TO_DATE=1 detected"
else
  pnpm build
fi

# WORDPRESS_VERSION accepts a release version or an "owner/repo#ref" git ref (see
# packages/docker/wp-alpine/docker-entrypoint.sh) - sanitize it into a filesystem-safe
# directory name for the shared, version-keyed core cache below.
readonly VERSION_DIR="${WORDPRESS_VERSION//[\/#]/-}"
readonly CORE_DIR="${MNT_HOME}/wordpress-core/${VERSION_DIR}"
readonly STACK_DIR="${MNT_HOME}/dev"

# create the shared, version-keyed core dir and this stack's per-container overlay
# dirs/files. Docker auto-creates missing bind-mount *directories* on `docker run`,
# but not missing bind-mounted *files* (wp-config.php/.htaccess) - a missing file
# source would otherwise turn into an empty directory inside the container.
mkdir -p "$CORE_DIR"
mkdir -p "$STACK_DIR/wp-content/plugins" "$STACK_DIR/wp-content/themes" "$STACK_DIR/wp-content/mu-plugins" "$STACK_DIR/wp-content/uploads"
touch -a "$STACK_DIR/wp-config.php" "$STACK_DIR/.htaccess"

# build the --volume argument list for `docker run`, dynamically discovering the
# monorepo's wp-plugin/wp-theme/wp-mu-plugin packages - one bind-mount per package,
# matching wp-env's previous per-item `mappings`/`plugins`/`themes` granularity.
VOLUME_ARGS=(
  --volume "$(pwd)/${CORE_DIR}:/htdocs"
  --volume "$(pwd)/${STACK_DIR}/wp-content/plugins:/htdocs/wp-content/plugins"
  --volume "$(pwd)/${STACK_DIR}/wp-content/themes:/htdocs/wp-content/themes"
  --volume "$(pwd)/${STACK_DIR}/wp-content/mu-plugins:/htdocs/wp-content/mu-plugins"
  --volume "$(pwd)/${STACK_DIR}/wp-content/uploads:/htdocs/wp-content/uploads"
  --volume "$(pwd)/${STACK_DIR}/wp-config.php:/htdocs/wp-config.php"
  --volume "$(pwd)/${STACK_DIR}/.htaccess:/htdocs/.htaccess"
)

if [[ "${TEST_PRODUCTION:-}" == 'true' ]]; then
  # mount the transpiled dist/ output instead of source
  for PLUGIN in $(find packages/wp-plugin -mindepth 1 -maxdepth 1 -type d -printf '%f\n' 2>/dev/null || echo ''); do
    zip_archive=$(find packages/wp-plugin/${PLUGIN} -regex ".*\.zip" -printf '%f\n' 2>/dev/null || echo '')
    VOLUME_ARGS+=(--volume "$(pwd)/packages/wp-plugin/${PLUGIN}/dist/${zip_archive%.zip}/${PLUGIN}:/htdocs/wp-content/plugins/${PLUGIN}")
  done

  for THEME in $(find packages/wp-theme -mindepth 1 -maxdepth 1 -type d -printf '%f\n' 2>/dev/null || echo ''); do
    zip_archive=$(find packages/wp-theme/${THEME} -regex ".*\.zip" -printf '%f\n' 2>/dev/null || echo '')
    VOLUME_ARGS+=(--volume "$(pwd)/packages/wp-theme/${THEME}/dist/${zip_archive%.zip}/${THEME}:/htdocs/wp-content/themes/${THEME}")
  done

  for PLUGIN in $(find packages/wp-mu-plugin -mindepth 1 -maxdepth 1 -type d -printf '%f\n' 2>/dev/null || echo ''); do
    if [[ -d "./packages/wp-mu-plugin/${PLUGIN}/${PLUGIN}" ]]; then
      zip_archive=$(find packages/wp-mu-plugin/${PLUGIN} -regex ".*\.zip" -printf '%f\n' 2>/dev/null || echo '')
      VOLUME_ARGS+=(--volume "$(pwd)/packages/wp-mu-plugin/${PLUGIN}/dist/${zip_archive%.zip}/${PLUGIN}/${PLUGIN}.php:/htdocs/wp-content/mu-plugins/${PLUGIN}.php")
      VOLUME_ARGS+=(--volume "$(pwd)/packages/wp-mu-plugin/${PLUGIN}/dist/${zip_archive%.zip}/${PLUGIN}/${PLUGIN}:/htdocs/wp-content/mu-plugins/${PLUGIN}")
    fi
  done
else
  for PLUGIN in $(find packages/wp-plugin -mindepth 1 -maxdepth 1 -type d -printf '%f\n' 2>/dev/null || echo ''); do
    VOLUME_ARGS+=(--volume "$(pwd)/packages/wp-plugin/${PLUGIN}:/htdocs/wp-content/plugins/${PLUGIN}")
  done

  for THEME in $(find packages/wp-theme -mindepth 1 -maxdepth 1 -type d -printf '%f\n' 2>/dev/null || echo ''); do
    VOLUME_ARGS+=(--volume "$(pwd)/packages/wp-theme/${THEME}:/htdocs/wp-content/themes/${THEME}")
  done

  for PLUGIN in $(find packages/wp-mu-plugin -mindepth 1 -maxdepth 1 -type d -printf '%f\n' 2>/dev/null || echo ''); do
    VOLUME_ARGS+=(--volume "$(pwd)/packages/wp-mu-plugin/${PLUGIN}/${PLUGIN}.php:/htdocs/wp-content/mu-plugins/${PLUGIN}.php")
    if [[ -d "./packages/wp-mu-plugin/${PLUGIN}/${PLUGIN}" ]]; then
      VOLUME_ARGS+=(--volume "$(pwd)/packages/wp-mu-plugin/${PLUGIN}/${PLUGIN}:/htdocs/wp-content/mu-plugins/${PLUGIN}")
    fi
  done
fi

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
