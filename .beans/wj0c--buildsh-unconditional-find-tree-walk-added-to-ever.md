---
# wj0c
title: 'build.sh: unconditional find tree-walk added to every package''s up-to-date check'
status: completed
type: task
priority: low
created_at: 2026-08-17T13:39:55Z
updated_at: 2026-08-18T09:50:56Z
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

## Summary

No code change - confirmed the overhead is empirically negligible at this repo's current scale, and any 'cheaper' alternative (git-diff-based, directory-mtime-based, checksum-cache-based) trades a measured non-problem for a real risk: silently misjudging staleness and serving a stale build is a correctness regression, categorically worse than a few milliseconds of find overhead.

## Verification

Measured the exact find command from ionos.wordpress.is_workspace_package_up_to_date directly against all 4 workspace packages (including stretch-extra, the largest at 97MB after excluding dist/node_modules):
- Best case (an early-modified file triggers -quit immediately): ~22ms combined across all 4 packages.
- True worst case (build-info newer than every file, forcing a full traversal with no early exit - the scenario the bean specifically worried about): ~23ms and ~51ms for the two largest packages individually, still negligible in aggregate.

Discussed with the user: closed as no-fix-needed rather than adding speculative complexity for a problem that doesn't empirically exist yet, following the same evidence-based-close pattern used for zb17/x5qc earlier in this epic.
