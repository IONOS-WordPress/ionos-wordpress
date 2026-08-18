---
# 62qo
title: Dedupe --use flag parsing across test.sh/lint.sh/build.sh
status: completed
type: task
priority: low
created_at: 2026-08-17T13:39:05Z
updated_at: 2026-08-18T09:03:06Z
parent: qi52
---

The '--use' option parsing (USE+=("${2,,}") on each --use flag, then [[ ${#USE[@]} -eq 0 ]] && USE=("all") default) is hand-copied identically in scripts/test.sh:60, scripts/lint.sh:47, scripts/build.sh:59, rather than being a shared arg-parsing helper in _bootstrap.sh.

## Impact

Low risk today since the logic is tiny, but if the default-to-"all" or lowercasing behavior needs a fix (e.g. supporting comma-separated '--use a,b'), it must be patched in three places; a partial fix leaves '--use' behaving inconsistently between 'pnpm test', 'pnpm build', and 'pnpm lint', which is confusing since all three scripts present the option identically in their --help text.

## Suggested fix

Extract a shared helper (e.g. 'ionos.wordpress.parse_use_flag') in _bootstrap.sh that all three scripts call.

## Location

scripts/test.sh:60
scripts/lint.sh:47
scripts/build.sh:59

## Summary of Changes

Added two shared helpers to scripts/includes/_bootstrap.sh:
- `ionos.wordpress.parse_use_flag <value>` - lowercases and appends to the caller's global USE array.
- `ionos.wordpress.default_use_to_all` - defaults USE to ("all") if nothing was given.

Used both in scripts/test.sh, scripts/lint.sh, scripts/build.sh, replacing the identical hand-copied logic in each.

## Bug caught during verification

The naive first version of `default_use_to_all` translated `[[ ${#USE[@]} -eq 0 ]] && USE=("all")` verbatim into a function body. That broke every script under `set -e` the moment `--use` WAS given (the common case): bash specially exempts a bare `&&`/`||` list used as a **top-level statement** from errexit when it evaluates false, but that exemption does NOT extend to the identical logic as a **function's last statement** - calling the function then aborts the whole script. Caught via `pnpm lint --use php` failing silently (exit 1, no output). Fixed by rewriting it as an `if` block, which always returns 0 regardless of which branch runs.

## Verification

- `pnpm lint --use php`, `pnpm lint --use php --use i18n`, `pnpm lint` (default, no --use): all pass, USE accumulates correctly across multiple flags and defaults to 'all' when omitted.
- `pnpm test --use php`: 15/15 PHPUnit tests pass.
- `pnpm build --filter @ionos-wordpress/wpdev-caddy` (default USE): builds successfully.
