---
# 6up7
title: 'build.sh: three lock-step global arrays should be one per-package record'
status: todo
type: task
priority: low
created_at: 2026-08-17T13:39:29Z
updated_at: 2026-08-17T13:39:29Z
parent: qi52
---

scripts/build.sh:128-206, ionos.wordpress.index_workspace_packages populates three global associative arrays (WP_PATH_BY_NAME, WP_NAME_BY_PATH, WP_DEPENDENCIES_BY_NAME) that only ever move together, keyed off the same package list, and are always read together in ionos.wordpress.is_workspace_package_up_to_date (path->name to look up name->deps, then name->path again to resolve each dependency's build-info).

## Impact

Three global arrays that must stay index-consistent invite drift: a future edit to dependency resolution (e.g. adding transitive deps) that only refreshes one of the three maps would silently break the up-to-date check for renamed/moved packages.

## Suggested fix

Collapse into a single 'WP_PACKAGE_INFO_BY_PATH["$path"]="$name $deps"' (or a jq-built lookup keyed one way) - one record per package instead of three arrays kept in lockstep.

## Location

scripts/build.sh:128-206 (ionos.wordpress.index_workspace_packages), ionos.wordpress.is_workspace_package_up_to_date
