---
# ytd4
title: Replace the devcontainer buildx cache-import with a prebuilt-image pull (~75s/run)
status: todo
type: task
priority: high
created_at: 2026-08-07T08:56:08Z
updated_at: 2026-08-07T08:56:08Z
parent: 1qc9
---

The single biggest cheap CI win found in the investigation. Independent of the other children.

## Problem

`.github/shared/actions/devcontainer-shell-run` passes `cacheFrom: <prebuilt image>` to
`devcontainers/ci`. That does **not** `docker pull` the prebuilt image - it runs a full
`docker buildx build --cache-from`, which:

1. resolves both devcontainer features (`docker-in-docker:2`, `claude-code:1`) from their registries,
2. imports the cache manifest,
3. has buildx pull the image layers through cache resolution - a single ~30s stall on the
   docker-in-docker feature stage (`#27`),
4. and then does the whole thing a **second time** (`devcontainer build` followed by
   `devcontainer up`, each re-resolving features).

Measured on run `31161602566`, `build and test` job: 08:28:33.9 -> 08:29:25.8 = **~56s** before the
first line of `runCmd` executes. Paid once per job, so twice per run (build + lint). Subsequent
steps in the same job are cheap (~4-6s) because `devcontainer up` reuses the running container -
the existing workflow comments already note this and deliberately batch steps to amortize it.

## Fix

Use the canonical devcontainer prebuild flow. `build-devcontainer-image.yaml` already publishes a
tagged image; consume it as an image rather than as a build cache - a CI devcontainer.json variant
whose `"image"` is the prebuilt tag, with no `build` and no `features` block (the features are
already baked into the published image). That reduces materialization to a plain `docker pull`.

Expected: ~56s -> ~15-20s per job, so **~75s off the run**.

## Also worth checking while in here

The `devcontainer` job takes 165s when `.devcontainer/` changed (100s build + **51s push**) and
gates both `build` and `lint` via `needs:`. The 51s push is therefore on the critical path even
though nothing downstream needs the image to be _pushed_. On the common path (image already exists)
the job is only ~10s, so this is secondary - but if the push can be made non-blocking, that is
another ~50s on `.devcontainer`-touching runs.

## Work items

- [ ] Prototype the prebuilt-image devcontainer.json variant
- [ ] Confirm baked features behave identically (docker-in-docker especially - it is the stage that
      stalls, and CI's `pnpm build`/`pnpm test` depend on its daemon)
- [ ] Measure materialization before/after from the log timestamps
- [ ] Investigate decoupling the 51s devcontainer push from the `needs:` gate
