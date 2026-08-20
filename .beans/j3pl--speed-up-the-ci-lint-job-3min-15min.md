---
# j3pl
title: Speed up the CI lint job (3min -> ~1.5min)
status: completed
type: task
priority: normal
created_at: 2026-08-06T08:40:03Z
updated_at: 2026-08-06T08:51:32Z
---

The `lint` job of `.github/workflows/integration.yaml` takes ~3 minutes
(run 31084271555, job 92561744049) while the actual linting is only ~25s.
~120s of the 181s is scaffolding.

## Measured breakdown (job 92561744049)

| step                                                           | duration |
| -------------------------------------------------------------- | -------- |
| checkout + ghcr login                                          | 8s       |
| `install` (first devcontainer boot ~50s + `pnpm install` ~30s) | 82s      |
| 3x `pull prebuilt <img> image`                                 | 31s      |
| `build necessary dockers`                                      | 7s       |
| `lint_project` (4s container start + 25s lint)                 | 29s      |
| 3x `publish <img> image`                                       | 18s      |
| teardown                                                       | 6s       |

Inside the 25s of real linting: ecs/PHP 12s, prettier 4.4s, docker image
existence check 3.5s, eslint 2.4s, stylelint 1.1s, pnpm-lock 0.5s,
wp-headers 0.2s, dennis 0.3s.

Root cause: the job uses 8 separate `devcontainer-shell-run` steps. Each
re-runs `devcontainer build` + `docker run` even on a full cache hit -
the first pays ~50s (image pull), each of the other seven ~6s just to get
a shell.

## Todo

- [x] Merge the 3 image-pull steps into a single `devcontainer-shell-run`,
      and the image-push steps likewise (~40s)
- [x] Drop potrans from the lint job entirely (~16s): `pnpm run lint`
      never uses potrans, only `lint-fix --use i18n` with a DEEPL_API_KEY
      does. It is pulled+pushed only because `scripts/lint.sh` builds
      `dennis-i18n potrans ecs-php` unconditionally - make that build
      line depend on `$USE`/`$FIX`
- [x] Remove the `build necessary dockers` step (~7s): it is a no-op,
      the images were just pulled and tagged and `scripts/lint.sh` runs
      the same build filter itself
- [x] Only push an image when the pull missed (~12s): have
      `docker-subproject-image-pull.sh` record a marker and guard
      `docker-subproject-image-push.sh` on it
- [x] Verify docs (`docs/4-lint.md`, `docs/agent/*`, `.github` READMEs)
      still match the changed behaviour

## Deferred (separate beans if wanted)

- Cache the pnpm store across runs (~20s)
- Run the linters in parallel inside `scripts/lint.sh` (~10s)

Expected: ~181s -> ~105s.

## Summary of Changes

### `.github/workflows/integration.yaml` (lint job)

8 `devcontainer-shell-run` steps -> 3:

- `install` + the 3 `pull prebuilt <img> image` steps merged into one
  `install and pull prebuilt lint images` step
- `build necessary dockers` deleted (`scripts/lint.sh` builds what it needs)
- the 3 `publish <img> image` steps merged into one `publish lint images` step
- potrans dropped entirely (pull, push and its `docker-subproject-image-name`
  step) - `pnpm run lint` never invokes it, and `pnpm run lint-fix:i18n`
  (the only consumer) is never run in CI

Also fixed the now-stale potrans mention in the
`DOCKER_SUBPROJECT_IMAGE_REPOSITORY_PREFIX` env comment.

### `scripts/lint.sh`

`pnpm build --filter dennis-i18n --filter potrans --filter ecs-php` was
unconditional. Now the filter list is derived from `$USE`/`$FIX`:
ecs-php for `all|php`, dennis-i18n for `all|i18n`, potrans only for
`--fix --use i18n`. `pnpm lint --use css` now builds no docker image at all.

### `.github/shared/scripts/docker-subproject-image-{pull,push}.sh`

`-pull.sh` writes a `<path>/image-pull-hit` marker on a cache hit (and
removes a stale one up front); `-push.sh` exits early when it sees the
marker. Previously a cache hit still paid a `docker login` + registry
round trip per image to upload zero layers. Also benefits the `build`
job's rector-php push. Marker added to `.gitignore` (and the duplicated
`build-info` line there removed).

### Documentation

Checked `docs/4-lint.md`, `docs/agent/*`, `.github/shared/**`. Found and
fixed - partly pre-existing, unrelated to this change:

- `docs/4-lint.md`: claimed `scripts/lint.sh` "also runs `phpcs` directly"
  (the phpcs function has been commented out as "not used anymore");
  pointed at `./ecs-config.php` instead of
  `./packages/docker/ecs-php/ecs-config.php`; embedded `--help` output was
  missing the `wp` linter. Added a "docker images" section documenting
  which image each linter needs and that CI pulls rather than builds them.
- `docs/agent/changeset-workflow.md`: the package-name table had 6 of 7 rows
  wrong (`packages/npm/*` for what actually lives in `packages/docker/*`,
  `wp-plugin/essentials` instead of `wp-plugin/ionos-essentials`) and was
  missing `ionos-core` and `wp-alpine`. Regenerated from the actual
  `package.json` files.

### Verification

- `pnpm lint --use css` -> no docker build triggered
- `pnpm lint --use i18n` -> builds dennis-i18n only (no potrans, no ecs-php)
- `pnpm lint` -> all 7 linters green
- workflow yaml parses; lint job now has 3 devcontainer-shell-run steps

No changeset: CI/tooling + docs only.

Expected CI effect: ~181s -> ~135s. The remaining deferred items (pnpm
store cache ~20s, parallel linters ~10s) would take it to ~105s.

## Measured after the change

Run 31086386050 (job 92566753896), commit e4a4bc99: **181s -> 145s (-36s, -20%)**.

| step                                | before    | after |
| ----------------------------------- | --------- | ----- |
| checkout + ghcr login               | 8s        | 7s    |
| install (+ image pulls, now merged) | 82s + 31s | 97s   |
| `build necessary dockers`           | 7s        | gone  |
| `lint_project`                      | 29s       | 31s   |
| publish images                      | 18s       | 5s    |
| setup/teardown                      | 6s        | 5s    |

Both pulls hit and both pushes correctly logged
"skip pushing ... registry already has it", so the marker mechanism works
end to end in CI.

Slightly less than the ~46s estimate: the merged install+pull step saved
16s rather than 23s (the two pulls still take ~12s of actual download).

What is left is dominated by the 82s install: ~50s devcontainer boot
(unavoidable first `devcontainer up` in the job) + ~30s `pnpm install`.
See the deferred items above.
