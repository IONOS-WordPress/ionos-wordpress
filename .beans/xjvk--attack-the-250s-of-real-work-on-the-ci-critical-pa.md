---
# xjvk
title: Attack the 250s of real work on the CI critical path (build 120s + test 130s)
status: todo
type: task
priority: high
created_at: 2026-08-07T08:56:22Z
updated_at: 2026-08-07T08:56:29Z
parent: 1qc9
blocked_by:
  - ytd4
---

Everything else in this epic fights over ~100s of overhead. This is where **65% of the critical
path** actually is, and no amount of image layout touches it.

Measured on run `31161602566`, `build and test` job (384s total):

| segment      | time  |
| ------------ | ----- |
| `pnpm build` | ~120s |
| `pnpm test`  | ~130s |

## Two independent levers

**1. Move the e2e shard split from within one runner to a job matrix.**
`scripts/test.sh` runs `E2E_SHARDS=3` concurrently _inside_ a single 4-vCPU `ubuntu-latest`. Each
shard costs a WordPress container (mariadb + php-fpm + caddy) plus a chromium, so the three shards
contend for 4 vCPUs - the workflow comment on the test step says as much ("pushing this higher
trades parallelism against CPU contention. re-measure before changing it"). Splitting across
_jobs_ gives each shard its own 4 vCPUs instead. Cost: each matrix job repays the per-job setup
(checkout, caches, devcontainer materialization ~56s today, ~15s after `ytd4`), so this pairs
naturally with `ytd4` and is much more attractive once that lands.

**2. Larger runners.** 4 -> 8/16 vCPU cuts the parallel parts of both build and test close to
linearly, for near-zero engineering effort. Often the cheapest real win available; needs a
cost decision.

## Investigate first

- [ ] Profile what dominates the ~120s `pnpm build` (rector transpilation across PHP targets? wp-scripts bundling?) - it may have its own cheap win
- [ ] Measure per-shard e2e wall clock to size the matrix payoff
- [ ] Price out larger runners against the measured saving
- [ ] Sequence after `ytd4` - the matrix multiplies whatever the per-job setup cost is
