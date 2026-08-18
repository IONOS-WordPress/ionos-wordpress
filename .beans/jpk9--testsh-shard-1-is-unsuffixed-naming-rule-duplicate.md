---
# jpk9
title: "test.sh: 'shard 1 is unsuffixed' naming rule duplicated in 3 places"
status: completed
type: task
priority: low
created_at: 2026-08-17T13:39:29Z
updated_at: 2026-08-18T09:39:21Z
parent: qi52
---

ionos.wordpress.test_container_name() (scripts/test.sh:122) and ionos.wordpress.test_stack_dir() (line 128) both do [[ "$1" == '1' ]] && echo '<bare>' || echo '<bare>-$1', and the e2e loop (lines 398-399) independently recomputes the same rule as SHARD_SUFFIX.

## Impact

The "is this shard 1" naming rule is defined three separate times; a future change (e.g. switching to 0-based shard numbering, or changing the separator from '-' to '_') requires hunting down and editing all three in lockstep - easy to update two and miss the third, producing a container name that matches but an artifacts-path suffix that doesn't.

## Suggested fix

One helper 'ionos.wordpress.shard_suffix "$shard"' (empty for shard 1, "-$shard" otherwise) that all three call sites and SHARD_SUFFIX build from.

## Location

scripts/test.sh:122 (test_container_name), 128 (test_stack_dir), 398-399 (SHARD_SUFFIX)

## Scope correction

The bean's diagnosis was subtly wrong: it claimed the e2e loop's `SHARD_SUFFIX` recomputes the same rule as `test_container_name`/`test_stack_dir`, but it actually uses a DIFFERENT condition - `$E2E_SHARDS == '1'` (is sharding enabled at all) rather than `$1 == '1'` (is this particular shard number 1). They diverge at E2E_SHARDS=3, SHARD=1: `test_container_name(1)` stays unsuffixed, but `SHARD_SUFFIX` becomes `-1` - deliberately, so concurrently-running shards' artifacts/storage-state paths stay distinct even for shard 1. Unifying all three into one rule (as suggested) would have been a real behavior regression.

## Summary of Changes

Extracted only the two genuinely-identical helpers: added `ionos.wordpress.shard_name_suffix <shard>` (empty for shard 1, "-$shard" otherwise) and used it in both `test_container_name()` and `test_stack_dir()`. Left the e2e loop's `SHARD_SUFFIX` as its own separate, differently-conditioned computation, with a comment explaining why it's intentionally not unified.

## Verification

- Directly compared old vs new naming for shards 1/2/3 - identical output.
- `pnpm test:php`: 15/15 PHPUnit tests pass (shard 1 path).
- `E2E_SHARDS=2 pnpm test:e2e security.spec.js`: 3/3 passed, confirming sharded container naming still works end-to-end.
