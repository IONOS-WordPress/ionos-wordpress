---
# 9gs2
title: Cache and publish rector-php/ecs-php/potrans/dennis-i18n docker images to the registry
status: completed
type: task
priority: normal
created_at: 2026-08-05T15:20:37Z
updated_at: 2026-08-05T15:28:33Z
---

The lint and integration workflows currently rebuild the rector-php, ecs-php,
potrans, and dennis-i18n docker images (packages/docker/*) from scratch on
every run, since scripts/build.sh's docker-image build step
(ionos.wordpress.build_workspace_package_docker) only skips a rebuild when a
matching image+build-info already exists _locally_ - there's no
registry-backed cache like wp-alpine has via build-wp-alpine-image.yaml.

## Goal

Once one of these images is built (in the lint or integration workflow), push
it to the registry (ghcr.io, same IMAGE_REGISTRY/IMAGE_REPOSITORY-style vars
as wp-alpine) so the next workflow run can pull it instead of rebuilding.

## Tagging

Reuse the _devcontainer_ image's tagging mechanism, not wp-alpine's:
.github/shared/actions/devcontainer-image-name/action.yaml derives the tag
from the git last-modified date of the relevant files
(\`git log -1 --format=\"%cd\" --date=format:'%Y-%m-%d-%H-%M-%S' -- <path>\`),
scoped per docker sub-project directory (e.g. packages/docker/rector-php).
wp-alpine instead uses a content hash (\`git rev-parse HEAD:<path>\`) - do not
copy that approach here.

## Scope

- packages/docker/rector-php (used by scripts/build.sh's wp-plugin rector step)
- packages/docker/ecs-php (used by scripts/lint.sh)
- packages/docker/potrans (used by scripts/lint.sh)
- packages/docker/dennis-i18n (used by scripts/lint.sh)

wp-alpine is out of scope - it already has its own registry-caching workflow
(build-wp-alpine-image.yaml).

## Todo

- [x] Extend/reuse the devcontainer-image-name action's date-based tagging
      logic, generalized to take a docker sub-project path
- [x] Add a pull-before-build step (like the wp-alpine pull step in
      integration.yaml) for each of the 4 images, tagging locally on success
      and falling back to a local build on miss
- [x] Add a push-to-registry step after a fresh local build, mirroring
      build-wp-alpine-image.yaml's publish pattern
- [x] Wire this into both lint.yaml and integration.yaml where these images
      are built
- [x] Verify scripts/build.sh's existing local-cache skip
      (ionos.wordpress.build_workspace_package_docker's build-info check)
      still behaves correctly alongside the new registry cache

## Summary of Changes

- Generalized `.github/shared/actions/devcontainer-image-name` into a new
  `.github/shared/actions/docker-subproject-image-name` composite action that
  computes a date-based tag for an arbitrary path (git last-modified date of
  files under it), and refactored `devcontainer-image-name` to call it
  internally (path=.devcontainer) instead of duplicating the logic.
- Added two new composite actions:
  - `docker-subproject-cache-pull`: pulls `<registry>/<repository>:<tag>`,
    tags it locally as `<pkg-name>:<pkg-version>` and `:latest`, and touches
    `<path>/build-info` so scripts/build.sh's existing local-cache check
    (`ionos.wordpress.build_workspace_package_docker`) skips rebuilding.
    Falls back silently (outputs pulled=false) if the tag isn't published yet.
  - `docker-subproject-cache-push`: tags the freshly built local image and
    pushes it to the registry under the same date-based tag; no-ops when
    `skip` is true (i.e. the pull step already found a cached image).
- Wired pull/push steps into `.github/workflows/integration.yaml`'s `build`
  job (rector-php, around the existing 'build project' step) and `lint` job
  (ecs-php, potrans, dennis-i18n, around 'build necessary dockers'/'lint_project').
  There is no separate lint.yaml - lint is a job inside integration.yaml.
- Added `DOCKER_SUBPROJECT_IMAGE_REPOSITORY_PREFIX` (vars.IMAGE_REPOSITORY_PREFIX,
  default 'ionos-wordpress') and registry credential env vars, following the
  same vars/secrets-with-defaults convention as the existing wp-alpine
  IMAGE_REGISTRY/IMAGE_REPOSITORY.
- Verified locally with a throwaway registry container: pull-miss falls back
  correctly, push succeeds and re-tags, and a subsequent pull hits the cache
  and produces the same local image/build-info state scripts/build.sh's skip
  check expects (confirmed the skip check actually fires).
