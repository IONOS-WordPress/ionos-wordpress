---
# g4m1
title: Phase 3 — PHPUnit on an ephemeral test stack
status: completed
type: task
priority: normal
created_at: 2026-08-03T10:59:25Z
updated_at: 2026-08-04T13:38:00Z
parent: b55y
blocked_by:
    - euw2
---

Goal: pnpm test:php starts a throwaway test container, runs PHPUnit inside it, and tears it
down unconditionally afterward.

## Tasks
- [x] Rewrite the PHPUnit path in scripts/test.sh: start a fresh test-stack container (own
      compose project/name so it can't collide with the dev stack), wrap the run in a
      trap/finally so the container is destroyed on success and failure
- [x] Replace the docker cp-based approach (pushing phpunit.xml/bootstrap.php/
      wp-tests-config.php in, pulling vendor/ out) with bind-mounting phpunit/ directly, since
      composer/phpunit deps are now baked into the image (Phase 1)
- [x] Preserve the --exclude .../mu-plugins/stretch-extra filtering and per-file --filter
      behavior from current scripts/test.sh (moved into phpunit.xml's <exclude>, see summary)

## Exit criteria
pnpm test:php passes against current test suite, and confirms the container is gone
afterward regardless of pass/fail.

See docs/agent/wp-env-to-alpine-migration-plan.md for full context.

## Summary of Changes

Rewrote `pnpm test:php` to run PHPUnit in a throwaway `wp-alpine` container
(`ionos-wordpress-test`), replacing the `pnpm exec wp-env run tests-wordpress phpunit`
call that broke when Phase 2 removed `.wp-env.json`.

Real, empirically-confirmed findings that reshaped the design (documented in the code
comments where they land, not just here):

- **Test-suite source**: traced from a real `wp-env` cache on disk (not just source
  reading) that wp-env clones `WordPress/wordpress-develop#trunk` for the test suite -
  a *different* repo from `WORDPRESS_VERSION`'s `WordPress/WordPress` core source, and
  always `trunk` regardless of the core version being tested. `WordPress/WordPress`
  itself has no `tests/` directory at all (verified via a pristine clone) - my first
  attempt assumed the same-source-cloned-twice model docs suggested and it doesn't
  work. Fixed: `scripts/test.sh` now clones `wordpress-develop#trunk` into a fixed
  `${MNT_HOME}/wordpress-tests/trunk` cache (not version-keyed, since it's always
  trunk), reused across runs.
- **PHPUnit version**: Phase 1 pinned `phpunit/phpunit ^11` (to satisfy
  `yoast/phpunit-polyfills`'s constraint solve, itself picked to match this repo's
  `phpunit.xml` 10.5 schema). Running the real suite proved WP core's own
  `wordpress-develop` test harness still calls a PHPUnit method removed in 10+
  (`PHPUnit\Util\Test::parseTestMethodAnnotations`) - exactly what the prototype's own
  Dockerfile comment warned about (WP Trac #59486/#62004). Corrected (with user
  sign-off) to pin `phpunit/phpunit ^9.0` uniformly in
  `packages/docker/wp-alpine/Dockerfile` (dropping the earlier PHP-major branching -
  ^9 works for both 7.4 and 8.x) and downgraded `phpunit/phpunit.xml`'s schema
  reference to 9.6 to match.
- **`--exclude` CLI ambiguity**: PHPUnit 9's CLI treats a bare `--exclude` as
  ambiguous against its own `--exclude-group`/etc. options. Moved the
  stretch-extra exclusion into `phpunit/phpunit.xml`'s `<exclude>` element instead
  (also excludes the now-deleted, gitignored `phpunit/vendor/` leftover from old
  wp-env runs, which the broadened `<directory>` scan below would otherwise pick up).
- **`<directory>` scope**: since `phpunit/` is now bind-mounted as a subdirectory
  (`/htdocs/phpunit`) rather than scattered file-by-file at the WP root like wp-env
  did, `phpunit.xml`'s testsuite `<directory>` changed from `./` to `../` so it still
  scans the whole WP root recursively for `*Test.php` files.
- **wp-tests-config.php wiring**: WP core's test bootstrap looks for
  `wp-tests-config.php` directly inside `$WP_TESTS_DIR` (confirmed via
  `wp-env-after-start.sh`'s `docker cp` destination) - single-file bind-mounted there.
  Its `ABSPATH`/`DB_HOST`/`DB_NAME`/`DB_USER`/`DB_PASSWORD` fallbacks (wp-env's
  `/var/www/html`/`mysql`/placeholder creds) are overridden via `--env
  WORDPRESS_CONFIG_EXTRA`/`WORDPRESS_DB_*` (the file's existing `getenv_docker()`
  hooks - no need to edit the file itself for these).
- **Readiness check**: HTTP-polling (like `start.sh`'s dev-container check) hit an
  infinite redirect loop on port 80 specifically (HTTP clients omit the default port
  from the `Host` header while wp-config's siteurl keeps it explicit - WP's canonical
  redirect never matches). Not an issue for the dev container (published on a
  non-default port), but real here. Switched to `wp core is-installed` instead, which
  is also the semantically correct check anyway - phpunit talks to the DB directly,
  never over HTTP.

**Other file changes**: `phpunit/bootstrap.php`'s composer autoload path updated from
`$HOME/.composer/vendor` (wp-env's runtime-install location) to `/opt/wp-tests/vendor`
(Phase 1's actual bake location). Extracted the mount-discovery logic shared by
`start.sh` and `test.sh` into `scripts/includes/_docker-mounts.sh` (behavior-preserving
refactor of `start.sh`, avoiding a second copy of the plugin/theme/mu-plugin discovery
loops for the test stack).

**Verified end-to-end** (real `pnpm test:php` runs, not just code review): all 15
tests across the 5 non-excluded `*Test.php` files pass; `--php-opts "--filter X"`
passthrough works; the container and `${MNT_HOME}/test` overlay are removed on both
success and an induced real failure (bad CLI flag); the `${MNT_HOME}/wordpress-tests`
cache is reused (not re-cloned) on repeat runs; the persistent dev container from
`pnpm start` is untouched and still serving throughout a `pnpm test:php` run.

**Known, accepted gap** (same as Phase 2's note): `pnpm test:e2e` still calls
`pnpm exec wp-env run tests-cli ...` and `pnpm exec wp-env` (removed in Phase 2) -
Phase 4 rewrites that against the ephemeral test container next.
