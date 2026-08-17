---
# xjvk
title: Attack the 250s of real work on the CI critical path (build 120s + test 130s)
status: in-progress
type: task
priority: high
created_at: 2026-08-07T08:56:22Z
updated_at: 2026-08-17T08:51:37Z
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

- [x] Profile what dominates the ~120s `pnpm build` (rector transpilation across PHP targets? wp-scripts bundling?) - it may have its own cheap win (answer: rector, ~49% of the now-67s step - see below)
- [x] Measure per-shard e2e wall clock to size the matrix payoff (see below - shards already balanced, matrix rejected)
- [ ] Price out larger runners against the measured saving
- [x] Sequence after `ytd4` - the matrix multiplies whatever the per-job setup cost is (ytd4 landed neutral, so matrix rejected - see below)

## Re-measured on run 31176419839 (2026-08-07, current state after ytd4/uizd)

Numbers in this bean's header are stale (predate other optimizations). Fresh breakdown:

| step         | time                                    | notes                                             |
| ------------ | --------------------------------------- | ------------------------------------------------- |
| `pnpm build` | **67s** (not 120s)                      | see below                                         |
| `pnpm test`  | **128s** (matches prior ~130s estimate) | ~35s container startup/wait + ~92s e2e wall clock |

`pnpm build` breakdown:

| segment                                             | time     | share                       |
| --------------------------------------------------- | -------- | --------------------------- |
| rector (4 separate CLI invocations, one per plugin) | **~33s** | 49%                         |
| i18n (POT/PO/MO/JSON/PHP generation, all plugins)   | ~14s     | 21%                         |
| stretch-extra plugin install (extendify etc.)       | ~10s     | 15%, possibly network-bound |
| webpack/wp-scripts bundling                         | ~3s      | 4%                          |

e2e shards are already fairly balanced: shard1 10 tests/1.5m, shard2 7 tests/1.3m, shard3 10
tests/1.6m (slowest, sets the wall clock).

## Job-matrix lever: investigated, rejected

The "move e2e shards to a job matrix" lever from this bean's original write-up assumed the
per-job devcontainer setup cost would shrink once `ytd4` landed, making the matrix pay for
itself. `ytd4`'s own measured outcome (see that bean) was that this cost is dominated by an
irreducible ~50-55s image pull (2.83GB image, network-bound) that did not shrink - it stayed a
wash.

Splitting 3 shards into 3 matrix jobs means paying that ~50s pull **three times instead of
once** (~150s total vs ~50s today), to save at most the ~30-40s of CPU contention visible in the
current shard timings (1.3m-1.6m spread). That is a net loss on the numbers we already have.

**Decision: do not pursue the e2e job-matrix split.** The remaining candidate levers are
batching rector's 4 invocations into one, checking whether the stretch-extra install is
network-bound/cacheable, and pricing larger runners (cost decision, not engineering) - not yet
scheduled under this bean.

## Design agreed: replace stretch-extra.sh's docker-run-php with native probe

Scope: fix both `install()` and `check()` in `scripts/stretch-extra.sh` (same pattern in both;
`install()` is the one on the CI critical path, `check()` isn't measured but shares the pattern).

Both currently shell out to a full `docker run ... php:8.3-cli[-alpine] php ...` just to eval a
static PHP config array and JSON-encode it - no version-sensitive behavior, any PHP works. The
devcontainer image is already `mcr.microsoft.com/devcontainers/php:8.4-bookworm`, so PHP is
native there and this docker round-trip is pure overhead (same class of cost `e6mc` already
eliminated elsewhere in the repo).

New shared helper (named, since used twice):

```bash
function ionos.wordpress.stretch_extra.run_php() {
  if command -v php >/dev/null 2>&1; then
    php "$@"
  else
    docker run --rm -i --quiet -v "$(pwd):/app" -w /app php:8.3-cli "$@"
  fi
}
```

- Probes with a plain `command -v php`, not the `_native-tools.sh` pinned-tool dispatch (that
  machinery is for the four versioned `packages/docker/<tool>` images with their own
  `COMPOSER_HOME`; this is just "run any PHP", nothing pinned).
- Native branch runs in the current `$(pwd)`, matching the docker branch's
  `-v "$(pwd):/app" -w /app` mount.
- `install()`'s heredoc and `check()`'s `-r "..."` both pass through unchanged as stdin/`"$@"`.
- Fallback path standardizes on a single image (`php:8.3-cli`) rather than keeping `install()`'s
  `cli` and `check()`'s `cli-alpine` separate - it's a rarely-exercised fallback (devcontainer/CI
  always take the native branch), not worth maintaining two images for.

Status: implemented in scripts/stretch-extra.sh.

## Implementation notes

Added `ionos.wordpress.stretch-extra.run_php()` and routed both `install()` (heredoc) and
`check()` (`-r` inline script) through it.

**Bug caught during verification**: the docker fallback branch initially omitted the explicit
`php` command (`docker run ... php:8.3-cli "$@"`). With no extra args (the `install()` heredoc
case), `"$@"` is empty, so the command reduces to `docker run ... php:8.3-cli` with nothing
after the image name - which runs the image's default CMD (`php -a`, the interactive REPL)
instead of reading the script from stdin. Fixed by always passing `php` explicitly:
`docker run ... php:8.3-cli php "$@"`.

Verified on a host with no native php (forces the docker fallback branch, the only branch
testable here): `./scripts/stretch-extra.sh --check` and `--install` both run clean end to end,
producing the same output as before the change. No changeset - root-level scripts/CI tooling
changes in this repo don't carry one (see `ytd4`, `e6mc`, `uizd` for precedent).

Not yet measured in CI (would need the native branch, i.e. a run inside the devcontainer, to
confirm the actual time saved on the ~10s stretch-extra install segment).

## Confirmed in CI: run 32011855968 (2026-08-17, first run with the native-php fix)

| step                            | before (31176419839) | after (32011855968) | delta |
| ------------------------------- | -------------------- | ------------------- | ----- |
| `build project`                 | 67s                  | **59s**             | -8s   |
| stretch-extra `--install` alone | ~10s                 | **~2.8s**           | -7.2s |

Matches the expected payoff - the docker-in-docker round trip for the config parse is gone,
leaving only the real network time (curl-ing extendify/extendable) and the unzip.
