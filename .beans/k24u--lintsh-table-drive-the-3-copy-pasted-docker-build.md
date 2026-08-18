---
# k24u
title: 'lint.sh: table-drive the 3 copy-pasted DOCKER_BUILD_FILTERS guard blocks'
status: todo
type: task
priority: low
created_at: 2026-08-17T13:39:42Z
updated_at: 2026-08-17T13:39:42Z
parent: qi52
---

scripts/lint.sh:417-428: three 'if [[ USE matches ]] && needs_docker_tools <tool>; then DOCKER_BUILD_FILTERS+=(--filter <tool>); fi' blocks differ only in the USE pattern and tool name (php/ecs-php, i18n/dennis-i18n, i18n+FIX/potrans).

## Impact

Adding a fifth linter/tool means copying a 3-line block yet again instead of adding one row to a table; a typo in one of the three '${USE[@]} =~ ...' regexes (e.g. forgetting the 'all|' prefix on a new one) silently disables docker-image building for that linter, surfacing only as a confusing "command not found" in CI.

## Suggested fix

Loop over an array of "tool:use-pattern" (or "tool:use-pattern:fix-only") pairs instead of three hand-written blocks.

## Location

scripts/lint.sh:417-428
