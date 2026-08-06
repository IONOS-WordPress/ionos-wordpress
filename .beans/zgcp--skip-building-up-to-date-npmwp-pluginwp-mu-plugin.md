---
# zgcp
title: Skip building up-to-date npm/wp-plugin/wp-mu-plugin workspace packages
status: completed
type: feature
priority: normal
created_at: 2026-08-06T13:41:27Z
updated_at: 2026-08-06T13:51:02Z
---

'pnpm run start' (via 'pnpm build') currently rebuilds every workspace package unconditionally, even when nothing changed since the last build. Only the docker package type has an existing skip-if-up-to-date check (build-info exists + matching image already present locally).

Generalize that pattern to npm, wp-plugin and wp-mu-plugin packages using the already-written build-info file (its mtime marks the last successful build) as the reference point.

## Design (agreed with user)

Add a staleness check in scripts/build.sh (ionos.wordpress.build_workspace_package), run for type npm/wp-plugin/wp-mu-plugin right before dispatching to the type-specific build function or 'pnpm build':

1. --force set -> always stale (now applies globally, not just docker).
2. No build-info file -> stale.
3. Directory freshness: any file in the package dir newer than build-info, excluding dist/, build-info, node_modules/, .git/, languages/_.po, languages/_.pot -> stale.
4. Dependency freshness: for each workspace:* entry in dependencies/devDependencies (reuse parsing from ionos.wordpress.get_workspace_package_dependency_order), if that dependency's build-info is newer than this package's build-info -> stale. Since packages build in topological order, a rebuilt dependency's build-info mtime updates and correctly cascades to dependents on the same build.sh run.
5. node_modules proxy (avoid slow full-tree scan under pnpm's hoisted/symlinked store): if root pnpm-lock.yaml or the package's own package.json is newer than build-info -> stale.
6. Otherwise up to date -> skip build, log an info message (mirrors the existing docker skip-log behavior).

## Scope

- Applies to: npm, wp-plugin, wp-mu-plugin package types.
- Does not change: docker package type (keeps its own existing skip check).
- Packages with a custom package.json 'build' script (dispatched via 'pnpm --filter ... build' instead of the type-specific function) are currently NOT covered by build-info at all -> needs a decision during implementation on whether/how to wrap those too.

## Tasks

- [x] Implement ionos.wordpress.is_workspace_package_up_to_date (or equivalent) in scripts/build.sh
- [x] Wire it into ionos.wordpress.build_workspace_package for npm/wp-plugin/wp-mu-plugin
- [x] Make --force bypass it (and continue to bypass the docker check)
- [x] Add/adjust skip-log message consistent with the docker skip message
- [x] Update docs/2-build.md to document the new incremental build behavior
- [x] Manually verify: pnpm build (no changes) skips everything; touching a src file rebuilds only that plugin; touching essentials cascades a rebuild to stretch-extra

## Summary of Changes

### scripts/build.sh

- Added global associative arrays WP_PATH_BY_NAME / WP_NAME_BY_PATH / WP_DEPENDENCIES_BY_NAME plus `ionos.wordpress.index_workspace_packages` to index every matched workspace package and its `workspace:*` dependencies. Called directly (not via command substitution) before the build loop so the populated globals survive.
- Added `ionos.wordpress.is_workspace_package_up_to_date <path>` returning 0 when the package can be skipped. Outdated when: `--force` is set; no `build-info` exists; a file in the package dir is newer than `build-info` (pruning `dist/`, `build-info`, `node_modules/`, `.git/`, `*.po`, `*.pot`); the package's own `package.json` or the root `pnpm-lock.yaml` is newer than `build-info`; or a `workspace:*` dependency's `build-info` is newer than its own.
- Wired the check into `ionos.wordpress.build_workspace_package`, skipping with a warn log for all non-docker types. Docker keeps its own pre-existing image-exists check.
- Updated the `--force` help text to describe the generalized behavior.

### docs/2-build.md

Documented the incremental build rules and how `--force` overrides them.

## Verification

Unit-level (functions sourced in isolation, all four scenarios correct) plus real end-to-end runs:

- `pnpm build` with no changes: all four wp/mu packages skipped; idempotent across repeats.
- Touching a stretch-extra source file: only stretch-extra rebuilt; next run skipped it again (confirms its `postpack` script does not falsely invalidate `build-info`).
- Touching an ionos-essentials source file then running full `pnpm build`: essentials rebuilt and both dependents (stretch-extra, ionos-core) correctly cascaded, while the unrelated ionos-wpdev-caddy stayed skipped.
- `--force` marks every package outdated.

Staleness check cost is ~75ms for all four packages.

## Note on the deferred scope item

The open question about packages with a custom `package.json` `build` script turned out to be moot in practice: none of the current npm/wp-plugin/wp-mu-plugin packages define one, so all of them go through the type-specific build functions that write `build-info`. The check is applied uniformly to all non-docker types regardless of dispatch path; a package whose custom build script never writes `build-info` simply always rebuilds, which is the safe fallback.
