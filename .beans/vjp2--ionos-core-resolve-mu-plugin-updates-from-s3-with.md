---
# vjp2
title: 'ionos-core: resolve mu-plugin updates from S3 with GitHub fallback'
status: todo
type: task
priority: normal
created_at: 2026-09-16T12:40:05Z
updated_at: 2026-09-17T11:53:30Z
parent: ig4m
---

`packages/wp-mu-plugin/ionos-core/ionos-core/update/index.php` hardcodes a single GitHub `INFO_JSON_URL` constant and uses it from a `wp_update_plugins` action together with its own `MU_Plugin_Upgrader`.

## Todos

- [ ] Replace the single `INFO_JSON_URL` constant with an S3 constant (carrying the `__S3_FOLDER__` placeholder, substituted by `scripts/build.sh`) plus the existing GitHub constant as fallback
- [ ] Apply the same S3-first / fallback-on-error resolution used by ionos-essentials
- [ ] Make sure the resulting `package` download URL is handed to `MU_Plugin_Upgrader` unchanged, so an S3 info.json leads to an S3 download
- [ ] Extend the `error_log()` messages so it is visible which source answered

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
