---
# 033f
title: 'build.sh: composer/wp-cli bypass native-tool dispatch, hardcode floating/stale version tags'
status: completed
type: task
priority: normal
created_at: 2026-08-17T13:38:40Z
updated_at: 2026-08-18T08:35:41Z
parent: qi52
---

scripts/build.sh's composer invocation ('docker run ... composer:latest install ...', ~line 250) and wp-cli invocation ('docker run ... wordpress:cli-php8.3 wp $@', ~line 327) always shell out to docker, unlike ecs-php/rector-php/potrans/dennis-i18n which use the native-tool dispatch in _native-tools.sh established elsewhere in this PR.

Both also hardcode version/tag literals disconnected from the project's actual PHP-version matrix (packages/docker/wordpress-alpine/image-matrix.json, used by scripts/test.sh):

- 'composer:latest' is a floating tag
- 'wordpress:cli-php8.3' hardcodes PHP 8.3

## Impact

- composer:latest can change lockfile-resolution behavior non-deterministically between CI runs and developer machines, with no single pin point to bump (unlike native tools, which install from a committed composer.lock).
- wordpress:cli-php8.3's embedded PHP version has to be found and hand-updated separately whenever the project's minimum PHP version changes, with nothing to catch a missed update.
- Both invocations always pay the docker-in-docker round trip the native-tools mechanism exists specifically to avoid (per build.sh's own header comment).

## Suggested fix

Register composer and wp-cli in _native-tools.sh's IONOS_NATIVE_TOOL_PATHS (assuming they're available natively in the devcontainer/CI image) so build.sh can use the same dispatch pattern as the other tools; pin composer's tag to a specific version instead of :latest.

## Location

scripts/build.sh (composer invocation ~line 250, wp-cli invocation ~line 327)
scripts/includes/_native-tools.sh (IONOS_NATIVE_TOOL_PATHS)

## Summary of Changes

- composer: now dispatches to the native `composer` binary when available (checked via `command -v composer`, respecting the existing `IONOS_WP_FORCE_DOCKER=1` escape hatch) - confirmed it genuinely IS present in the devcontainer/CI image (ships with the base image `mcr.microsoft.com/devcontainers/php:8.4-bookworm`; `.devcontainer/Dockerfile` already calls it directly via `composer global install` with no prior install step). Falls back to docker only when composer truly isn't on PATH, now pinned to `composer:2.10.2` instead of the floating `:latest`.
- wp-cli: left docker-only, since (unlike composer) it is NOT installed anywhere in `.devcontainer/Dockerfile` today - registering it as a native tool would require actually adding it to the devcontainer image, a bigger/riskier change (affects every developer's devcontainer, needs an image rebuild/pull) than a script-only fix. Discussed scope with the user and decided to defer that. Instead, added a comment on the `wordpress:cli-php8.3` tag explaining it must track AGENTS.md's stated minimum supported PHP version (currently 8.3, which it already correctly matches) so a future bump isn't missed silently.

## Verification

- `pnpm build --filter ecs-php` (composer isn't installed on this host, so this exercises the pinned docker fallback): pulled and used `composer:2.10.2` successfully, image built.
- `pnpm build` (full): passes end-to-end, including the wp-cli-driven localization-file generation step in `ionos-core`/`ionos-essentials`.
