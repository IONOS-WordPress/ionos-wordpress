---
# 0vw6
title: 'test.sh: unparenthesized find -o drops directory filter from first alternative'
status: completed
type: bug
priority: high
created_at: 2026-08-17T13:37:34Z
updated_at: 2026-08-18T07:56:33Z
parent: qi52
---

scripts/test.sh:342:

    find packages -path '*/wp-plugin/*/dist/*-?.?.?-php?.?' -o -path '*/wp-mu-plugin/*/dist/*-?.?.?-php?.?' -type d -name '*-?.?.?-php?.?'

Without grouping parentheses, this parses as `(-path A) -o (-path B -type d -name C)` - the `-type d -name C` filter only applies to the second (wp-mu-plugin) alternative, not the first (wp-plugin).

Verified directly: a plain file (not a directory) at packages/wp-plugin/foo/dist/foo-1.2.3-php8.1 is matched by this find even though it isn't a directory, because the wp-plugin branch has no -type d filter at all.

## Impact

A stray file under a plugin's dist tree matching the glob pattern gets treated as a "transpiled plugin directory" and fed into the next `find "$transpiled_plugin_dir" -name "*.php" ...`, which either errors on a non-directory path or silently produces nothing - masking the real per-plugin PHP-version compatibility check for that path.

## Fix

Group the alternatives explicitly so -type d -name applies to both:

    find packages \( -path '*/wp-plugin/*/dist/*-?.?.?-php?.?' -o -path '*/wp-mu-plugin/*/dist/*-?.?.?-php?.?' \) -type d -name '*-?.?.?-php?.?'

## Location

scripts/test.sh:342

## Summary of Changes

Wrapped the two `-path` alternatives in `\( ... \)` in `scripts/test.sh` so `-type d -name '*-?.?.?-php?.?'` applies to both the wp-plugin and wp-mu-plugin branches, not just the second.

## Verification

Ran the raw find both ways - both currently return the same 4 real directories in this repo state (no stray non-directory files matching the glob today), but the parenthesized form is now correct regardless. `pnpm test:php` passes end-to-end with the fix in place.
