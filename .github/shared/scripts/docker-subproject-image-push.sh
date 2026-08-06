#!/usr/bin/env bash

#
# pushes a packages/docker/* sub-project image (freshly built by
# scripts/build.sh, or already restored by docker-subproject-image-pull.sh)
# to the registry, tagged with the same date-based tag
# docker-subproject-image-pull.sh looks for - so the next workflow run can
# pull it instead of rebuilding. a no-op push (no new layers to upload) when
# the pull step already found and restored a cached image.
#
# must run inside the same docker daemon scripts/build.sh's docker build
# step ran in (the devcontainer's docker-in-docker daemon in CI, see
# devcontainer-shell-run).
#
# usage: docker-subproject-image-push.sh <path> <registry> <repository> <tag>
# requires IMAGE_REGISTRY_USERNAME / IMAGE_REGISTRY_PASSWORD in the environment
#

set -euo pipefail

SUBPROJECT_PATH="$1"
REGISTRY="$2"
REPOSITORY="$3"
TAG="$4"

LOCAL_IMAGE_NAME="$(jq -r '.name' "$SUBPROJECT_PATH/package.json" | sed -r 's/@//g')"
PACKAGE_VERSION="$(jq -r '.version' "$SUBPROJECT_PATH/package.json")"
IMAGE="${REGISTRY}/${REPOSITORY}:${TAG}"

echo "$IMAGE_REGISTRY_PASSWORD" | docker login "$REGISTRY" --username "$IMAGE_REGISTRY_USERNAME" --password-stdin

docker tag "${LOCAL_IMAGE_NAME}:${PACKAGE_VERSION}" "$IMAGE"
docker push "$IMAGE"
