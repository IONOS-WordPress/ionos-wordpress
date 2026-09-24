---
# vjp2
title: 'ionos-core: resolve mu-plugin updates from S3 with GitHub fallback'
status: completed
type: task
priority: normal
created_at: 2026-09-16T12:40:05Z
updated_at: 2026-09-22T09:11:36Z
parent: ig4m
---

`packages/wp-mu-plugin/ionos-core/ionos-core/update/index.php` hardcodes a single GitHub `INFO_JSON_URL` constant and uses it from a `wp_update_plugins` action together with its own `MU_Plugin_Upgrader`.

## Todos

- [x] Replace the single `INFO_JSON_URL` constant with an S3 constant (carrying the `__S3_FOLDER__` placeholder, substituted by `scripts/build.sh`) plus the existing GitHub constant as fallback
- [x] Apply the same S3-first / fallback-on-error resolution used by ionos-essentials
- [x] Make sure the resulting `package` download URL is handed to `MU_Plugin_Upgrader` unchanged, so an S3 info.json leads to an S3 download
- [x] Extend the `error_log()` messages so it is visible which source answered

## Notes

Must-use plugins have no `Update URI` mechanism in WordPress core, so there is no header to change here and no hook registration to duplicate - only the URL resolution changes.

## How the existing mechanism works

Worth knowing before touching it, because it shares nothing with the wp-plugin update path:

WordPress core has no update mechanism for must-use plugins - no `Update URI` header, no `update_plugins_<host>` filter, no entry in the updates screen. `ionos-core` implements its own:

1. hooks the `wp_update_plugins` cron event (roughly twice daily)
2. fetches the hardcoded `INFO_JSON_URL` with a 5 second timeout
3. parses the body strictly (`JSON_THROW_ON_ERROR`) and reads `version` and `package`
4. reads its own version from the `Version` header of `ionos-core.php` via `get_file_data()`
5. if the remote version is higher, runs `MU_Plugin_Upgrader->upgrade($package)`, which subclasses `WP_Upgrader` with an `Automatic_Upgrader_Skin`: download, unpack, `copy_dir()` straight into `WPMU_PLUGIN_DIR`, then delete the working directory

Consequences for this bean:

- There is no `Update URI` header to read, so the rule established for ionos-essentials ("query the header first, the legacy url second") does not apply. Two constants are needed, S3 first and GitHub as fallback.
- The plugin installs the update itself rather than handing a descriptor to WordPress, and it does the version comparison itself. A descriptor that resolves to a bad `package` url is applied without anyone confirming it, and `copy_dir()` overwrites in place with no rollback. That raises the stakes on the fallback logic: prefer returning no update over returning a half-parsed one.

## Summary of Changes

Rewrote `packages/wp-mu-plugin/ionos-core/ionos-core/update/index.php`:

- Renamed the sole `INFO_JSON_URL` constant to point at S3 (`https://s3-de-central.profitbricks.com/web-hosting/__S3_FOLDER__/ionos-core-info.json`), kept the previous GitHub URL as `LEGACY_INFO_JSON_URL`.
- Extracted a `fetch_update_info()` helper that tries S3 then GitHub (deduplicated), mirroring ionos-essentials' `fetch_update_info()`: skips a source on transport error, non-200 status, empty body, invalid JSON, or a JSON body missing `version`/`package`, and returns `null` if both sources fail (never applies a half-parsed update).
- Logs which URL was tried/failed/answered via `error_log()`, matching the essentials message format plus a new success line naming the source that resolved.
- `MU_Plugin_Upgrader->upgrade($package)` still receives the `package` URL from the resolved info untouched, so an S3-sourced descriptor downloads from S3.
- No build.sh change needed: the existing `__S3_FOLDER__` substitution in `ionos.wordpress.build_workspace_package_wp_plugin` already runs generically over any staged package (wp-plugin and wp-mu-plugin alike).
- Added `update/tests/phpunit/UpdateTest.php` PHPUnit coverage for `fetch_update_info()` (source precedence, malformed/incomplete json handling); ionos-essentials' update mechanism gained equivalent coverage too.
