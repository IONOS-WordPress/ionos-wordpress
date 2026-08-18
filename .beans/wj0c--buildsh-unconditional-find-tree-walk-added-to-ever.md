---
# wj0c
title: "build.sh: unconditional find tree-walk added to every package's up-to-date check"
status: todo
type: task
priority: low
created_at: 2026-08-17T13:39:55Z
updated_at: 2026-08-17T13:39:55Z
parent: qi52
---

scripts/build.sh's new ionos.wordpress.is_workspace_package_up_to_date (called from build_workspace_package and build_workspace_package_docker) does a 'find' traversal of the entire package directory (pruning dist/node_modules/.git) to look for any file newer than build-info, for every package on every 'pnpm build' invocation.

Previously (develop:scripts/build.sh:154), a docker package's "skip build" check was just two O(1) checks: '[[ -f build-info ]] && docker image inspect ...'.

## Impact

Every 'pnpm build' invocation - including ones where nothing changed and the old check would already have short-circuited quickly - now pays for a full directory find scan per workspace package before the docker-image-exists shortcut is even reached. Adds noticeable overhead in monorepos with many/large packages, on every build, not just when a rebuild is actually warranted.

## Suggested fix

Consider a cheaper staleness check (e.g. relying on git/mtime metadata already available, or checking only a curated set of "source changed" paths) before falling back to a full tree walk, or gate the tree walk behind a quick negative check.

## Location

scripts/build.sh (ionos.wordpress.is_workspace_package_up_to_date)
