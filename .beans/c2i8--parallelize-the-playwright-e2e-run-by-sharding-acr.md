---
# c2i8
title: Parallelize the Playwright e2e run by sharding across WordPress containers
status: completed
type: task
priority: high
created_at: 2026-08-06T11:29:35Z
updated_at: 2026-08-06T11:49:26Z
---

Split out of [[t48h]], which measured the integration workflow's `build and test` job. After
the scaffolding fixes landed there, the single dominant remaining cost is the Playwright e2e
run: **122s of a ~360s job**, executed strictly serially (`workers: 1`, `fullyParallel: false`
in playwright.config.js).

## Why `workers: N` alone cannot work

The suite is written against ONE shared, mutable WordPress instance (a single throwaway
wp-alpine container started by scripts/test.sh). Specs set up global state in `beforeAll` via
wp-cli and actively contradict each other:

- `ionos_essentials_welcome` (user meta, user 1) - **set to true** by tabs, next-best-actions,
  maintenance and security-options; **deleted** by welcome and dashboard-localization.
- `ionos_group_brand` (option) - set to `ionos` by dashboard-myaccount and marketplace,
  flipped to `strato` mid-test by welcome.
- `secondary-plugin-dir.spec.js` runs `wp plugin deactivate ionos-essentials` in `beforeAll`
  and only reactivates it in `afterAll` - every essentials dashboard spec fails if it runs
  concurrently with that window.
- `maintenance.spec.js` enables maintenance mode globally.

So file-level parallelism against the shared instance is not merely flaky, it fails
deterministically. Any parallelism must give each worker its own WordPress.

## Approach: shard across independent containers

Run N throwaway wp-alpine containers (own name, own mount dir, own published port) and give
each one a Playwright `--shard=i/N`. Each shard gets a private WordPress, so the existing
`beforeAll` state setup stays valid exactly as written - no test rewrites.

Plumbing needed:

- scripts/test.sh: start N containers on TEST_HTTP_PORT+i, run N playwright processes
  concurrently, aggregate exit codes, tear all of them down on exit.
- playwright.config.js: `storageState`, `outputDir` and the html report folder are currently
  hardcoded single paths - each shard needs its own (different port => different cookies, and
  concurrent writers would otherwise clobber each other).
- globalSetup runs per shard and must authenticate against that shard's baseURL.
- scripts/_get-workflow-artefacts.sh: collect the per-shard report/result dirs.

Default must stay at 1 shard so local dev and targeted single-file runs are unaffected.

## Expected gain

CI runners are 4 vCPU / 16GB. Each wp-alpine container runs mariadb + php-fpm + caddy
(~525MB image), and each shard also drives a chromium. CPU contention means the realistic
target is 2-3 shards for roughly a 2x improvement, not a linear 4x:
122s -> ~60-70s, i.e. the job drops from ~6:00 to around **5:00**.

## Todo

- [x] Parameterize the test container start in scripts/test.sh so N can be started
- [x] Make playwright.config.js per-shard (storageState / outputDir / report folder)
- [x] Fan out the playwright invocation with --shard and per-shard WP_BASE_URL
- [x] Aggregate shard exit codes so a failure in any shard fails the run
- [x] Collect per-shard artifacts in _get-workflow-artefacts.sh
- [x] Validate locally that the sharded run is green and actually faster
- [x] Pick the shard count for CI and wire it into integration.yaml

## Summary of Changes

Sharding implemented and validated locally. `E2E_SHARDS=N` starts N throwaway wp-alpine
containers and runs one playwright process per shard against its own WordPress.

### Files

- `scripts/test.sh` - E2E_SHARDS plumbing: `start_test_container` / `await_test_container` /
  per-shard name+port+overlay helpers; shard 1 keeps the historical name/port so PHPUnit is
  unaffected; shards 2..N start before the PHPUnit run and are only awaited just before e2e,
  so their startup overlaps the syntax checks and PHPUnit; concurrent fan-out with
  `--shard=i/N`; exit codes aggregated across all shards.
- `playwright.config.js` - per-shard storageState / outputDir / html report.
- `playwright/exec-test-cli.js` - honours `TEST_CONTAINER_NAME` so a spec's wp-cli calls hit
  the same WordPress it is browsing (this was the hardcoded link that made sharding possible
  without touching a single spec).
- `scripts/_get-workflow-artefacts.sh` - collects the `-<n>` suffixed report/result dirs.
- `.github/workflows/integration.yaml` - `E2E_SHARDS=3`.
- `docs/5-test.md` - new "Running e2e tests in parallel" section.

### The non-obvious bug

The first sharded run had shard 3 failing 3 tests with `Error: Not logged in`. Cause:
@wordpress/e2e-test-utils-playwright's `requestUtils` fixture is worker-scoped and wired to a
module-level `STORAGE_STATE_PATH` (default `<cwd>/artifacts/storage-states/admin.json`) - a
single file shared by all concurrent shard processes. Specs call `requestUtils.setupRest()`
to restore login state, so the shards were overwriting each other's cookies, which are bound
to their own container's port. Every test after the first `setupRest()` in a shard then landed
back on wp-login.php.

Fixed by exporting `STORAGE_STATE_PATH` (and `WP_ARTIFACTS_PATH`) per shard, and pointing
playwright.config.js's `use.storageState` at that same file - previously the two were
different paths, which is a latent inconsistency even unsharded.

### Measurements (local, 20 cores - CI runners have 4, so expect less)

| run                      | wall clock |
| ------------------------ | ---------- |
| `pnpm test` unsharded    | 1m31.8s    |
| `pnpm test` E2E_SHARDS=3 | 1m06.9s    |
| e2e only, unsharded      | 1m24.9s    |
| e2e only, E2E_SHARDS=3   | 1m00.8s    |

All 28 e2e tests plus the 14 PHPUnit tests pass in every configuration; containers are torn
down cleanly; a targeted single-file run correctly falls back to one container.

Shard balance is uneven (32.6s / 49.1s / 26.9s) because `--shard` splits by test count, not
duration - shard 2 is the ceiling. Rebalancing would buy another ~15s.

## Follow-ups worth considering

- Re-measure on the CI runner and tune E2E_SHARDS - 3 containers plus 3 chromiums on 4 vCPUs
  may not be the optimum, and the local numbers do not predict it.
- Rebalance the shards (slowest shard currently ~1.8x the fastest).
