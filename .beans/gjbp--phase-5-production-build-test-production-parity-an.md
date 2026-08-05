---
# gjbp
title: Phase 5 — Production-build (TEST_PRODUCTION) parity and CI custom-PHP-version testing
status: completed
type: task
priority: normal
created_at: 2026-08-03T10:59:36Z
updated_at: 2026-08-05T08:32:58Z
parent: b55y
blocked_by:
  - 7hn5
---

Goal: preserve the ability to run the full test suite against transpiled dist/ output, and add
a way to run tests against a non-default PHP version without reintroducing a maintained
multi-version image matrix.

## Tasks

- [x] Port the current .wp-env.override.json mapping-rewrite logic into the Phase 2/3 mount
      generator: when TEST_PRODUCTION=true is set, point generated bind mounts at
      packages/wp-plugin/<name>/dist/... instead of source, and rsync phpunit/ test dirs into
      the dist folders as today. Purely a source-vs-dist mount switch — unrelated to PHP version
- [x] Add PHP_VERSION_OVERRIDE=<php-version> as a separate mechanism: Dockerfile keeps its
      ARG_PHP_VERSION build-arg, Phase 1 publish workflow builds/pushes a small prebuilt matrix
      (8.4 default, 7.4 legacy). When PHP_VERSION_OVERRIDE=7.4 is set, scripts/test.sh pulls the
      matching prebuilt tag instead of building a local image, and runs the ephemeral test stack
      against it — no build step on the hot path since this runs on every PR update and locally

## Exit criteria

TEST_PRODUCTION=true pnpm test passes, matching current CI behavior;
PHP_VERSION_OVERRIDE=7.4 pnpm test runs the suite against the prebuilt PHP 7.4 image with no
local build step, locally and in CI.

See docs/agent/wp-env-to-alpine-migration-plan.md for full context.

## Summary of Changes

Implemented both remaining pieces of the wp-env→Alpine migration's TEST_PRODUCTION
and PHP_VERSION_OVERRIDE parity:

- `scripts/includes/_docker-mounts.sh`: added `ionos.wordpress.mount_phpunit_dirs()`,
  which bind-mounts each source `phpunit/` test directory directly at its equivalent
  path under the mounted `dist/` output, since rector's build step excludes `tests/`
  from `dist/` (`scripts/build.sh --exclude=tests/`). Chose a bind mount over a
  filesystem copy (the pre-wp-alpine `.wp-env.override.json`-era approach) because
  `dist/` is also bind-mounted wholesale in source mode - writing real test files
  into it would leak stale copies into later non-`TEST_PRODUCTION` runs (confirmed
  this actually breaks with a "Cannot redeclare class" fatal when first tried the
  rsync approach).
- **Real bug found and fixed while verifying** (not just review - actually running
  `TEST_PRODUCTION=true pnpm test:php`): the existing TEST_PRODUCTION mu-plugin mount
  pointed the package's dist root (containing both the loader `.php` and a nested
  `<plugin>/` code dir, mirroring source layout) directly at
  `wp-content/mu-plugins/<plugin>`, instead of its nested `<plugin>/<plugin>/`
  subdir - crashing every TEST_PRODUCTION run with a WP-CLI fatal
  (`Failed opening required '.../mu-plugins/ionos-core/update/index.php'`) since the
  loader's `require_once __DIR__ . '/ionos-core/...'` had no matching directory.
  Found the identical bug in the regular wp-plugin dist mount too (masked there since
  the whole dist root, including the misplaced nested code, is still mounted as one
  unit - only surfaced via a missing-fixture test failure, not a crash). Fixed both.
- `scripts/test.sh`: added `PHP_VERSION_OVERRIDE=7.4` handling - validates against
  the published matrix (only `7.4` today), computes the same content-hash tag the
  Phase 1 publish workflow (`.github/workflows/build-wp-alpine-image.yaml`) produces,
  logs into `IMAGE_REGISTRY` when credentials are present, and `docker pull`s the
  prebuilt tag instead of using the local `ionos-wordpress/wp-alpine:latest` build.
- `.env`: added `IMAGE_REGISTRY`/`IMAGE_REPOSITORY` (defaulting to GHCR, matching the
  publish workflow). `.env.local.example`/`.secrets.example`: documented
  `PHP_VERSION_OVERRIDE` and the optional `IMAGE_REGISTRY_USERNAME`/
  `IMAGE_REGISTRY_PASSWORD` secrets.

**Verified end-to-end** (real runs against a local Docker registry standing in for
GHCR, not just code review): `pnpm test:php` in source mode (15 tests),
`TEST_PRODUCTION=true pnpm test:php` (14 tests - `stretch-extra` mu-plugin has no
dist variant so its 1 test is expectedly absent), `TEST_PRODUCTION=true pnpm
test:e2e --e2e-opts "--grep-invert @editor"` (28/28 specs), `PHP_VERSION_OVERRIDE=7.4
pnpm test:php` pulling from a local registry pushed under the exact
`<hash>-php7.4` tag scheme, and the combination
`TEST_PRODUCTION=true PHP_VERSION_OVERRIDE=7.4 pnpm test:php` (14/14) - and
confirmed running source-mode `pnpm test:php` again immediately after a
`TEST_PRODUCTION=true` run still passes (no dist/ pollution regression).
