---
# s6gy
title: Fix stale ionos-support references in the wpdev-caddy plugin header
status: todo
type: task
priority: low
created_at: 2026-09-16T12:49:59Z
updated_at: 2026-09-16T12:49:59Z
---

`packages/wp-plugin/ionos-wpdev-caddy/ionos-wpdev-caddy.php` still carries header values from a previous plugin name (`ionos-support`):

- `Update URI` points at `ionos-support-info.json`
- `Plugin URI` points at `packages/wp-plugin/ionos-support`, a folder that no longer exists

Both are dead references. The package is `"private": true`, so it is never released and the `Update URI` has no effect at all - it only exists because `scripts/lint.sh:486` requires the field to be present and non-empty. This is purely about not making the next reader chase a name that does not exist anywhere else in the repo.

## Todos

- [ ] Point `Update URI` at `ionos-wpdev-caddy-info.json`, matching the name `scripts/release.sh` would derive from the workspace folder
- [ ] Point `Plugin URI` at `packages/wp-plugin/ionos-wpdev-caddy`
- [ ] Leave `"private": true` and add no update checker - making the plugin publicly released is a separate decision
