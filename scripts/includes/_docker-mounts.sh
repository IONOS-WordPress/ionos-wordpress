#
# this file is sourced by scripts/start.sh and scripts/test.sh to build the
# `docker run` --volume argument list for a wordpress-alpine stack.
#

#
# TEST_PRODUCTION=true mounts a package's transpiled dist/ output instead of its
# source, but rector's build step doesn't carry phpunit/ test directories into
# dist/ (see scripts/build.sh's --exclude=tests/) - bind-mount each source
# phpunit/ dir directly at its equivalent path under the dist mount so
# `pnpm test:php` still finds and runs them when testing against the
# production build (mirrors the pre-wordpress-alpine .wp-env.override.json-era rsync
# step in start.sh, but as a bind mount rather than a filesystem copy - dist/
# is also bind-mounted wholesale in source mode, so writing actual test files
# into it would leak stale copies into non-TEST_PRODUCTION runs. Docker still
# creates the (empty) mount-point directory tree on the host to hang each
# nested mount off of, since the dist mount it nests under is itself a host
# bind mount - harmless clutter under the gitignored dist/ tree, wiped by the
# next build.
#
# appends to the global array VOLUME_ARGS (must be declared by the caller)
#
# @param $1 path to the package's source dir (e.g. "packages/wp-plugin/foo/foo")
# @param $2 container path the matching dist dir is mounted at (e.g.
#           "/htdocs/wp-content/plugins/foo/foo")
#
function ionos.wordpress.mount_phpunit_dirs() {
  local source_dir="$1"
  local container_dir="$2"

  for phpunit_dir in $(find "$source_dir" -type d -name phpunit 2>/dev/null || echo ''); do
    local relative_dir="${phpunit_dir#"$source_dir"/}"
    VOLUME_ARGS+=(--volume "$(pwd)/${phpunit_dir}:${container_dir}/${relative_dir}")
  done
}
export -f ionos.wordpress.mount_phpunit_dirs

#
# finds the single build zip archive under a package's dir tree (see
# scripts/build.sh). errors out if none or more than one is found - a stale
# zip left over from a previous version bump (not cleaned before rebuilding)
# must not silently produce a multi-line result that corrupts the caller's
# --volume mount path.
#
# @param $1 path to the package's dir (e.g. "packages/wp-plugin/foo")
# @stdout the zip archive's filename (without path)
#
function ionos.wordpress.find_single_zip_archive() {
  local package_dir="$1"
  local zip_archives
  readarray -t zip_archives < <(find "$package_dir" -regex '.*\.zip' -printf '%f\n' 2>/dev/null | sort)

  if [[ ${#zip_archives[@]} -eq 0 ]]; then
    ionos.wordpress.log_error "no build zip archive found under '${package_dir}' - run 'pnpm build' first"
    exit 1
  fi

  if [[ ${#zip_archives[@]} -gt 1 ]]; then
    ionos.wordpress.log_error "multiple build zip archives found under '${package_dir}' (${zip_archives[*]}) - remove the stale one(s) and rebuild"
    exit 1
  fi

  echo "${zip_archives[0]}"
}
export -f ionos.wordpress.find_single_zip_archive

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
      zip_archive=$(ionos.wordpress.find_single_zip_archive "packages/wp-plugin/${PLUGIN}")
      # dist/<zip>/<plugin> mirrors the package root (loader .php + nested
      # <plugin>/ code dir), same as source - mounted as a whole (unlike
      # mu-plugins, WordPress only needs the loader's own path to resolve),
      # but phpunit test fixtures referenced via relative paths (e.g.
      # ../../foo.json) live inside the nested dir, not the root
      dist_package_root="packages/wp-plugin/${PLUGIN}/dist/${zip_archive%.zip}/${PLUGIN}"
      ionos.wordpress.mount_phpunit_dirs "packages/wp-plugin/${PLUGIN}/${PLUGIN}" "/htdocs/wp-content/plugins/${PLUGIN}/${PLUGIN}"
      VOLUME_ARGS+=(--volume "$(pwd)/${dist_package_root}:/htdocs/wp-content/plugins/${PLUGIN}")
    done

    for THEME in $(find packages/wp-theme -mindepth 1 -maxdepth 1 -type d -printf '%f\n' 2>/dev/null || echo ''); do
      zip_archive=$(ionos.wordpress.find_single_zip_archive "packages/wp-theme/${THEME}")
      VOLUME_ARGS+=(--volume "$(pwd)/packages/wp-theme/${THEME}/dist/${zip_archive%.zip}/${THEME}:/htdocs/wp-content/themes/${THEME}")
    done

    for PLUGIN in $(find packages/wp-mu-plugin -mindepth 1 -maxdepth 1 -type d -printf '%f\n' 2>/dev/null || echo ''); do
      if [[ -d "./packages/wp-mu-plugin/${PLUGIN}/${PLUGIN}" ]]; then
        zip_archive=$(ionos.wordpress.find_single_zip_archive "packages/wp-mu-plugin/${PLUGIN}")
        # dist/<zip>/<plugin> mirrors the package root (loader .php + nested
        # <plugin>/ code dir), same as source - mount the nested dir, not the
        # root, or the loader's require_once __DIR__ . '/<plugin>/...' breaks
        dist_package_root="packages/wp-mu-plugin/${PLUGIN}/dist/${zip_archive%.zip}/${PLUGIN}"
        dist_plugin_dir="${dist_package_root}/${PLUGIN}"
        ionos.wordpress.mount_phpunit_dirs "packages/wp-mu-plugin/${PLUGIN}/${PLUGIN}" "/htdocs/wp-content/mu-plugins/${PLUGIN}"
        VOLUME_ARGS+=(--volume "$(pwd)/${dist_package_root}/${PLUGIN}.php:/htdocs/wp-content/mu-plugins/${PLUGIN}.php")
        VOLUME_ARGS+=(--volume "$(pwd)/${dist_plugin_dir}:/htdocs/wp-content/mu-plugins/${PLUGIN}")
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
# packages/docker/wordpress-alpine/docker-entrypoint.sh) into a filesystem-safe directory
# name for the shared, version-keyed caches under ${MNT_HOME}.
#
function ionos.wordpress.wordpress_version_dir() {
  echo "${1//[\/#]/-}"
}
export -f ionos.wordpress.wordpress_version_dir
