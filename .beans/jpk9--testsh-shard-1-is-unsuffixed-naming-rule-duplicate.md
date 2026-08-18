---
# jpk9
title: "test.sh: 'shard 1 is unsuffixed' naming rule duplicated in 3 places"
status: todo
type: task
priority: low
created_at: 2026-08-17T13:39:29Z
updated_at: 2026-08-17T13:39:29Z
parent: qi52
---

ionos.wordpress.test_container_name() (scripts/test.sh:122) and ionos.wordpress.test_stack_dir() (line 128) both do [[ "$1" == '1' ]] && echo '<bare>' || echo '<bare>-$1', and the e2e loop (lines 398-399) independently recomputes the same rule as SHARD_SUFFIX.

## Impact

The "is this shard 1" naming rule is defined three separate times; a future change (e.g. switching to 0-based shard numbering, or changing the separator from '-' to '_') requires hunting down and editing all three in lockstep - easy to update two and miss the third, producing a container name that matches but an artifacts-path suffix that doesn't.

## Suggested fix

One helper 'ionos.wordpress.shard_suffix "$shard"' (empty for shard 1, "-$shard" otherwise) that all three call sites and SHARD_SUFFIX build from.

## Location

scripts/test.sh:122 (test_container_name), 128 (test_stack_dir), 398-399 (SHARD_SUFFIX)
