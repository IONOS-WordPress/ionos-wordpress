---
# ykhh
title: Cache the pnpm store in CI
status: completed
type: task
priority: normal
created_at: 2026-08-06T08:54:19Z
updated_at: 2026-08-06T09:10:32Z
---

Follow-up to [[j3pl]]. After that bean the `lint` job is 145s, of which the
`install and pull prebuilt lint images` step is 97s: ~50s devcontainer boot,
~30s `pnpm install --frozen-lockfile`, ~12s image pulls. The `build and test`
job pays the same ~30s install.

`./.npmrc` sets `store-dir=.pnpm-store`, so the pnpm store lives at
`$GITHUB_WORKSPACE/.pnpm-store` - inside the bind mount devcontainers/ci
maps into the container, therefore reachable by a host-side `actions/cache`
step with no devcontainer changes at all.

## Todo

- [x] Add an `actions/cache` step on `.pnpm-store` keyed on
      `hashFiles('pnpm-lock.yaml')` (with a restore-key prefix) before the
      install step, in both the `lint` and `build and test` jobs
- [x] Measure the next run and compare against the 145s / 97s baseline
- [x] Keep only if it is a net win - revert otherwise

## Risk to verify, not assume

The local dev store is 3.3GB. A fresh CI store will be smaller (no
accumulated historical versions) but could still be 1-2GB. Restoring and
saving a cache that size can cost more wall clock than the ~30s install it
is meant to replace. This is why the last todo exists: the change only stays
if the measurement says it helps.

Secondary concern: with a `restore-keys` fallback the store accumulates
across lockfile generations. GitHub evicts LRU at the 10GB repo cache limit,
so it self-limits, but the entry size is worth watching.

## Summary of Changes

Added `.github/shared/actions/pnpm-store-cache` (a thin `actions/cache`
wrapper on `.pnpm-store`, keyed on `hashFiles('pnpm-lock.yaml')` with a
`Linux-pnpm-store-` restore-key prefix) and wired it into both the `lint`
and `build and test` jobs, ahead of their install steps.

No devcontainer changes were needed: `./.npmrc` sets
`store-dir=.pnpm-store`, so the store already lives inside the workspace
that devcontainers/ci bind-mounts into the container.

## Measured

Cache entry is **619MB**. All figures from the `lint` job.

| run      | cache           | restore | install + pulls | job total |
| -------- | --------------- | ------- | --------------- | --------- |
| e4a4bc99 | none            | -       | 97s             | 145s      |
| 1f2b0e9b | miss (+9s save) | -       | 88s             | 136s      |
| rerun 1  | hit             | 13s     | 73s             | 134s      |
| rerun 2  | hit             | 10s     | 63s             | 115s      |

Install goes from ~92s to ~68s (-24s); the restore costs 10-13s of that
back. **Net ~12-16s per job**, consistent in direction across both pairs.

Kept. Marginal enough that it is worth re-checking if the store grows a
lot - at some size the restore will overtake the download it saves.

## Combined effect with j3pl

The lint job started this sequence at 181s and now averages ~125s on a
warm cache - roughly a 30% reduction. The `build and test` job gets the
same install saving for free.

What is left in the lint job is dominated by the ~50s first
`devcontainer up`, which no amount of caching inside the job can avoid.
