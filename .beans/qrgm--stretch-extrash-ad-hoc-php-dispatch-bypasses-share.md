---
# qrgm
title: 'stretch-extra.sh: ad-hoc PHP dispatch bypasses shared native-tools mechanism'
status: completed
type: task
priority: low
created_at: 2026-08-17T13:38:53Z
updated_at: 2026-08-18T09:33:26Z
parent: qi52
---

ionos.wordpress.stretch-extra.run_php() in scripts/stretch-extra.sh implements its own native-vs-docker dispatch ('command -v php >/dev/null 2>&1') instead of registering 'php' in _native-tools.sh's IONOS_NATIVE_TOOL_PATHS / calling ionos.wordpress.native_tool.

_native-tools.sh's probing logic explicitly documents why it avoids env-sniffing and instead probes for a specific installed binary path, with IONOS_WP_FORCE_DOCKER=1 as an escape hatch. This function bypasses all of that: it uses whatever 'php' is first on $PATH (which may be the wrong version), ignores IONOS_WP_FORCE_DOCKER, and hardcodes 'php:8.3-cli' as its docker fallback image tag inline.

## Impact

If the shared version/escape-hatch logic changes, this script silently doesn't pick it up. A PHP version bump project-wide requires hunting down and fixing this stray '8.3' literal separately, disconnected from image-matrix.json.

## Suggested fix

Register php in IONOS_NATIVE_TOOL_PATHS (or a version-aware equivalent) and route through the shared dispatch instead of a bespoke 'command -v' check.

## Location

scripts/stretch-extra.sh, ionos.wordpress.stretch-extra.run_php() (~line 24-30)

## Scope decision

The bean's literal suggestion (register 'php' in IONOS_NATIVE_TOOL_PATHS) doesn't fit that registry's actual shape: it assumes a per-tool COMPOSER_HOME-isolated install under `$IONOS_NATIVE_TOOLS_PREFIX/<tool>/vendor/bin/<binary>` (ecs-php/rector-php/potrans/dennis-i18n) - php itself is just the system interpreter, not such an install. Discussed with the user and scoped this down to the two gaps that were genuinely bugs, without forcing php through an abstraction it doesn't fit:

## Summary of Changes

- Made `ionos.wordpress.stretch-extra.run_php()` respect the `IONOS_WP_FORCE_DOCKER=1` escape hatch, matching every other native-tool-or-docker call site in the codebase (it previously always used whatever `php` was first on PATH, with no way to force-verify the docker fallback by hand).
- Documented that the `php:8.3-cli` docker tag is arbitrary/reproducibility-only, not a real compatibility constraint (the config file has no version-sensitive syntax) - so a future PHP-version bump elsewhere in the project doesn't need to touch this literal.

## Verification

- Docker fallback path (no native php available, this host): `ionos.wordpress.stretch-extra.run_php -r 'echo PHP_VERSION;'` -> 8.3.33.
- Inside the dev container (native php available): confirmed it uses native php normally, and `IONOS_WP_FORCE_DOCKER=1` correctly forces the docker branch instead.
- `pnpm stretch-extra --check`: ran end-to-end through the real command path (exercises run_php via the config-interpretation step), correctly reported real plugin/theme version differences.
