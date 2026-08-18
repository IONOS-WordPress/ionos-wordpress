---
# 3ihn
title: Dedupe docker image-name derivation logic (build.sh vs distclean.sh)
status: todo
type: task
priority: low
created_at: 2026-08-17T13:39:05Z
updated_at: 2026-08-17T13:39:05Z
parent: qi52
---

scripts/build.sh (ionos.wordpress.build_workspace_package_docker, ~line 219) and scripts/distclean.sh (~line 32) contain an identical 4-line block deriving DOCKER_IMAGE_NAME/DOCKER_USERNAME/DOCKER_REPOSITORY from a package.json name (sed -r 's/@//g', split on '/', honor DOCKER_USERNAME/DOCKER_REPOSITORY overrides), copy-pasted verbatim including the accompanying comments.

## Impact

This naming scheme (how @scope/name maps to a docker image name, plus the override env vars) is the kind of thing that changes once (e.g. a new registry naming convention); with two independent copies, build.sh and distclean.sh can drift so that 'pnpm build' produces images under a name 'pnpm distclean' no longer recognizes as its own - leaving orphaned images/containers behind after cleanup.

## Suggested fix

Extract a shared 'ionos.wordpress.docker_image_name_for_package <package.json path>' helper into a sourced include.

## Location

scripts/build.sh:219
scripts/distclean.sh:32
