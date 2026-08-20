#
# dual-mode dispatch for the CLI tools that live in packages/docker/*.
#
# ecs-php, rector-php, potrans and dennis-i18n each exist twice:
#
#   - as a docker image (packages/docker/<tool>/Dockerfile), which is what developers
#     working outside the dev container use, and
#   - installed natively into the dev container image (see .devcontainer/Dockerfile),
#     which is what dev container users and CI - which runs everything through the dev
#     container - use.
#
# the native path exists because every dockerized invocation costs a docker-in-docker
# round trip, and because a fresh checkout otherwise has to build five docker images
# before 'pnpm lint' does anything. it is not a different tool: both paths install from
# the same committed composer.lock / pinned version, so they run identical versions. see
# .beans/e6mc--*.md for the full rationale.
#
# CAVEAT: CI only ever exercises the native path, so the docker path is not covered by
# automation. that is a deliberate, documented trade (same bean). IONOS_WP_FORCE_DOCKER=1
# forces the docker path and is the way to verify it by hand:
#
#   IONOS_WP_FORCE_DOCKER=1 pnpm lint
#

# root the dev container installs the native tools under. mirrors the docker images'
# layout on purpose: every tool gets its own COMPOSER_HOME, so its binary sits at
# "$COMPOSER_HOME/vendor/bin/<tool>" exactly as it sits at /composer/vendor/bin/<tool>
# inside the image. that is what lets the shared config files
# (packages/docker/ecs-php/ecs-config.php, packages/docker/rector-php/rector-config-*.php)
# resolve their vendor paths through $COMPOSER_HOME in both modes.
export IONOS_NATIVE_TOOLS_PREFIX="${IONOS_NATIVE_TOOLS_PREFIX:-/opt/ionos-wordpress/tools}"

# path of each tool's executable, relative to $IONOS_NATIVE_TOOLS_PREFIX.
# the composer-installed tools follow "<tool>/vendor/bin/<binary>"; dennis is a python
# package installed with pipx, which puts its entry point in a plain bin/ directory.
declare -A IONOS_NATIVE_TOOL_PATHS=(
  [ecs-php]='ecs-php/vendor/bin/ecs'
  [rector-php]='rector-php/vendor/bin/rector'
  [potrans]='potrans/vendor/bin/potrans'
  [dennis-i18n]='dennis-i18n/bin/dennis-cmd'
)
# bash cannot export associative arrays into a child process's environment - only
# plain scalars and functions (via `export -f`) propagate that way. every caller
# of ionos.wordpress.native_tool() reaches this file via `source` (a fork, not a
# re-exec), which inherits the array regardless; a re-exec'd process (`bash -c`,
# a separately invoked #!/usr/bin/env bash script) would just have to source
# this file itself, same as every other function/variable declared here.

#
# echoes the absolute path of a natively installed tool and returns 0, or returns 1 if the
# tool must be run from its docker image instead.
#
# probes for the executable rather than sniffing the environment: $REMOTE_CONTAINERS /
# $CODESPACES are not reliably set by devcontainers/ci, so CI would pick the wrong branch.
# probing behaves identically in CI, in the dev container and on a host that happens to
# have the tool, and degrades to docker on its own.
#
# @param $1 tool name (= the packages/docker/<tool> directory name)
#
function ionos.wordpress.native_tool() {
  local tool="$1"

  # explicit escape hatch - see the caveat at the top of this file
  [[ "${IONOS_WP_FORCE_DOCKER:-}" == '1' ]] && return 1

  local relative_path="${IONOS_NATIVE_TOOL_PATHS[$tool]:-}"
  if [[ -z "$relative_path" ]]; then
    ionos.wordpress.log_error "unknown tool '$tool' - expected one of: ${!IONOS_NATIVE_TOOL_PATHS[*]}"
    exit 1
  fi

  local path="$IONOS_NATIVE_TOOLS_PREFIX/$relative_path"
  [[ -x "$path" ]] || return 1

  echo "$path"
}
export -f ionos.wordpress.native_tool

#
# echoes the COMPOSER_HOME a natively installed tool was installed under.
#
# the shared config files resolve their vendor paths through $COMPOSER_HOME, so it has to
# be exported into the tool process - the dev container does not set it globally (that
# would point every unrelated composer invocation at a tool's private home).
#
# @param $1 tool name (= the packages/docker/<tool> directory name)
#
function ionos.wordpress.native_tool_composer_home() {
  echo "$IONOS_NATIVE_TOOLS_PREFIX/$1"
}
export -f ionos.wordpress.native_tool_composer_home

#
# runs a tool natively if available, else falls back to its docker image - for the
# common case where the argument list is identical between both modes (the docker
# image bind-mounts the workspace at /project/ with that as its WORKDIR, so paths
# are already relative to the repository root in both modes, same as natively).
#
# not used for tools whose docker/native invocations construct genuinely different
# arguments (e.g. rector's bind-mounted vs. real host paths) - only for the shared-args
# shape.
#
# @param $1 tool name (= the packages/docker/<tool> directory name)
# @param $2 docker image name (e.g. "ionos-wordpress/ecs-php")
# @param $3 (nameref) array of extra `docker run` flags (e.g. --user/-i/-e ...)
# @param $@ (remaining) the tool's argument list, shared between both modes
#
function ionos.wordpress.run_native_or_docker() {
  local tool="$1"
  local docker_image="$2"
  local -n extra_docker_flags="$3"
  shift 3

  local tool_path
  if tool_path="$(ionos.wordpress.native_tool "$tool")"; then
    "$tool_path" "$@"
  else
    docker run \
      $DOCKER_FLAGS \
      --rm \
      "${extra_docker_flags[@]}" \
      -v "$(pwd)":/project/ \
      "$docker_image" \
      "$@"
  fi
}
export -f ionos.wordpress.run_native_or_docker

#
# true if any tool still has to be run from its docker image, i.e. if the docker images
# listed have to be built at all. lets callers skip 'pnpm build --filter <tool>'
# entirely when everything they need is available natively.
#
# @param $@ tool names
#
function ionos.wordpress.needs_docker_tools() {
  local tool
  for tool in "$@"; do
    ionos.wordpress.native_tool "$tool" >/dev/null || return 0
  done
  return 1
}
export -f ionos.wordpress.needs_docker_tools
