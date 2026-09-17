---
# ajy4
title: '_docker-mounts.sh: multiple stale zips can corrupt TEST_PRODUCTION mount path'
status: completed
type: bug
priority: normal
created_at: 2026-08-17T13:38:25Z
updated_at: 2026-08-18T08:03:45Z
parent: qi52
---

In the TEST_PRODUCTION=true branch of scripts/includes/_docker-mounts.sh (~lines 84-93/102-112):

    zip_archive=$(find packages/wp-plugin/${PLUGIN} -regex ".*\.zip" -printf '%f\n' 2>/dev/null || echo '')
    ...
    dist_package_root="packages/wp-plugin/${PLUGIN}/dist/${zip_archive%.zip}/${PLUGIN}"

If more than one '.zip' exists under a plugin's tree (e.g. a stale zip left from a previous version bump that wasn't cleaned before rebuilding), 'zip_archive' becomes a multi-line string. '${zip_archive%.zip}' only strips the suffix off the trailing line, so 'dist_package_root' ends up containing an embedded newline and a garbled path.

## Impact

After a version bump where an old zip under packages/wp-plugin/<plugin>/dist/*.zip wasn't removed, running 'TEST_PRODUCTION=true pnpm test' produces two zip filenames concatenated across lines, breaking the resulting --volume mount argument for that plugin (wrong path, or docker invocation fails outright).

## Fix

Ensure exactly one match (e.g. 'find ... | sort | tail -1' with an explicit error if zero/multiple matches, or clean stale zips before this step runs).

## Location

scripts/includes/_docker-mounts.sh (TEST_PRODUCTION=true branch)

## Summary of Changes

Added a shared `ionos.wordpress.find_single_zip_archive()` helper in `scripts/includes/_docker-mounts.sh` that requires exactly one match, erroring out (exit 1, clear message) on zero or multiple - instead of silently returning a multi-line result. Used it at all three affected call sites (the bean only mentioned the wp-plugin one, but the identical bug existed in the wp-theme and wp-mu-plugin `TEST_PRODUCTION=true` loops too).

## Verification

- Simulated all three cases directly: single zip -\> returns it; two zips -\> exits 1 with 'multiple build zip archives found ... remove the stale one(s) and rebuild'; zero zips -\> exits 1 with 'no build zip archive found ... run pnpm build first'.
- `TEST_PRODUCTION=true pnpm test:php`: passes end-to-end (15/15 PHPUnit) with the real single-zip case.
