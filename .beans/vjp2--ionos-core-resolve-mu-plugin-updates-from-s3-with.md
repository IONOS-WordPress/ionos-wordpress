---
# vjp2
title: 'ionos-core: resolve mu-plugin updates from S3 with GitHub fallback'
status: todo
type: task
priority: normal
created_at: 2026-09-16T12:40:05Z
updated_at: 2026-09-16T12:40:05Z
parent: ig4m
---

`packages/wp-mu-plugin/ionos-core/ionos-core/update/index.php` hardcodes a single GitHub `INFO_JSON_URL` constant and uses it from a `wp_update_plugins` action together with its own `MU_Plugin_Upgrader`.

## Todos

- [ ] Replace the single `INFO_JSON_URL` constant with an S3 constant (carrying the `__S3_FOLDER__` placeholder) plus the existing GitHub constant as fallback
- [ ] Apply the same S3-first / fallback-on-error resolution used by ionos-essentials
- [ ] Make sure the resulting `package` download URL is handed to `MU_Plugin_Upgrader` unchanged, so an S3 info.json leads to an S3 download
- [ ] Extend the `error_log()` messages so it is visible which source answered

## Notes

Must-use plugins have no `Update URI` mechanism in WordPress core, so there is no header to change here and no hook registration to duplicate - only the URL resolution changes.
