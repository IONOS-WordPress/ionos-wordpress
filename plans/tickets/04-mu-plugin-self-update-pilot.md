# Ticket 4 — `test-mu-plugin` self-update pilot

Status: TODO
Depends on: Ticket 1 (needs the generalized release pipeline to produce `test-mu-plugin`'s
`-info.json` reliably alongside essentials)
Parent plan: [../generalize-plugin-release-mechanism.md](../generalize-plugin-release-mechanism.md) (section 6)
Jira: sub-task of [GPHWPP-4402](https://hosting-jira.1and1.org/browse/GPHWPP-4402)

## Goal

Give `packages/wp-mu-plugin/test-mu-plugin` a small, stable self-update mechanism, piloting what
mu-plugin self-update can look like given WordPress core has no built-in equivalent for
must-use plugins. This is a scoped pilot exception — `stretch-extra` and future mu-plugins stay
download-only by default; this ticket does not change that default policy.

## Scope

New file:

- `packages/wp-mu-plugin/test-mu-plugin/test-mu-plugin/inc/update/index.php`

Modified:

- `packages/wp-mu-plugin/test-mu-plugin/test-mu-plugin.php` — add `Update URI` header, `require_once`
  the new file.

## Design (reuse WordPress core primitives — keep custom code to the update logic only)

- **Trigger:** hook the existing `wp_update_plugins` cron action (`add_action('wp_update_plugins', ...)`)
  — do **not** add custom `wp_schedule_event()` scheduling (mu-plugins have no activation hook to
  bootstrap one from anyway; WP already fires this twice daily).
- **Version source:** fetch `<plugin>-info.json` from the `Update URI` header via `wp_remote_get()`
  (same convention/URL shape as essentials).
- **URL source:** read `get_plugin_data(PLUGIN_FILE)['UpdateURI']` — generic header parsing,
  works the same regardless of plugin type.
- **Version compare:** `get_plugin_data(PLUGIN_FILE)['Version']` vs. info.json's `version` via
  `version_compare()`.
- **Download + install:** `require_once ABSPATH . 'wp-admin/includes/file.php';` then
  `download_url()` → `WP_Filesystem()` + `unzip_file()` → extract to a temp dir, then swap in with
  two `$wp_filesystem->move($from, $to, true)` calls (one file, one companion folder) rather than
  a recursive `copy_dir()` — smaller and closer to atomic.
- **Safety net:** rename current file/folder to `.bak` before swapping in the new ones; delete the
  backup only after confirming `get_plugin_data()` reports the expected new version; auto-restore
  from `.bak` on failure.
- **Observability:** single `update_option('test_mu_plugin_last_update_check', [...])` call
  storing timestamp/prev-version/new-version/success-or-failure — no admin UI.

## Known limitation (document, don't solve here)

`WP_Filesystem()` may require FTP/SSH credentials on hosts where PHP doesn't own the files
directly — no UI to supply them in a cron context, so the update silently no-ops and logs an
error on such hosts. Acceptable for a pilot; flag before promoting this pattern beyond
`test-mu-plugin`.

## Acceptance criteria

- [ ] `test-mu-plugin.php` has an `Update URI` header pointing at its `-info.json`.
- [ ] Publishing a bumped version and firing `wp_update_plugins` (e.g. via
      `wp cron event run wp_update_plugins`) replaces the mu-plugin's files and the new version is
      live, confirmed via `get_plugin_data()`.
- [ ] A simulated broken build triggers the `.bak` auto-restore path instead of leaving the site
      fataling on every page load.
- [ ] `test_mu_plugin_last_update_check` reflects each check's outcome.
- [ ] `stretch-extra` and the general mu-plugin docs are untouched — this stays a scoped
      exception, not a new default policy.

## Notes

Do not start this ticket until Ticket 1 has landed and a real `test-mu-plugin` release has gone
through the generalized pipeline at least once.
