#
# this file is sourced by scripts/start.sh and scripts/test.sh to build the
# `docker run` --volume argument list for a wp-alpine stack.
#

#
# prepares a stack's per-container overlay dirs/files (wp-content/{plugins,themes,
# mu-plugins,uploads}, wp-config.php, .htaccess) and appends `docker run` --volume
# args for it plus every discovered wp-plugin/wp-theme/wp-mu-plugin package -
# one bind-mount per package, matching wp-env's previous per-item
# `mappings`/`plugins`/`themes` granularity.
#
# @param $1 path to this stack's overlay dir (e.g. "${MNT_HOME}/dev")
# @param $2 path to the shared, version-keyed core cache dir (bind-mounted at /htdocs)
#
# populates the global array VOLUME_ARGS (must be declared by the caller)
#
function ionos.wordpress.build_wp_volume_args() {
  local stack_dir="$1"
  local core_dir="$2"

  # Docker auto-creates missing bind-mount *directories* on `docker run`, but not
  # missing bind-mounted *files* (wp-config.php/.htaccess) - a missing file source
  # would otherwise turn into an empty directory inside the container.
  mkdir -p "$core_dir"
  mkdir -p "$stack_dir/wp-content/plugins" "$stack_dir/wp-content/themes" "$stack_dir/wp-content/mu-plugins" "$stack_dir/wp-content/uploads"
  touch -a "$stack_dir/wp-config.php" "$stack_dir/.htaccess"

  # wp-content/themes is a per-stack overlay, so only the very first container to
  # ever download a given WORDPRESS_VERSION gets bundled themes for free (extracted
  # directly into its own overlay by docker-entrypoint.sh). Every other/later
  # stack (e.g. the ephemeral test container, once the dev container has already
  # claimed that first download) needs seeding from the shared
  # .default-themes-cache docker-entrypoint.sh stashes outside the overlaid
  # subtrees - mirrors the prototype's prepare-mounts.sh.
  if [[ -d "$core_dir/.default-themes-cache" ]] && [[ -z "$(ls -A "$stack_dir/wp-content/themes" 2>/dev/null)" ]]; then
    cp -r "$core_dir/.default-themes-cache/." "$stack_dir/wp-content/themes/"
  fi

  VOLUME_ARGS+=(
    --volume "$(pwd)/${core_dir}:/htdocs"
    --volume "$(pwd)/${stack_dir}/wp-content/plugins:/htdocs/wp-content/plugins"
    --volume "$(pwd)/${stack_dir}/wp-content/themes:/htdocs/wp-content/themes"
    --volume "$(pwd)/${stack_dir}/wp-content/mu-plugins:/htdocs/wp-content/mu-plugins"
    --volume "$(pwd)/${stack_dir}/wp-content/uploads:/htdocs/wp-content/uploads"
    --volume "$(pwd)/${stack_dir}/wp-config.php:/htdocs/wp-config.php"
    --volume "$(pwd)/${stack_dir}/.htaccess:/htdocs/.htaccess"
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
}
export -f ionos.wordpress.build_wp_volume_args

#
# sanitizes WORDPRESS_VERSION (a release version or an "owner/repo#ref" git ref, see
# packages/docker/wp-alpine/docker-entrypoint.sh) into a filesystem-safe directory
# name for the shared, version-keyed caches under ${MNT_HOME}.
#
function ionos.wordpress.wordpress_version_dir() {
  echo "${1//[\/#]/-}"
}
export -f ionos.wordpress.wordpress_version_dir
