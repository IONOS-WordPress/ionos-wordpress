#!/usr/bin/env bash

#
# pulls a previously published image of a docker workspace package
# (wordpress-alpine, rector-php, ecs-php, dennis-i18n, ...) from the registry and
# tags it locally as <docker-image-name>:<package-version> and :latest, plus
# touches <path>/build-info - so scripts/build.sh's existing local-cache
# check (ionos.wordpress.build_workspace_package_docker) skips rebuilding it.
#
# on a cache hit it also writes <path>/image-pull-hit (gitignored) so the
# matching docker-subproject-image-push.sh can skip re-pushing an image the
# registry already has.
#
# must run inside the same docker daemon scripts/build.sh's docker build
# step runs in (the devcontainer's docker-in-docker daemon in CI, see
# devcontainer-shell-run) - falls back silently to a local build if the tag
# isn't published yet/pull fails.
#
# usage: docker-subproject-image-pull.sh <path> <registry> <repository> <tag>
# requires IMAGE_REGISTRY_USERNAME / IMAGE_REGISTRY_PASSWORD in the environment
#

set -euo pipefail

SUBPROJECT_PATH="$1"
REGISTRY="$2"
REPOSITORY="$3"
TAG="$4"

IMAGE="${REGISTRY}/${REPOSITORY}:${TAG}"
LOCAL_IMAGE_NAME="$(jq -r '.name' "$SUBPROJECT_PATH/package.json" | sed -r 's/@//g')"
PACKAGE_VERSION="$(jq -r '.version' "$SUBPROJECT_PATH/package.json")"

rm -f "$SUBPROJECT_PATH/image-pull-hit"

echo "$IMAGE_REGISTRY_PASSWORD" | docker login "$REGISTRY" --username "$IMAGE_REGISTRY_USERNAME" --password-stdin

# retry: ghcr.io answers a burst of pulls (devcontainer image, wordpress-alpine,
# rector-php, the php:*-cli images) with a secondary rate limit that aborts the
# transfer mid-layer with a 403 - transient, and a plain local build instead of a
# ~1s pull costs minutes. only worth retrying while the tag actually exists though:
# a not-yet-published one never appears by waiting (and if the manifest check is
# rate-limited too we just fall through to the local build, which is the correct
# outcome anyway)
PULLED=no
for ATTEMPT in 1 2 3; do
  docker pull "$IMAGE" && { PULLED=yes; break; }
  [[ $ATTEMPT -eq 3 ]] && break
  docker manifest inspect "$IMAGE" >/dev/null 2>&1 || break
  echo "pull of $IMAGE failed although the tag exists - retrying in 20s ($ATTEMPT/3) ..."
  sleep 20
done

if [[ "$PULLED" == 'yes' ]]; then
  docker tag "$IMAGE" "${LOCAL_IMAGE_NAME}:${PACKAGE_VERSION}"
  docker tag "$IMAGE" "${LOCAL_IMAGE_NAME}:latest"
  touch "$SUBPROJECT_PATH/build-info"
  touch "$SUBPROJECT_PATH/image-pull-hit"
else
  echo "::warning::could not pull prebuilt image $IMAGE - falling back to a local build"
fi
