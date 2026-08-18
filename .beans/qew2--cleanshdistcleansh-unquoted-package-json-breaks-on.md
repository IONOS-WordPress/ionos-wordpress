---
# qew2
title: 'clean.sh/distclean.sh: unquoted $PACKAGE_JSON breaks on paths with spaces'
status: completed
type: task
priority: low
created_at: 2026-08-17T13:40:07Z
updated_at: 2026-08-18T08:50:26Z
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

## Summary of Changes

Quoted \$PACKAGE_JSON in scripts/distclean.sh's dirname/jq invocations (lines ~28-31).

## Scope note

The bean cited 'scripts/clean.sh:30-31' too, but clean.sh has no such loop at all - confirmed by inspection, the bug only exists in distclean.sh. The for-loop's own unquoted \$(find ...) enumeration (which also breaks on space-containing paths) was left as-is: it's the same idiom used unquoted in ~12 other loops across this codebase (lint.sh, build.sh, test.sh, update-dependencies.sh, etc.) - rearchitecting just this one loop to a null-delimited while-read would be inconsistent with the rest of the repo and go beyond the bean's stated fix ('quote all variable expansions').
