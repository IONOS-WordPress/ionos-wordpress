#!/usr/bin/env bash

#
# script is not intended to be executed directly. use `pnpm exec ...` instead or call it as package script.
#
# this script deletes the container packages this repository publishes to the GitHub
# container registry - the dev container image and one image per packages/docker/*
# workspace package - including every version they hold.
#
# it deletes whole packages, not just versions: that is the only way to clear the
# legacy per-timestamp dev container packages (the tag used to live in the package
# *name*, see .github/shared/actions/devcontainer-image-name/action.yaml), which
# version-level pruning cannot reach.
#
# the target list is derived from this repository, never from "everything the
# organization owns" - packages published by other repositories are left alone and
# reported as skipped.
#
# DESTRUCTIVE AND IRREVERSIBLE. deleting a package deletes all of its versions, and
# GitHub cannot restore them. dry run is the default; deletion needs an explicit --yes.
# the next CI run rebuilds and republishes whatever it needs, so the cost of being
# wrong is build time rather than lost artifacts - but a release running concurrently
# will rebuild from scratch.
#

# bootstrap the environment
source "$(realpath $0 | xargs dirname)/includes/bootstrap.sh"

# MARK: parse arguments
YES=no

while [[ $# -gt 0 ]]; do
  case $1 in
    --help)
      # print everything in this script file after the '###help-message' marker
      printf "$(sed -e '1,/^###help-message/d' "$0")\n"
      exit
      ;;
    --yes|-y)
      YES=yes
      shift
      ;;
    *)
      ionos.wordpress.log_error "unknown option $1 (try --help)"
      exit 1
      ;;
  esac
done
# ENDMARK:

# MARK: preconditions
if [[ -z "$GH_TOKEN" ]]; then
  ionos.wordpress.log_error 'GH_TOKEN is not set.'
  cat <<'EOF'

GH_TOKEN is required to list and delete GitHub packages.

It's expected to be set in a `.secrets` file (see `.secrets.example`).

The token MUST BE a GitHub personal access token (classic) carrying the `read:packages`
and `delete:packages` scopes - fine-grained tokens cannot delete container packages.

EOF
  exit 1
fi

# reuse the shared wrapper so the "is gh installed" diagnostics live in one place
GH="$GIT_ROOT_PATH/scripts/gh-cli.sh"
# ENDMARK:

# MARK: resolve the package owner
# derived from the repository url rather than hardcoded, so a fork purges its own packages
REPOSITORY_URL="$(jq -r '.repository.url' "$GIT_ROOT_PATH/package.json")"
# "git@github.com:IONOS-WordPress/ionos-wordpress.git" -> "IONOS-WordPress" / "ionos-wordpress"
REPOSITORY_SLUG="${REPOSITORY_URL##*:}"
REPOSITORY_SLUG="${REPOSITORY_SLUG%.git}"
OWNER="${REPOSITORY_SLUG%%/*}"
REPOSITORY_NAME="${REPOSITORY_SLUG#*/}"

# ghcr.io package names are lowercase, the repository slug is not
REPOSITORY_NAME="${REPOSITORY_NAME@L}"

# organization and user accounts have different package endpoints, and only the
# authenticated user's own packages are reachable for a user account
if "$GH" api "/orgs/${OWNER}" >/dev/null 2>&1; then
  LIST_ENDPOINT="/orgs/${OWNER}/packages?package_type=container&per_page=100"
  PACKAGE_ENDPOINT_PREFIX="/orgs/${OWNER}/packages/container"
else
  LIST_ENDPOINT="/user/packages?package_type=container&per_page=100"
  PACKAGE_ENDPOINT_PREFIX="/user/packages/container"
fi
# ENDMARK:

# MARK: compute the set of packages this repository publishes
# keep in sync with the workflows that publish them:
#   .github/shared/actions/devcontainer-image-name/action.yaml (dev container)
#   .github/workflows/build-wordpress-alpine-image.yaml        (IMAGE_REPOSITORY)
#   .github/workflows/integration.yaml                         (one per packages/docker/*)
declare -A EXPECTED_PACKAGES=()

EXPECTED_PACKAGES["${REPOSITORY_NAME}-devcontainer"]=1

# wordpress-alpine publishes under IMAGE_REPOSITORY (".../wordpress-alpine-dev"), which
# deliberately differs from its workspace package name - take it from the same .env the
# workflows read rather than restating it here
[[ -n "$IMAGE_REPOSITORY" ]] && EXPECTED_PACKAGES["${IMAGE_REPOSITORY##*/}"]=1

for PACKAGE_JSON in "$GIT_ROOT_PATH"/packages/docker/*/package.json; do
  [[ -f "$PACKAGE_JSON" ]] || continue
  EXPECTED_PACKAGES["$(basename "$(dirname "$PACKAGE_JSON")")"]=1
done

# the dev container used to carry its timestamp in the package name instead of the tag,
# leaving one package per change behind - those are exactly what this script exists for
LEGACY_DEVCONTAINER_PATTERN="^${REPOSITORY_NAME}-[0-9]{4}(-[0-9]{2}){5}-devcontainer$"
# ENDMARK:

# MARK: match against what is actually published
ionos.wordpress.log_header "container packages owned by '${OWNER}' :"

MATCHED=()
SKIPPED=()

while read -r PACKAGE_NAME; do
  [[ -n "$PACKAGE_NAME" ]] || continue
  if [[ -n "${EXPECTED_PACKAGES[$PACKAGE_NAME]:-}" ]] || [[ "$PACKAGE_NAME" =~ $LEGACY_DEVCONTAINER_PATTERN ]]; then
    MATCHED+=("$PACKAGE_NAME")
  else
    SKIPPED+=("$PACKAGE_NAME")
  fi
done < <("$GH" api --paginate "$LIST_ENDPOINT" --jq '.[].name')

for PACKAGE_NAME in "${SKIPPED[@]}"; do
  echo "  skip   $PACKAGE_NAME (not published by this repository)"
done

for PACKAGE_NAME in "${MATCHED[@]}"; do
  # surface the blast radius per package - a legacy dev container package holds one
  # version, wordpress-alpine-dev holds two per publish
  VERSION_COUNT="$("$GH" api --paginate "${PACKAGE_ENDPOINT_PREFIX}/$(jq -rn --arg v "$PACKAGE_NAME" '$v|@uri')/versions?per_page=100" --jq '.[].id' 2>/dev/null | wc -l)"
  echo "  DELETE $PACKAGE_NAME ($VERSION_COUNT version(s))"
done

if [[ ${#MATCHED[@]} -eq 0 ]]; then
  ionos.wordpress.log_info 'nothing to purge - no published package matches this repository'
  exit 0
fi
# ENDMARK:

# MARK: purge
if [[ "$YES" != 'yes' ]]; then
  cat <<EOF

dry run - nothing was deleted.

${#MATCHED[@]} package(s) would be deleted from '${OWNER}', including every version they
hold. this cannot be undone. re-run with --yes to actually delete them :

  pnpm purge-registry --yes

EOF
  exit 0
fi

for PACKAGE_NAME in "${MATCHED[@]}"; do
  ionos.wordpress.log_warn "deleting package $PACKAGE_NAME ..."
  "$GH" api --method DELETE "${PACKAGE_ENDPOINT_PREFIX}/$(jq -rn --arg v "$PACKAGE_NAME" '$v|@uri')"
done

ionos.wordpress.log_info "purged ${#MATCHED[@]} container package(s) from '${OWNER}'"
# ENDMARK:

exit 0

###help-message
Usage: pnpm purge-registry [--yes]

Deletes the container packages this repository publishes to the GitHub container
registry ghcr.io - the dev container image and one image per packages/docker/*
workspace package - including every version they hold.

Packages published by other repositories in the same organization are never touched;
they are listed as 'skip'.

Options:
  --yes, -y   actually delete. without it the script only reports what it would do.
  --help      show this message

DESTRUCTIVE AND IRREVERSIBLE - GitHub cannot restore a deleted package version. The
next CI run rebuilds and republishes whatever it needs, so the cost is build time.
Never run it while a release is in flight.

Requires GH_TOKEN in .secrets - a classic personal access token with the
'read:packages' and 'delete:packages' scopes.
