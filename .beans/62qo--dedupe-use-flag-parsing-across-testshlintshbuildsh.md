---
# 62qo
title: Dedupe --use flag parsing across test.sh/lint.sh/build.sh
status: todo
type: task
priority: low
created_at: 2026-08-17T13:39:05Z
updated_at: 2026-08-17T13:39:05Z
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
