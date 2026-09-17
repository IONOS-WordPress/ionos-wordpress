---
# t48h
title: Speed up the integration workflow's 'build and test' job
status: completed
type: task
priority: high
created_at: 2026-08-06T09:33:10Z
updated_at: 2026-08-06T11:28:03Z
---

The `build and test` job in .github/workflows/integration.yaml is the critical path of the
integration workflow: 6:34 of a 6:56 run (run 31088625951). The `lint` job finishes in 2:00
and the `devcontainer` job in 0:13, so all wall-clock reduction must come from this job.

## Measured breakdown (run 31088625951, job 92574046253)

| step                           | wall clock                                                                 |
| ------------------------------ | -------------------------------------------------------------------------- |
| set up + checkout + ghcr login | 7s                                                                         |
| pnpm store cache restore       | 12s                                                                        |
| install project                | 59s (36s cold `devcontainer build` image pull + 23s actual `pnpm install`) |
| pull prebuilt wp-alpine image  | 14s                                                                        |
| pull prebuilt rector-php image | 14s                                                                        |
| build project                  | 94s                                                                        |
| publish rector-php image       | 6s                                                                         |
| **test project**               | **174s**                                                                   |
| gather + attach artifacts      | 9s                                                                         |
| **total**                      | **394s**                                                                   |

### Inside `build project` (89s of work)

- ionos-essentials 29s (webpack 1s, i18n ~6s, **rector 20s**)
- ionos-wpdev-caddy 12s (**rector 10s**)
- stretch-extra 30s (**rector 14s**)
- ionos-core 15s (**rector 12s**)
- => **~56s is Rector**, run serially, one `docker run` per plugin, with `--clear-cache`
  and no persistent cache volume. scripts/build.sh's package loop is strictly serial
  (topological order via tsort) even though the four plugins are independent of each other.

### Inside `test project` (174s)

- 4s devcontainer re-run overhead
- **12s downloading Playwright browsers** (184MB chromium + 115MB headless shell + 2MB ffmpeg).
  `scripts/test.sh` logs `found playwight installations : 0` every run - browsers land in
  `~/.cache/ms-playwright` _inside_ the ephemeral devcontainer, so nothing is ever cached.
- 2s vite build of the playwright-ct cache
- **6s `git clone` of WordPress/wordpress-develop#trunk** into ./mnt/wordpress-tests/trunk - never cached
- 9s waiting for the wp-alpine test container to become ready
- **11s PHP target-version syntax checks**, of which **5s is pulling `php:7.4-cli`** - never cached
- 1s PHPUnit (14 tests, 0.256s of actual test time)
- 8s gap (wp user fixups + playwright startup + globalSetup)
- **122s Playwright e2e: 28 tests, `workers: 1`, `fullyParallel: false`**

### Per-step devcontainer overhead

Every `devcontainer-shell-run` re-runs `devcontainer build` + `docker run`. The first costs
36s (pulls the cached image layers from ghcr); the six subsequent ones cost ~4s each = ~24s.
The `lint` job already consolidates its steps for this reason (see the comment at
integration.yaml:191-193); the `build` job still has 7 separate steps.

## Opportunities, by value

- [>] **Playwright e2e parallelism (~80s)** - split out into its own bean, see below.
- [x] ~~**Parallelize the four plugin builds (~35s)**~~ - SCRAPPED. Rejected by Lars: the build
      order is load-bearing beyond the declared `workspace:*` deps - stretch-extra and ionos-core
      consume essentials' _built_ output, so plugin builds must stay serial.
- [x] **Consolidated the 7 devcontainer-shell-run steps into 4 (~12s)** - install + wp-alpine
      pull + rector-php pull now share one step; build + rector-php publish share another.
      Needed renaming the clashing `IMAGE_REPOSITORY` env var into `WP_ALPINE_IMAGE_REPOSITORY`
      and `IMAGE_REPOSITORY_PREFIX`, matching the lint job's convention.
- [x] **Cached the Playwright browser download (~12s on a cache hit)** - scripts/test.sh sets
      `PLAYWRIGHT_BROWSERS_PATH=$(pwd)/.playwright-browsers` when CI=true, plus an
      `actions/cache` step keyed on pnpm-lock.yaml. Verified locally: a cold install downloads
      ~300MB, a warm re-run of `playwright install chromium` returns in 0.46s. Left untouched
      outside CI so local dev keeps its machine-wide ~/.cache/ms-playwright.
      Also fixed the adjacent diagnostic line, which globbed `*/chrome-linux/chrome` while
      playwright actually writes `chrome-linux64/` - it had always reported 0 installations.
- [x] **Sparse-cloned wordpress-develop instead of caching it (~4s)** - a full checkout is
      229MB, so an actions/cache restore would have cost more than the 6s clone it replaced.
      Only `tests/phpunit` is ever mounted into the test container, so the clone now uses
      `--filter=blob:none --sparse` + `sparse-checkout set --no-cone tests/phpunit`: 49MB.
      Verified the resulting tree is identical to the full clone apart from wp-tests-config.php,
      which test.sh mounts separately from ./phpunit anyway.
- [x] **Moved the `php:*-cli` pull off the critical path (~5s)** - the build step now pulls the
      images derived from packages/docker/rector-php/rector-config-php*.php in the background,
      concurrently with `pnpm run build`, into the same docker-in-docker daemon the test step
      reuses. Failures are swallowed; test.sh still pulls on demand as a fallback.

## Summary of Changes

Implemented the safe subset, minus the scrapped build parallelisation:

- `.github/workflows/integration.yaml` - 7 devcontainer-shell-run steps -> 4; added an
  actions/cache for .playwright-browsers; background `php:*-cli` prefetch during the build.
- `scripts/test.sh` - CI-only PLAYWRIGHT_BROWSERS_PATH; sparse clone of wordpress-develop;
  fixed the chrome-linux64 glob in the installation-count log line.
- `.gitignore` - /.playwright-browsers

Expected: ~33s off the 394s job => **6:34 -> ~6:00**. The playwright portion (~12s) only pays
from the second run onward, once the cache is populated.

Also restored `- uses: pnpm-store-cache` in the **lint** job: it was missing from the working
tree when this work started but present at HEAD (1f2b0e9b) - an uncommitted local deletion
unrelated to these changes.

## Deferred

The dominant remaining cost is the 122s serial Playwright e2e run (`workers: 1`). See the
unchecked item above - worth ~80s, but needs test-isolation work first.
