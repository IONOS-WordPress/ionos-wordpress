---
# jfhd
title: Dedupe embedded --help printing idiom across 7 scripts
status: completed
type: task
priority: low
created_at: 2026-08-17T13:39:17Z
updated_at: 2026-08-18T08:54:38Z
parent: qi52
---

The "print embedded help text and exit" idiom (printf "$(sed -e '1,/^###help-message/d' "$0")\n"; exit) is duplicated verbatim across 7 scripts: build.sh:23, lint.sh:21, purge-registry.sh:36, test.sh:28, stretch-extra.sh:33, update-dependencies.sh:95, watch.sh:25 - despite _bootstrap.sh existing specifically to hold shared behavior and being sourced by every one of these scripts.

## Impact

If the help-marker convention changes (different marker string, wanting to pipe through a pager, or handling scripts invoked via symlink where "$0" resolution differs), every one of the 7 copies needs an identical edit; missing one leaves that script's --help silently broken or inconsistent with the others.

## Suggested fix

Extract a one-line helper (e.g. 'ionos.wordpress.print_help "$0"') into _bootstrap.sh.

## Location

scripts/build.sh:23, lint.sh:21, purge-registry.sh:36, test.sh:28, stretch-extra.sh:33, update-dependencies.sh:95, watch.sh:25

## Summary of Changes

Added a shared `ionos.wordpress.print_help()` helper to `scripts/includes/_bootstrap.sh` (prints everything after the '###help-message' marker in the given script and exits) and replaced the duplicated 2-3 line idiom in all 7 call sites: build.sh, lint.sh, purge-registry.sh, test.sh, stretch-extra.sh, update-dependencies.sh, watch.sh.

## Verification

Ran `--help` on all 7 scripts directly - each prints its own embedded help text correctly through the shared helper.
