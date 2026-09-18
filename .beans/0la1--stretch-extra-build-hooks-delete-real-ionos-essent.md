---
# 0la1
title: TEST_PRODUCTION phpunit-dir bind mounts let a real plugin update delete tracked test files
status: completed
type: bug
priority: high
created_at: 2026-09-18T10:30:52Z
updated_at: 2026-09-18T10:48:33Z
---

**Root cause found and fixed — this was misdiagnosed initially, see below.**

The real cause has nothing to do with `stretch-extra.sh`'s build hooks. `scripts/includes/_docker-mounts.sh`'s `ionos.wordpress.mount_phpunit_dirs()` (used by `TEST_PRODUCTION=true pnpm start`/`pnpm test`) bind-mounted each package's real, tracked `tests/phpunit` source directory directly at the equivalent path inside the dist-mounted plugin/mu-plugin directory in the container - exactly the directory tree a real plugin-upgrade (WordPress core's `Plugin_Upgrader`, or our own `MU_Plugin_Upgrader`) recursively deletes when installing an update. That recursive delete propagated straight through the bind mount and deleted the real, tracked test files on the host - not a dist copy.

## Reproduction

1. Comment out (or restore) the `ionos-essentials` entry in `packages/wp-mu-plugin/stretch-extra/stretch-extra/inc/stretch-extra-config.php` ('plugins' array), i.e. toggle whether it is present.
2. Run `pnpm build` (rebuilds `stretch-extra`, which runs `scripts/stretch-extra.sh --clean` then `--install` via its `prebuild`/`postbuild` hooks).
3. Check `git status` — these tracked files under the REAL `ionos-essentials` plugin source (not the stretch-extra bundle copy) show as deleted:
   - `packages/wp-plugin/ionos-essentials/ionos-essentials/inc/dashboard/tests/phpunit/ClassNBATest.php`
   - `packages/wp-plugin/ionos-essentials/ionos-essentials/inc/loop/tests/phpunit/LoopTest.php`
   - `packages/wp-plugin/ionos-essentials/ionos-essentials/inc/migration/tests/phpunit/MigrationTest.php`
   - `packages/wp-plugin/ionos-essentials/ionos-essentials/inc/security/tests/phpunit/ClassSecurityTest.php`
   - `packages/wp-plugin/ionos-essentials/ionos-essentials/inc/update/tests/phpunit/UpdateTest.php`
   - `packages/wp-plugin/ionos-essentials/ionos-essentials/inc/wpscan/tests/phpunit/ClassWPScanTest.php`

Reproduced twice in one session this way, but the config toggle turned out to be a red herring - it was coincidental with, not the cause of, an `ionos-essentials` plugin-update attempt (the config toggle exists specifically to get past `stretch-extra`'s unrelated `upgrader_pre_install` block on `ionos-essentials`, in order to test the update - so every occurrence of the toggle in this session was immediately followed by an actual update attempt).

## Actual root cause

`scripts/includes/_docker-mounts.sh`'s `ionos.wordpress.mount_phpunit_dirs()`. `TEST_PRODUCTION=true` mounts a package's `dist/` build output instead of source, but rector's build excludes `tests/` from `dist/` (see `scripts/build.sh`) - so this function bind-mounted each source `tests/phpunit` directory directly at its equivalent path inside the container, so `pnpm test:php` could still find and run them against the production build.

That equivalent path is exactly the plugin/mu-plugin directory tree a real update recursively deletes and recreates - WordPress core's `Plugin_Upgrader` for a `wp-plugin`, or our own `MU_Plugin_Upgrader` (`packages/wp-mu-plugin/ionos-core/ionos-core/update/class-mu-plugin-upgrader.php`) for a `wp-mu-plugin`. That delete walks straight through the nested bind mount and deletes the real, tracked test files on the host, not a disposable dist copy.

Confirmed reproducible directly, no stretch-extra config toggling needed at all: with `TEST_PRODUCTION=true pnpm start` running, `pnpm cli plugin update ionos-essentials` (after clearing the `update_plugins` transient) got as far as its 'Removing the old version of the plugin...' step, which is exactly where it deleted the bind-mounted `tests/phpunit` files - matching `git status` showing the same 6 files deleted, and matching the WP-CLI output ('Could not remove the old plugin' - the _top-level_ plugin directory is itself the dist bind-mount point, which the container cannot rmdir, so only its _contents_, including the nested phpunit bind mounts, actually got deleted).

(`stretch-extra`'s `upgrader_pre_install` block on `ionos-essentials` is real and unrelated - see the 'how this was found' note below - but had to be worked around locally to reach this repro, since it normally prevents any `ionos-essentials` update attempt entirely.)

## Fix

Changed `ionos.wordpress.mount_phpunit_dirs()` to rsync-mirror each source `tests/phpunit` dir into a disposable copy under the package's own gitignored `dist/.phpunit-mirror/` tree, and bind-mount _that_ copy instead of the source directly. A real update's delete now only destroys a copy that gets regenerated on the next `pnpm start`/`pnpm test` - never the tracked source.

Considered and rejected: mounting the source read-only (`:ro`) instead. That also stops the delete from reaching the host, but `packages/docker/wordpress-alpine/docker-entrypoint.sh` does an unconditional `chown -R php:php /htdocs` under `set -eu` on every container start/recreate - which fails (and aborts the whole entrypoint, so the container never comes up) the moment it hits a read-only-mounted file. The mirror-copy approach avoids this entirely, since the mirror is a plain, freshly-chownable directory.

## Verification

With the fix in place, reran the exact repro above (`TEST_PRODUCTION=true pnpm start`, `pnpm cli plugin update ionos-essentials`, same 'Removing the old version...'/'Could not remove the old plugin' failure as before - that generic Plugin_Upgrader limitation on bind-mounted directories is unrelated and unfixed, see caveat below): `git status`/`stat`/`md5sum` on the real source file were all unchanged afterward, while the mirror copy under `dist/.phpunit-mirror/` was emptied instead.

## Remaining caveat (separate, unfixed, lower severity)

`Plugin_Upgrader`'s real-plugin-update flow (for `wp-plugin` packages like `ionos-essentials`) still fails at its final step in `TEST_PRODUCTION` mode with "Could not remove the old plugin" - it cannot `rmdir`/replace the plugin's own top-level directory, since that directory is itself the dist bind-mount point (a structural Docker limitation, not fixed here). Update _detection_ and _download from the resolved URL_ both work correctly; only the final directory-swap fails. This never affected `ionos-core`'s self-update mechanism, since `MU_Plugin_Upgrader::upgrade()` copies files into the existing mu-plugins directory rather than replacing it wholesale - that path (already covered by the mirror fix above too) completes successfully end-to-end.

## Impact

Silently deleted real, tracked PHPUnit test files from a developer's working tree with no warning whenever a real plugin/mu-plugin self-update ran against a `TEST_PRODUCTION=true` stack - easy to miss and could get committed as an unintended deletion. Caught and reverted via `git checkout --` before anything was committed, both times it happened in this session.

## How this was found

While using an isolated `TEST_PRODUCTION=true` WordPress stack to verify the S3-first plugin update mechanism (see epic ig4m), `ionos-essentials`'s update was blocked by `stretch-extra`'s `upgrader_pre_install` filter (a separate, unrelated, pre-existing feature that hardcodes `ionos-essentials` as "already provisioned by your WordPress Hosting" and rejects any install/update attempt for it). Toggling that config entry off locally to get past the block, in order to actually test the S3 update flow, is what led to triggering (and then finding) this issue.
