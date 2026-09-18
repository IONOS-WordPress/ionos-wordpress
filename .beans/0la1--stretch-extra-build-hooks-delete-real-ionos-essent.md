---
# 0la1
title: stretch-extra build hooks delete real ionos-essentials test files
status: todo
type: bug
priority: high
created_at: 2026-09-18T10:30:52Z
updated_at: 2026-09-18T10:31:15Z
---

Toggling the ionos-essentials entry in stretch-extra-config.php and rebuilding deletes tracked test files from the real plugin source tree, not just the bundled copy.

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

Reproduced twice in one session (once removing the entry, once restoring it) with the exact same 6 files both times. A same-config rebuild (no toggle) did NOT reproduce it - the deletion seems tied to the config *changing*, not to `pnpm build` in general.

## What does NOT explain it (ruled out)

- `scripts/stretch-extra.sh`'s two cleanup lines only target the bundle copy by path pattern and don't match the real source path:
  `find . -path "*/stretch-extra/stretch-extra/plugins/*" -name "*Test.php" -delete`
  (same for `themes/*`) - neither pattern contains any real-source path segment.
- `ionos.wordpress.stretch-extra.clean()`'s `rm -rf "$dir"` only removes real (non-symlink, per `find -type d` without `-L`) top-level dirs directly under the stretch-extra bundle path (e.g. `packages/wp-mu-plugin/stretch-extra/stretch-extra/plugins/ionos-essentials`), confirmed via `git ls-files`/`.gitignore` that this bundle dir is untracked/regenerated, not a symlink into the real source.
- `scripts/build.sh`'s rsync into `dist/` for essentials itself is one-directional (source -> dist, with `--exclude=tests/`) and doesn't run in this scenario anyway - the build log showed essentials' own build step was skipped ('already up to date') both times the deletion occurred, so essentials' own build machinery isn't rebuilding/touching itself.
- The stretch-extra `--install` step's `unzip` extracts the essentials dist zip (which already excludes `tests/`) into the bundle path, not into the real source path.

## Open questions for whoever picks this up

- Is there a hard link (not symlink) between the bundle copy and the real source, created by some earlier/different code path (e.g. a one-time manual dev-container setup step), such that unlinking one path's directory entry could look like this? (Hard-linked directories aren't normally possible on Linux/ext4, but individual hard-linked *files* are - worth checking `stat --format='%h'` / inode numbers on affected files before reproducing again.)
- Is `packages/docker/wordpress-alpine`'s build or any `pnpm -w run stretch-extra` invocation from a different script (`packages/wp-mu-plugin/stretch-extra/scripts/postpack.sh` also calls `--clean`/`--install` again) doing something extra not visible in `scripts/stretch-extra.sh` itself?
- Reproduce again with `bash -x` tracing enabled on `scripts/stretch-extra.sh` and `packages/wp-mu-plugin/stretch-extra/scripts/postpack.sh` to catch the exact command that touches the real source path.

## Impact

Silently deletes real, tracked PHPUnit test files from a developer's working tree with no warning - easy to miss and could get committed as an unintended deletion (a git commit after this would silently drop the test files from what gets committed, unless the developer notices `git status` first). Both occurrences in this session were caught and reverted via `git checkout --` before anything was committed.

## How this was found

While using an isolated `TEST_PRODUCTION=true` WordPress stack to verify the S3-first plugin update mechanism (see epic ig4m), `ionos-essentials`' update was blocked by `stretch-extra`'s `upgrader_pre_install` filter (unrelated pre-existing feature, hardcodes ionos-essentials as 'already provisioned by your WordPress Hosting'). Toggling it off in `stretch-extra-config.php` to test past the block is what triggered this.
