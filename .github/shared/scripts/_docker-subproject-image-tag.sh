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
# accepts more than one <path>, in which case the tag is the last commit touching *any*
# of them. the dev container needs that: it installs the packages/docker/* CLI tools
# natively from their committed composer.lock files (see .devcontainer/Dockerfile), so a
# tool version bump has to invalidate the dev container image too - otherwise CI would
# keep reusing an image holding the previous tool versions while the tool images
# themselves get rebuilt, and the two paths would silently disagree. over-invalidating
# (a README touch in a tool directory rebuilds the dev container) is the safe direction.
#
# history-derived rather than content-derived (a git tree hash) on purpose: it is
# readable in the registry UI and sorts chronologically, which makes stale tags
# prunable. the cost is that reverting <path> to an already published state yields a
# new tag and one extra build instead of re-hitting the existing image.
#
# needs the git history, so callers must check out with 'fetch-depth: 0' - a depth-1
# checkout is rejected below instead of silently yielding an image ref ending in ':'.
#
# usage: _docker-subproject-image-tag.sh <path> [<path>...]
#

set -euo pipefail

if [[ $# -eq 0 ]]; then
  echo "::error::usage: _docker-subproject-image-tag.sh <path> [<path>...]" >&2
  exit 1
fi

TAG="$(git log -1 --format='%cd' --date=format:'%Y-%m-%d-%H-%M-%S' -- "$@")"

if [[ -z "$TAG" ]]; then
  echo "::error::no commit found touching '$*' - either the path is wrong or this is a shallow clone (actions/checkout needs 'fetch-depth: 0')" >&2
  exit 1
fi

echo "$TAG"
