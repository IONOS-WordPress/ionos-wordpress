---
# k24u
title: 'lint.sh: table-drive the 3 copy-pasted DOCKER_BUILD_FILTERS guard blocks'
status: completed
type: task
priority: low
created_at: 2026-08-17T13:39:42Z
updated_at: 2026-08-18T09:22:17Z
parent: qi52
---

scripts/lint.sh:417-428: three 'if [[ USE matches ]] && needs_docker_tools <tool>; then DOCKER_BUILD_FILTERS+=(--filter <tool>); fi' blocks differ only in the USE pattern and tool name (php/ecs-php, i18n/dennis-i18n, i18n+FIX/potrans).

## Impact

Adding a fifth linter/tool means copying a 3-line block yet again instead of adding one row to a table; a typo in one of the three '${USE[@]} =~ ...' regexes (e.g. forgetting the 'all|' prefix on a new one) silently disables docker-image building for that linter, surfacing only as a confusing "command not found" in CI.

## Suggested fix

Loop over an array of "tool:use-pattern" (or "tool:use-pattern:fix-only") pairs instead of three hand-written blocks.

## Location

scripts/lint.sh:417-428

## Summary of Changes

Replaced the 3 hand-copied 'if [[ USE matches ]] \&\& needs_docker_tools <tool>; then DOCKER_BUILD_FILTERS+=(--filter <tool>); fi' blocks in scripts/lint.sh with a table (`DOCKER_BUILD_FILTER_TOOLS`, rows of `tool:use-pattern:requires-fix`) and a single loop that reads each row and applies the same guard logic. Adding a 4th linter now means adding one row instead of a hand-copied block.

## Verification

- Directly compared old vs new logic for 3 USE/FIX combinations (USE=php FIX=no, USE=i18n FIX=yes, USE=all FIX=no) - identical DOCKER_BUILD_FILTERS output in all 3.
- `pnpm lint --use php`: passes.
- `pnpm lint-fix --use i18n`: potrans actually ran and translated missing .po entries (confirms the FIX-gated potrans row works end-to-end, not just in isolation) - reverted the resulting .po file changes since they're an unrelated incidental side effect of testing, not part of this fix.
