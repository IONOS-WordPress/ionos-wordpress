---
# ykhh
title: Cache the pnpm store in CI
status: in-progress
type: task
created_at: 2026-08-06T08:54:19Z
updated_at: 2026-08-06T08:54:19Z
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

- [ ] Add an `actions/cache` step on `.pnpm-store` keyed on
      `hashFiles('pnpm-lock.yaml')` (with a restore-key prefix) before the
      install step, in both the `lint` and `build and test` jobs
- [ ] Measure the next run and compare against the 145s / 97s baseline
- [ ] Keep only if it is a net win - revert otherwise

## Risk to verify, not assume

The local dev store is 3.3GB. A fresh CI store will be smaller (no
accumulated historical versions) but could still be 1-2GB. Restoring and
saving a cache that size can cost more wall clock than the ~30s install it
is meant to replace. This is why the last todo exists: the change only stays
if the measurement says it helps.

Secondary concern: with a `restore-keys` fallback the store accumulates
across lockfile generations. GitHub evicts LRU at the 10GB repo cache limit,
so it self-limits, but the entry size is worth watching.
