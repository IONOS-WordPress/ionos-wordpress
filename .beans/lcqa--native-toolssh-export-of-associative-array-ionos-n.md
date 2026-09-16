---
# lcqa
title: '_native-tools.sh: export of associative array IONOS_NATIVE_TOOL_PATHS is a bash no-op'
status: completed
type: bug
priority: low
created_at: 2026-08-17T13:38:25Z
updated_at: 2026-08-18T08:18:18Z
parent: qi52
---

scripts/includes/_native-tools.sh:42:

    declare -A IONOS_NATIVE_TOOL_PATHS=(...)
    export IONOS_NATIVE_TOOL_PATHS

Bash cannot export associative (or indexed) arrays into a child process's environment - only plain scalar variables and functions (via 'export -f') propagate that way. Array contents are only visible to a forked subshell of the same interpreter, not to a re-exec'd process.

Currently harmless: every caller of ionos.wordpress.native_tool() (scripts/lint.sh, build.sh, rector-fix-types.sh) uses $(...) command substitution, which forks rather than exec's a new interpreter, so the array is inherited via the fork regardless of the (ineffective) export.

## Impact

This is a latent trap, not an active bug: any future call path that re-execs a fresh bash process (e.g. via 'bash -c', or a script invoked as a separate #!/usr/bin/env bash process rather than sourced/forked) will see IONOS_NATIVE_TOOL_PATHS as empty and ionos.wordpress.native_tool will fail with a misleading "unknown tool" error for valid tool names, with no obvious clue that the root cause is a scoping issue rather than a missing registration.

## Fix

Remove the misleading 'export' (it asserts a guarantee bash can't provide), and if cross-process propagation is ever actually needed, pass the tool list a different way (e.g. re-source _native-tools.sh in the child, or serialize to an env var as a delimited string).

## Location

scripts/includes/_native-tools.sh:42

## Summary of Changes

Removed the ineffective `export IONOS_NATIVE_TOOL_PATHS` line and replaced it with a comment explaining why (bash can't export associative arrays into a re-exec'd child; every real caller reaches this file via `source`, which inherits it through the fork regardless).

## Verification

- `ionos.wordpress.native_tool nonexistent-tool`: correctly reports 'unknown tool ... expected one of: ecs-php rector-php potrans dennis-i18n' - confirms the array's contents are intact without the export.
- `pnpm lint`: PHP/WordPress/i18n/CSS/JS linting (which exercises ecs-php, rector-php, dennis-i18n via this dispatch) all pass.
