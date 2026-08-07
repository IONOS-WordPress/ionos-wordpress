#!/usr/bin/env bash

#
# prints the registry tag for a docker sub-project directory: the committer date of the
# last commit touching <path>, formatted '2026-08-05-11-10-25'.
#
# single source of truth for the repository-wide '<image>:<tag>' scheme - the
# devcontainer (.devcontainer), the wordpress-alpine dev/test image and the
# rector-php/ecs-php/dennis-i18n tool images all resolve their tag through here, so a
# tag means the same thing whichever image it is attached to. the wordpress-alpine image
# appends a '-php<version>' suffix on top, because one commit publishes several PHP
# variants of the same content.
#
# history-derived rather than content-derived (a git tree hash) on purpose: it is
# readable in the registry UI and sorts chronologically, which makes stale tags
# prunable. the cost is that reverting <path> to an already published state yields a
# new tag and one extra build instead of re-hitting the existing image.
#
# needs the git history, so callers must check out with 'fetch-depth: 0' - a depth-1
# checkout is rejected below instead of silently yielding an image ref ending in ':'.
#
# usage: docker-subproject-image-tag.sh <path>
#

set -euo pipefail

SUBPROJECT_PATH="${1:?usage: docker-subproject-image-tag.sh <path>}"

TAG="$(git log -1 --format='%cd' --date=format:'%Y-%m-%d-%H-%M-%S' -- "$SUBPROJECT_PATH")"

if [[ -z "$TAG" ]]; then
  echo "::error::no commit found touching '$SUBPROJECT_PATH' - either the path is wrong or this is a shallow clone (actions/checkout needs 'fetch-depth: 0')" >&2
  exit 1
fi

echo "$TAG"
