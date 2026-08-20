---
# p3j2
title: Isolate e2e tests via per-test DB snapshot/restore
status: completed
type: feature
priority: normal
created_at: 2026-08-20T08:15:47Z
updated_at: 2026-08-20T09:47:00Z
---

Give each e2e test a clean WordPress DB by snapshotting the ionos-wordpress-test container's MariaDB once after initial provisioning and restoring it before every test, replacing the container-sharding workaround.

## Design

Approach: `mariadb-dump`/`mariadb` reload (fastest, no new deps, already in the wordpress-alpine image). Restore runs once **per spec file**, not once per test - several specs deliberately build up state across the tests in one file via their own `beforeAll` (e.g. secondary-theme-dir.spec.js's deletable/installable pair), so per-test restore broke them. `test.beforeAll` also runs in a phase that precedes all Playwright fixtures (even auto ones), so the restore is a plain function each spec file calls via its own outermost `test.beforeAll(restoreDbOnce)`, not a fixture.

## Tasks

- [x] Add dump/restore helpers (`dumpTestDb()`/`restoreTestDb()`) alongside `playwright/exec-test-cli.js`, running `mariadb-dump`/`mariadb` against `$TEST_CONTAINER_NAME`, snapshot file kept inside the container
- [x] Call the dump helper once at the end of `playwright/e2e/global-setup.js`, right after the existing `setupRest()`/`activateTheme`/`activatePlugin` calls - also snapshots the storage-state cookie file (`playwright/e2e/storage-state.js`), since a spec calling `requestUtils.setupRest()` rotates the DB session token and rewrites that file together
- [x] Add `playwright/e2e/fixtures.js` re-exporting `test`/`expect` from `@wordpress/e2e-test-utils-playwright` plus a plain `restoreDbOnce()` function (not a fixture - see design note above)
- [x] Update all 17 e2e spec files under `packages/*/tests/e2e/*.spec.js` to import `test`/`expect`/`restoreDbOnce` from the new fixtures module and call `test.beforeAll(restoreDbOnce)` as the file's outermost hook
- [x] Update `docs/5-test.md`'s "e2e test isolation" section to describe the restore-once-per-file mechanism
- [x] Verify: full `pnpm run test` (react + PHPUnit + e2e) passes, 28/28 e2e tests green, restore overhead negligible (~1s/file)

## Summary of Changes

Implemented per-file DB snapshot/restore for e2e test isolation:

- `playwright/exec-test-cli.js`: added `dumpTestDb()`/`restoreTestDb()` using `mariadb-dump`/`mariadb` against the `wordpress` DB user (no root needed), snapshot kept at `/tmp/e2e-db-snapshot.sql` inside the container.
- `playwright/e2e/storage-state.js`: new shared module for the storage-state path constants, used by `playwright.config.js`, `global-setup.js` and `fixtures.js`.
- `playwright/e2e/global-setup.js`: dumps the DB and snapshots the storage-state cookie file once, right after login/theme/plugin setup.
- `playwright/e2e/fixtures.js`: re-exports `test`/`expect` plus a plain `restoreDbOnce()` function.
- All 17 e2e spec files: call `test.beforeAll(restoreDbOnce)` as their outermost hook.

Key design pivot during implementation: originally planned a per-TEST restore via a Playwright beforeEach/auto-fixture, but this broke several specs that intentionally build up state across tests in one file (secondary-theme-dir.spec.js's deletable->installable, welcome.spec.js's dismiss->still-closed) - discovered because test.beforeAll runs in a phase that precedes even auto fixtures, so a fixture-based restore either ran too late (after the file's own beforeAll, wiping its setup) or, once forced to run before every test via workarounds, wiped out legitimate cross-test-within-a-file state. Settled on restoring once per spec FILE via a plain test.beforeAll(restoreDbOnce) as each file's outermost hook, which exactly matches the original cross-FILE leakage problem (welcome.spec.js vs tabs/maintenance/security-options) without breaking intra-file sequencing.

Also discovered and fixed: a spec calling requestUtils.setupRest() (e.g. maintenance.spec.js's logout/login-back-in) rotates the DB session token and rewrites the storage-state cookie file; restoring only the DB left the next file's browser context holding a stale-but-different cookie ("Not logged in" errors) - fixed by snapshotting/restoring the storage-state file in lockstep with the DB.

Verified: full `pnpm run test` (react + PHPUnit + e2e) passes twice in a row, 28/28 e2e tests green both times (one earlier run had a single flaky test due to a transient MySQL duplicate-key race from a lingering background WP request, self-healed by Playwright's existing retry config).

Review follow-up (DB credentials): `playwright/exec-test-cli.js` originally hardcoded `-u wordpress -ppassword wordpress`. Note that `.env`/`.secrets` contain no DB credentials at all - the values are baked into the wordpress-alpine image (`Dockerfile` `GRANT ... IDENTIFIED BY 'password'` and `docker-entrypoint.sh` `wp config create --dbuser/--dbpass`, which ignores `WORDPRESS_DB_*`), and `scripts/test.sh` passes `--env WORDPRESS_DB_*` only so `phpunit/wp-tests-config.php`'s `getenv_docker()` resolves. The JS copy was removable and has been removed: `DB_CREDENTIALS` now holds `-u "$WORDPRESS_DB_USER" -p"$WORDPRESS_DB_PASSWORD" "$WORDPRESS_DB_NAME"` unexpanded, and both `dumpTestDb()`/`restoreTestDb()` run via `sh -c` so the container resolves them. Verified by a dump/restore round-trip inside a running container. Making the image itself honour `WORDPRESS_DB_*` (true single source of truth) was considered and deliberately left out of this bean - it touches the shared image and the dev stack; worth a follow-up bean.

Follow-up implemented in this bean: the wordpress-alpine image now honours `WORDPRESS_DB_*` instead of hardcoding credentials.

- `Dockerfile`: the build-time `GRANT ALL PRIVILEGES ON wordpress.* ... IDENTIFIED BY 'password'` (and the mariadbd it started just to run it) is gone; the MySQL step is now only `mariadb-install-db --datadir=/data --user=mysql`, which leaves `root@localhost` on unix_socket auth. The image ships with **no** wordpress db user at all.
- `docker-entrypoint.sh`: added a `WORDPRESS_DB_HOST/NAME/USER/PASSWORD` default block (`localhost`/`wordpress`/`wordpress`/`password`) - the single place the defaults are spelled out. After MariaDB comes up, an idempotent `GRANT ... IDENTIFIED BY` (as OS root over the socket) creates/refreshes the user from those variables. `wp config create` now passes `--dbname/--dbhost/--dbuser/--dbpass` from them, and the "does the database already exist" probe uses root over the socket with `$WORDPRESS_DB_NAME` instead of `mariadb -u root -p'password'`.
- Net effect: `scripts/test.sh`'s `--env WORDPRESS_DB_*` are now authoritative rather than having to mirror values baked into the image, and `playwright/exec-test-cli.js` expanding those same variables in-container is consistent by construction. `.env`/`.secrets` deliberately still carry no DB credentials (the DB only ever listens on the container's own socket).

Verified against a locally rebuilt image:

- custom values (`WORDPRESS_DB_NAME=customdb`, `USER=customuser`, `PASSWORD=cu5t0m-p4ss`): container installs cleanly, wp-config.php holds exactly those values, `mysql.user` contains `customuser` and no `wordpress` user, and the exec-test-cli dump/restore round-trip works (102KB snapshot, `wp option get siteurl` fine afterwards).
- `docker restart` of that container: GRANT re-run is a no-op, takes the "Database already exists" branch, no errors - so an existing `/data` (or a container from an older image that baked the user in) keeps working.
- no `WORDPRESS_DB_*` passed: defaults reproduce the previous `wordpress`/`password`/`wordpress` setup, so the dev stack (`scripts/start.sh` passes none) and `phpunit/wp-tests-config.php`'s `getenv_docker()` fallbacks are unaffected.
- full `pnpm run test`: 28/28 e2e green; `pnpm test:php` 15/15 (40 assertions) OK.

Note: both image files changed, so the wordpress-alpine image tag changes - CI needs the publish workflow to build the new tag (the existing pull-retry/local-build fallback in test.sh covers the race). A running dev container must be restarted to pick up the new image.
