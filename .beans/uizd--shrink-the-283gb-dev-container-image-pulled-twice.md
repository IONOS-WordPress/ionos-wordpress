---
# uizd
title: Shrink the 2.83GB dev container image (pulled twice per CI run)
status: completed
type: task
priority: high
created_at: 2026-08-07T11:22:03Z
updated_at: 2026-08-07T11:56:08Z
parent: 1qc9
---

The dev container image is **2.83GB uncompressed** (838MB `devcontainers/php:8.4-bookworm` base +
~2GB of our own layers) and is fetched once per job, twice per run. Measured at ~39s per job in run
31172912156 - the single largest fixed cost left in CI, and the thing [[ytd4]] wrongly assumed was a
mechanism problem rather than a payload problem.

It is also the whole of a developer's first-run wait, so this is one of the few items that pays off
on both sides.

## Prime suspect

`.devcontainer/Dockerfile` runs `pnpx playwright install-deps` with **no browser argument**, which
installs the system dependencies for firefox and webkit as well. Only chromium is ever used -
`scripts/test.sh` runs `pnpm exec playwright install chromium`, and `playwright.config.js` has no
other browser project.

`install-deps chromium` is strictly smaller (verified: the all-browser set additionally wants
libavcodec/libavif/libgav1/libyuv, which are webkit/firefox media deps). **Not quantified on
bookworm** - deliberately not predicting a number here, see the postmortem in [[ytd4]].

## Instrumentation gap to close at the same time

`devcontainer-shell-run` currently logs `npm install -g @devcontainers/cli` and `docker pull` inside
one 39s block, so their split is unknown. That matters: if the npm install is a meaningful slice, it
is separately fixable (cache it), and without the split we would be guessing again.

## Work items

- [x] Split the `npm install` / `docker pull` timings in `devcontainer-shell-run` so the log shows
      each on its own
- [x] `pnpx playwright install-deps` -> `pnpx playwright install-deps chromium`
- [x] Also drop the three composer download caches (~27MB) - no runtime value
- [x] Measure in CI: per-job pull time before/after
- [x] ~~Fold in [[xla3]]~~ - **rejected, it works against this bean**. Baking the browser would add
      ~300MB to an image pulled twice per run, and CI would not even use it: `scripts/test.sh` points
      `PLAYWRIGHT_BROWSERS_PATH` at a workspace-relative directory when `CI=true` so the host-side
      `actions/cache` can carry it between runs, and that restore already costs only ~7s. Noted in
      the Dockerfile so nobody "fixes" it later.
- [x] Decide [[ytd4]]'s fate on the result

## Measured locally (deterministic - image size does not need CI)

Like for like, both a plain `docker build` of `.devcontainer/Dockerfile` with no features layered on:

| image                     | size       |
| ------------------------- | ---------- |
| before                    | 2.00GB     |
| `install-deps chromium`   | 1.64GB     |
| + composer caches removed | **1.61GB** |

**-390MB, 19.5%.** In CI the dev container features (docker-in-docker, claude-code) add ~830MB on
top, so the pulled image goes ~2.83GB -> ~2.44GB, about 14%.

Note the 2.83GB figure quoted earlier in this bean is the _with-features_ image built by
`devcontainer up`; comparing it against a plain `docker build` would have overstated the win as 42%.

Verified in the slimmed image: `pnpm lint --use php` and `--use i18n` pass (so all four native tools
still work), and chromium still launches and renders a screenshot - the reduced dependency set does
not break it.

## Other candidates if playwright is not the bulk

- `pnpm env use --global 24.18.0` pulls a full node distribution (~200MB)
- the three composer tool installs added by [[e6mc]] (~100-200MB) - the cost of the native tools,
  and worth knowing precisely
- apt lists / caches not cleaned in the `gh`/`entr` layer

## Measured in CI (run 31175438431)

The instrumentation added with this change now separates the two setup costs:

```
timing: devcontainer CLI install took 1s      (lint)   / 12s (build and test)
timing: image pull took 31s (2.3G on disk)    (lint)   / 32s (build and test)
```

The image is **2.3G in CI**, down from ~2.83G - the predicted ~2.44G, slightly beaten.

| job            | before (31172912156) | after (31175438431) |
| -------------- | -------------------- | ------------------- |
| lint           | 112s                 | **93s**             |
| build and test | 310s                 | **283s**            |
| lint `install` | 59s                  | **53s**             |

### How much of that is really this change

Attributable: the pull, ~39s (pull+npm, unseparated) -> ~32s, so **~6s per job, ~12s per run**.

The rest of the job-level delta is run-to-run variance, not this change. Across the three runs
since e6mc: `build project` 70s / 73s / 66s, `test project` 138s / 129s / 123s - a +-5-6% spread
that swamps a 6s effect. Saying "lint got 19s faster" would be reading noise as signal.

The real, durable win is the 390MB every developer no longer pulls.

### Newly visible: the CLI install is worth removing

`npm install -g @devcontainers/cli` measured **1s in one job and 12s in another** in the same run.
That variance is now the largest uncontrolled cost in the setup path, and it exists only because
[[ytd4]] moved off devcontainers/ci. Worth eliminating - but with two samples one second apart in
one job and twelve in another, gather more data before picking a fix.
