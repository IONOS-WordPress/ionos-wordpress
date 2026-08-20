---
# 7hn5
title: Phase 4 — Playwright/E2E against the same ephemeral test stack
status: completed
type: task
priority: normal
created_at: 2026-08-03T10:59:25Z
updated_at: 2026-08-04T14:33:57Z
parent: b55y
blocked_by:
  - g4m1
---

Goal: pnpm test:e2e reuses the Phase 3 ephemeral test container.

## Tasks

- [x] Update playwright.config.js: baseURL points at the ephemeral test stack's dynamically
      assigned (or fixed) port instead of wp-env's hardcoded localhost:8889
- [x] Rewrite playwright/wp-env.js's execTestCLI to docker exec into the new test container
      (single container now, no more tests-cli-1 container-name discovery via
      `wp-env status --json`)
- [x] Preserve global-setup.js behavior (RequestUtils auth/storage state, theme/plugin
      activation) — should need no changes beyond the base URL/port
- [x] Ensure scripts/test.sh's pre-e2e admin-password-reset step still works against the new
      container
- [x] Tie into the same ephemeral start→run→teardown wrapper from Phase 3: a single
      `pnpm test` invocation brings up one shared ephemeral test-stack container and runs both
      PHPUnit and Playwright against it, tearing it down once at the end (pass or fail)

## Exit criteria

pnpm test:e2e passes against the current Playwright suite; container is torn down after.

See docs/agent/wp-env-to-alpine-migration-plan.md for full context.

## Summary of Changes

Rewired `pnpm test:e2e` (and the shared `pnpm test` path) to run Playwright against
the same ephemeral `wp-alpine` container Phase 3 introduced for PHPUnit, instead of
the removed `wp-env`.

- `scripts/test.sh`: hoisted the container start/readiness-wait/teardown-trap logic
  out of the `all|php`-only block into a new block gated by `all|php|e2e` - so a
  `--use php --use e2e` (or `all`) run shares **one** container and **one** teardown
  (verified via log-line counting: exactly one "waiting for the test container" per
  combined run, and via a live `docker ps` check mid-run). Added
  `--publish "${TEST_HTTP_PORT}:80"` to the `docker run` call (Phase 3 deliberately
  published nothing, since PHPUnit never needs HTTP - Playwright does). The e2e
  sub-block replaces `pnpm -s wp-env run tests-cli wp ...` with
  `docker exec --user php "$TEST_CONTAINER_NAME" wp ...` for the pre-e2e admin
  password reset, and exports `WP_BASE_URL="http://localhost:${TEST_HTTP_PORT}"`
  before invoking `pnpm exec playwright test` - `@wordpress/scripts`'s own
  `config/playwright.config.js` already resolves `baseURL` from that env var, so no
  `playwright.config.js` baseURL override was needed.
- `.env`: added `TEST_HTTP_PORT=8889` (matches the old wp-env e2e port for
  continuity, now a real setting instead of a hardcoded fallback).
- `playwright/wp-env.js`'s `execTestCLI`: dropped the `wp-env status --json`/`jq`
  container-name discovery entirely - fixed container name (`ionos-wordpress-test`)
  now, plus `--user php` (matching `pnpm cli`/`pnpm enter`'s Phase 2 convention).
  Exported function name/signature unchanged - none of the ~13 `*.spec.js` files
  importing it needed changes.
- `playwright.config.js`: replaced the inherited, dead `webServer.command: 'npm run
wp-env start'` with an explicit no-op (`'true'`) since `reuseExistingServer: true`
  means it's never actually invoked (test.sh starts the container first) but a stale
  wp-env reference sitting there was misleading.

**Real bug found and fixed while verifying** (not just code review - actually running
the suite): the first `pnpm test:e2e` run had 1 real failure (`Descriptify ›
Changing of homeurl is disabled`) - traced to a genuine parity gap from Phase 1's
entrypoint, which never defined `WP_HOME`/`WP_SITEURL` as wp-config.php constants the
way wp-env's generated config always did (that's what makes WordPress disable the
siteurl/home admin fields and show the "defined in wp-config.php" notice). Fixed in
`packages/docker/wp-alpine/docker-entrypoint.sh`'s `wp config create --extra-php`
block. Also found and fixed a second real gap in
`scripts/includes/_docker-mounts.sh`: the shared `.default-themes-cache` reseeding
step from the prototype's `prepare-mounts.sh` was never ported in Phase 2/3 - the
persistent dev container happened to work by being the very first container to ever
download core (bundled themes land directly in its own overlay), but the ephemeral
test container always starts with an empty themes overlay and needs the reseed.
Fixed by adding the cache-copy step to `build_wp_volume_args()`, benefiting both
`start.sh` and `test.sh`.

**Verified end-to-end** (real runs, not just review): `pnpm test:e2e` passes all 28
specs across `packages/wp-plugin`/`wp-mu-plugin`; `pnpm test` (react + php + e2e
together) passes with exactly one container lifecycle; container and
`${MNT_HOME}/test` overlay both removed after both success and mid-run interference;
`execTestCLI`-dependent specs (e.g. `descriptify.spec.js`'s `wp option update
ionos_market de`) confirmed actually executing against the container.

This completes the `b55y` epic (Phases 1-4: base image → dev stack → PHPUnit → E2E,
all now running on `wp-alpine` instead of `@wordpress/env`). Remaining phases per
`docs/agent/wp-env-to-alpine-migration-plan.md`: Phase 5 (`TEST_PRODUCTION`/
`PHP_VERSION_OVERRIDE` parity - `TEST_PRODUCTION` mount-switching is already wired
via Phase 2/3's shared mount builder, `PHP_VERSION_OVERRIDE` itself is not yet),
Phase 6 (CI integration), Phase 7 (cutover - delete `@wordpress/env`,
`wp-env-after-start.sh`/`wp-env-after-destroy.sh`, `.wp-env.json` generation code, and
update docs still referencing wp-env).
