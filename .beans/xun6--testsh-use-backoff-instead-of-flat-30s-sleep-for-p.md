---
# xun6
title: 'test.sh: use backoff instead of flat 30s sleep for PHP_VERSION_OVERRIDE image pull retry'
status: todo
type: task
priority: low
created_at: 2026-08-17T13:39:54Z
updated_at: 2026-08-17T13:39:54Z
parent: qi52
---

scripts/test.sh:180-188: when PHP_VERSION_OVERRIDE is set, 'docker pull' is retried up to 5 times with a flat 'sleep 30' between attempts (up to 150s of blocking wait) before falling back to a local build. This whole pull/retry path is new in this PR - test.sh previously only ever built locally.

## Impact

When the override image simply isn't published yet (a common, expected case - not a transient failure), 'pnpm test' with PHP_VERSION_OVERRIDE set now blocks up to 2.5 minutes in serial 30-second sleeps before reaching the local-build fallback it was always going to need anyway.

## Suggested fix

Use a shorter exponential/backoff schedule (e.g. 5s/10s/20s) to reach the same fallback decision faster in the common "not published yet" case, reserving longer waits for cases where a retry is actually likely to succeed.

## Location

scripts/test.sh:180-188
