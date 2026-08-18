---
# 9p16
title: Dedupe CORE_DIR path construction between start.sh and test.sh
status: todo
type: task
priority: low
created_at: 2026-08-17T13:39:17Z
updated_at: 2026-08-17T13:39:17Z
parent: qi52
---

scripts/start.sh:22-23 and scripts/test.sh:140 both independently compute:

    CORE_DIR="${MNT_HOME}/wordpress-core/$(ionos.wordpress.wordpress_version_dir "$WORDPRESS_VERSION")"

reusing the _docker-mounts.sh helper for the version-dir part, but re-deriving the "wordpress-core/<version>" path concatenation by hand in both call sites rather than exposing it as a single 'ionos.wordpress.core_dir' function in _docker-mounts.sh (which already owns the core-dir/stack-dir mounting concept via ionos.wordpress.build_wp_volume_args).

## Impact

Minor since it's one string concatenation, but if the core cache's directory layout under MNT_HOME ever changes (e.g. adding a namespace segment), both start.sh and test.sh need the identical edit. test.sh already shows the pattern is prone to copy-drift: it duplicates 'readonly VERSION_DIR'/'readonly CORE_DIR' declarations that mirror start.sh's as separate un-shared readonly locals rather than one function call.

## Suggested fix

Add 'ionos.wordpress.core_dir <wordpress_version>' to _docker-mounts.sh and have both scripts call it.

## Location

scripts/start.sh:22-23
scripts/test.sh:140
