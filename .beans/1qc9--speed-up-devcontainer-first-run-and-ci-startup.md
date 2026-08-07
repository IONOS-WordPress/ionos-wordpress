---
# 1qc9
title: Speed up devcontainer first-run and CI startup
status: todo
type: epic
created_at: 2026-08-07T08:55:14Z
updated_at: 2026-08-07T08:55:14Z
---

Umbrella for the "make devcontainer / CI much faster" investigation.

## Origin

Question asked: can we merge the `packages/docker/*` workspace images, the pnpm store and a
pre-seeded chromium into the devcontainer image, so that pulling/building the devcontainer
alone yields a ready-to-use environment? Would that speed up CI?

## Measurement baseline

Profiled integration run `31162274348`'s predecessor `31161602566` (all caches warm, devcontainer
image rebuilt) from the log timestamps:

|                                       |                              |
| ------------------------------------- | ---------------------------- |
| total wall clock                      | 557s                         |
| `devcontainer` job (gates build+lint) | 165s (100s build + 51s push) |
| `build and test` (critical path)      | 384s                         |
| `lint` (parallel, off critical path)  | 176s                         |

`build and test` (384s) breaks down as:

| segment                                            | time  |
| -------------------------------------------------- | ----- |
| checkout + pnpm-store cache + playwright cache     | 30s   |
| first devcontainer materialization                 | ~56s  |
| `pnpm install` + wordpress-alpine/rector-php pulls | ~24s  |
| devcontainer re-entry for steps 2-4                | ~15s  |
| `pnpm build`                                       | ~120s |
| `pnpm test`                                        | ~130s |

The ~56s materialization is `devcontainers/ci` resolving both features twice and then having
buildx pull the whole image through the `--cache-from` cache-import path (a single ~30s stall on
the docker-in-docker feature stage, `#27`). It is paid once per job, so twice per run.

## Conclusions from the investigation

**The original premise does not hold for CI.** Baking artifacts into the devcontainer image is
roughly net-zero to net-negative there, because the devcontainer image is materialized per job -
exactly like the caches it would replace - and the buildx cache-import path is the _slower_ of the
two channels. `actions/cache` restores the pnpm store in 17s and the playwright browsers in 7s.
Trading those for +1.1GB of tool images and +1GB of pnpm store on an image pulled twice per run
loses.

**Baking the pnpm store is rejected outright**, on two counts:

- `.npmrc` sets `store-dir=.pnpm-store`, inside the bind-mounted workspace, so a store baked at
  `$HOME` would simply not be seen. Moving it out breaks `.github/shared/actions/pnpm-store-cache`.
- A baked store must track `pnpm-lock.yaml`, which changes far more often than `.devcontainer/`.
  The image tag is derived from `.devcontainer`'s git history, so the tag scheme would have to
  include the lockfile -> a ~150s devcontainer rebuild+push on every dependency bump, and a
  multi-GB re-pull for every developer.

**It does make sense for developer onboarding.** A fresh devcontainer today needs `pnpm install`
(3.3GB store), `pnpm build` (builds 5 docker images locally) and a ~300MB chromium download before
anything works. That is the win worth chasing - measured in dev-minutes, not CI-minutes.

**The CI win is elsewhere**, and the children below split it accordingly.
