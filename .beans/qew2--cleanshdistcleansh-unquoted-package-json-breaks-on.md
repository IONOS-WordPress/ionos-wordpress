---
# qew2
title: 'clean.sh/distclean.sh: unquoted $PACKAGE_JSON breaks on paths with spaces'
status: todo
type: task
priority: low
created_at: 2026-08-17T13:40:07Z
updated_at: 2026-08-17T13:40:07Z
parent: qi52
---

scripts/clean.sh:30-31: PACKAGE_NAME=$(jq -r '.name' $PACKAGE_JSON) and scripts/distclean.sh similarly leave $PACKAGE_JSON/loop variables unquoted when iterating 'find packages/docker -maxdepth 2 -mindepth 2 -name "package.json"'.

## Impact

A packages/docker/<name>/package.json path containing a space (or the workspace checked out under a path with spaces) breaks word-splitting in the for loop and in the jq/dirname invocations, causing the cleanup loop to silently skip or mis-target packages rather than erroring clearly.

## Fix

Quote all variable expansions in these loops ("$PACKAGE_JSON", etc.).

## Location

scripts/clean.sh:30-31
scripts/distclean.sh (equivalent loop)
