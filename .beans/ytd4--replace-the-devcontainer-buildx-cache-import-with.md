---
# ytd4
title: Replace the devcontainer buildx cache-import with a prebuilt-image pull (~75s/run)
status: completed
type: task
priority: high
created_at: 2026-08-07T08:56:08Z
updated_at: 2026-08-07T11:56:32Z
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

- [x] Prototype the prebuilt-image devcontainer.json variant
- [x] Confirm baked features behave identically (docker-in-docker especially - it is the stage that
      stalls, and CI's `pnpm build`/`pnpm test` depend on its daemon)
- [ ] Measure materialization before/after from the log timestamps (needs a CI run)
- [ ] Investigate decoupling the 51s devcontainer push from the `needs:` gate - deliberately left
      out of this change; it only pays off on runs that touch `.devcontainer`

## Implementation

`devcontainers/ci` is gone from `devcontainer-shell-run`; it drives the devcontainer CLI directly
against a generated `{name, image}` config. Everything else - privileged, the docker-in-docker
entrypoint and volume, `remoteUser`, the dev config's own mounts - is read back from the image's
`devcontainer.metadata` label, so the generated config only has to name the image. That is the
canonical prebuild flow and it is what keeps the CI config from drifting against
`.devcontainer/devcontainer.json`.

Deliberately omitted from the generated config: `appPort` (nothing connects from the runner - the
tests talk to wordpress-alpine inside docker-in-docker), `containerEnv` (resolving
`${localEnv:ANTHROPIC_API_KEY}` would write a secret to a file on the runner) and the vscode
customizations.

The image must already be published. All three consumers (`integration.yaml`'s build and lint,
`pre-release.yml`) declare `needs: devcontainer`, which builds and pushes it when the tag is
missing - so a failed pull is a real error and is reported as one rather than silently degrading
into a local build.

## Measured locally before pushing

Against a locally built stand-in image, with the workspace bind-mounted exactly as in CI:

| operation                     | time |
| ----------------------------- | ---- |
| `devcontainer up` (image cfg) | 2.1s |
| `devcontainer exec`           | 0.9s |
| full action body              | 1.9s |

versus ~55s for the buildx cache-import path. Also verified:

- **docker-in-docker actually runs** under the metadata entrypoint (`docker version` inside reports
  server 29.7.2) - this was the main risk of dropping the `features` block.
- **the container is reused across steps**: same container id, `/tmp` marker survives, and the dind
  daemon keeps its pulled images. The build job depends on this - it pulls wordpress-alpine in one
  step and consumes it two steps later.
- stdout is captured into `runCmdOutput` and stderr is not, which is what
  `gather_workflow_artifacts` needs (it reads a list of paths from stdout while the scripts log to
  stderr).
- a non-zero exit propagates through the `| tee` (this is why the body sets `pipefail`; without it
  the failure is masked).
- `--remote-env` parsing handles blank lines and values containing spaces.

Not exercised locally: the authenticated `docker pull` (the token on hand lacks `read:packages`) and
the one-off `npm install -g @devcontainers/cli` (the CLI version is read from `package.json` so the
host CLI and the workspace dependency cannot drift).

## Verified safe to drop

`devcontainers/ci` mounted the runner's file-command files so `$GITHUB_OUTPUT`/`$GITHUB_ENV`/
`$GITHUB_STEP_SUMMARY` worked inside the container. Nothing needs that: the only writer is
`scripts/release.sh`, and `release.yaml` runs it directly on the runner, not through this action.
`pre-release.sh` mentions `pnpm release` only in a comment.

## Re-measured after e6mc landed (run 31171188972)

e6mc removed the docker round trips, which shrank everything _except_ this. The dev container
materialization is unchanged at ~55s per job, so it is now a much larger share of what is left:

| step                          | before e6mc | after e6mc | of which materialization |
| ----------------------------- | ----------- | ---------- | ------------------------ |
| build job, `install and pull` | 80s         | 68s        | ~55s                     |
| lint job, `install`           | 76s         | 61s        | ~55s                     |
| `build project`               | 124s        | 70s        | -                        |
| `lint_project`                | 60s         | 31s        | -                        |
| **build and test** (job)      | 384s        | **303s**   |                          |
| **lint** (job)                | 176s        | **111s**   |                          |

So this task is now worth roughly:

- build and test: 303s -> ~263s
- lint: 111s -> ~71s (materialization is **~50% of that whole job**)
- critical path: ~313s -> ~273s

Nothing about the approach changes - it is still "stop using `cacheFrom` as a build cache and pull
the prebuilt image instead". Only the payoff estimate moves, upward in relative terms.

Caveat on the numbers: run 31171188972 also paid a one-off 272s dev container rebuild (161s build +
98s push) because that commit touched `.devcontainer` and the tool directories, so its 591s wall
clock is not the steady state. The steady state should be ~10s + 303s. Confirm against the next run
that does not touch those paths before quoting a total.

## Outcome: measured in CI, and it did NOT deliver

Run 31172912156 (this change) against run 31171188972 (previous, buildx path). Like for like -
both had to fetch the image, neither rebuilt the dev container:

| step                          | buildx path | pull path | delta |
| ----------------------------- | ----------- | --------- | ----- |
| lint job, `install`           | 61s         | **59s**   | -2s   |
| build job, `install and pull` | 68s         | **69s**   | +1s   |
| lint (job)                    | 111s        | **112s**  | +1s   |
| build and test (job)          | 303s        | **310s**  | +7s   |

**Net zero.** The predicted ~75s/run saving did not materialise.

### Why the estimate was wrong

The original profiling attributed a ~30s stall on buildx stage `#27` to "buildx pulling the image
through cache resolution, which is slower than a plain pull". That was the wrong conclusion: the 30s
was the **layer transfer itself**, which is identical whichever mechanism fetches it. The dev
container image is **2.83GB uncompressed** (838MB base + ~2GB of our layers), and it is fetched once
per job, twice per run. That cost is irreducible without making the image smaller.

Decomposition, before and after - they come to the same total:

| buildx path                      |      | pull path                   |          |
| -------------------------------- | ---- | --------------------------- | -------- |
| feature resolution x2 + manifest | ~7s  | `npm install -g` CLI + pull | 39s      |
| layer fetch                      | ~30s | (included above)            |          |
| second build (`build` then `up`) | ~3s  | -                           |          |
| container start + dind init      | ~12s | `devcontainer up`           | 12.6s    |
| **total**                        | ~52s | **total**                   | **~52s** |

So this removed ~10s of genuine buildx overhead (double feature resolution + the redundant second
build) and gave it straight back as `npm install -g @devcontainers/cli` on the host. The npm install
and the pull are not separated in the log - that is a gap in this change's own instrumentation.

### What is still true

- Container reuse across steps works in CI (only the first step starts a container), so the
  build job still pulls wordpress-alpine in one step and consumes it two steps later.
- Both jobs are green; nothing regressed functionally.
- The mechanism is now explicit - a pull is a pull - rather than a third-party action performing a
  hidden buildx build.

### Where the time actually is

The 2.83GB image, fetched twice per run. Candidate: `.devcontainer/Dockerfile` runs
`pnpx playwright install-deps` with no browser argument, which installs the system dependencies for
firefox and webkit as well, though only chromium is ever used. `install-deps chromium` is strictly
smaller - unquantified on bookworm, and deliberately not promised as a number after this bean's
estimate went wrong once already. Measure it in CI before believing it.

Cheaper, smaller follow-up: the `npm install -g` is paid per job and could be cached or avoided

- but instrument the pull/npm split first rather than guessing again.

## Verdict: kept, but it did not pay for itself

[[uizd]] settled the open question by measuring the two setup costs separately (instrumentation this
bean should have had from the start):

```
image pull                 31-32s   <- the real cost, proportional to image size
devcontainer CLI install   1-12s    <- introduced by THIS bean
devcontainer up            ~12s     <- unchanged either way
```

So the pull path costs ~44-55s against the buildx path's ~52s. Still a wash. What this change
removed (double feature resolution and a redundant second build, ~10s) it gave back as
`npm install -g @devcontainers/cli`, which devcontainers/ci did not need because it bundles the CLI.

**Kept rather than reverted**, on two grounds that are about clarity, not speed:

- the mechanism is now honest - a pull is a pull, rather than a third-party action performing a
  hidden buildx build whose cost is easy to misattribute (which is exactly what happened here)
- it is what made the pull cost measurable at all, and therefore what made [[uizd]] possible

If the `npm install -g` is eliminated the balance tips to a genuine ~9s/job win. Until then this is
neutral, and it should not be cited as a performance improvement.

### Lesson

The local benchmark that justified this bean (`devcontainer up` in 2.1s) measured an image that was
**already present locally**, i.e. everything except the bottleneck. A local measurement of a
CI-network-bound cost is worthless. Measure the thing that actually varies.
