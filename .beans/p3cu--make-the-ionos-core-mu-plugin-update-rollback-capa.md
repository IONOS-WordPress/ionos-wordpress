---
# p3cu
title: Make the ionos-core mu-plugin update rollback capable
status: draft
type: feature
priority: normal
created_at: 2026-09-17T11:56:09Z
updated_at: 2026-09-17T11:58:55Z
---

`MU_Plugin_Upgrader` (`packages/wp-mu-plugin/ionos-core/ionos-core/update/class-mu-plugin-upgrader.php`) overwrites the installed mu-plugin in place and keeps no copy of what it replaced:

```php
$result = \copy_dir($working_dir, WPMU_PLUGIN_DIR);
```

If that copy fails halfway, or if the downloaded version turns out to be broken, there is nothing to go back to. A regular plugin update restores the previous version in that situation; this one cannot.

## Why this matters more for a mu-plugin than for a regular plugin

Must-use plugins are always active and load before everything else. There is no deactivation escape hatch in the admin, so a broken mu-plugin can take the whole site down, including the screen an administrator would use to fix it. The update also runs unattended from the `wp_update_plugins` cron event, so nobody is watching when it happens.

## Goal

After a failed update the site runs the version it ran before, without manual intervention.

## Approaches to evaluate

- **Reuse core's mechanism.** Since WordPress 6.3 `WP_Upgrader::run()` can take `hook_extra.temp_backup`, which stores the current directory under `wp-content/upgrade-temp-backup/` and restores it when the update fails. `MU_Plugin_Upgrader` currently bypasses `run()` entirely and calls `download_package()`, `unpack_package()` and `copy_dir()` by hand, so it gets none of this. Check whether `temp_backup` supports a destination outside `plugins`/`themes`; if it does, this is the smallest and best-supported change.
- **Hand-rolled backup.** Copy the installed files aside before `copy_dir()` and restore them when it returns a `WP_Error`. More code and a second place to get the filesystem handling right, but no dependency on core internals.

## Open questions for refinement

- **What counts as a failure?** A `WP_Error` from `copy_dir()` is the easy case. A copy that succeeds while the new version is broken at runtime is the dangerous one, and it needs something more - for example a marker option written before the update and cleared once the new version has loaded successfully, so the following request can detect that the previous load never completed and restore.
- How many previous versions are kept, and who cleans them up?
- Should a rollback be reported somewhere an operator sees it, rather than only `error_log()`?
- Does the same weakness apply to any other mu-plugin shipped from this repository?

## Related

Independent of the S3 migration ([[serve-plugin-updates-from-s3-instead-of-github-releases]]), but the two touch the same file: that work makes the updater resolve its descriptor from S3, this one changes how the descriptor is applied.

## Robustness bar : match the regular plugin update path

The guiding requirement is not just "add a rollback" but that updating `ionos-core` should be about as safe as updating a regular plugin. `Plugin_Upgrader::upgrade()` runs everything through `WP_Upgrader::run()` and gets a set of safeguards that the hand-rolled `MU_Plugin_Upgrader` currently has none of, because it calls `download_package()`, `unpack_package()` and `copy_dir()` directly and never enters `run()`:

- **temp backup and restore on failure** - `hook_extra.temp_backup` moves the installed version to `wp-content/upgrade-temp-backup/` before overwriting, restores it on `shutdown` when the update failed, and deletes it on success. Core also schedules a cleanup task for leftovers.
- **`clear_destination`** - the old files are deleted through the `upgrader_clear_destination` filter rather than being copied over, so files removed in the new version do not linger.
- **`upgrader_pre_install` / `upgrader_post_install` filters** - the documented extension points other code expects to be able to hook.
- **package signature verification** - `verify_file_signature()` is part of the download path in `run()`.
- **maintenance mode** - `WP_Upgrader::maintenance_mode()` keeps visitors off a half-written installation. Note this is called by the *callers* of `run()`, not by `run()` itself, so it has to be invoked explicitly.
- **locking** - `WP_Upgrader::create_lock()` / `release_lock()` prevent two update runs from racing. Also caller-invoked, and relevant here because the update is triggered from cron.
- **structured error reporting** through the skin and `$this->result`, instead of a bare `error_log()`.

Reaching parity is likely more valuable than bolting a backup onto the current code, and it is less code, since `run()` does the orchestration.

## Two findings from reading the core source

Checked against `mnt/wordpress-core/.../wp-admin/includes/class-wp-upgrader.php`:

1. **The `'plugins'`/`'themes'` restriction on `temp_backup` is documentation, not code.** The docblock of `move_to_temp_backup_dir()` says `$dir` "Accepts 'plugins' or 'themes'", but the implementation only uses it as a folder name (`$sub_dir = $dest_dir . $args['dir'] . '/'`), and `restore_temp_backup()` mirrors that. Passing `'dir' => 'mu-plugins'` with `'src' => WPMU_PLUGIN_DIR` would very probably work. Being undocumented behaviour, it needs a test and a note that a core change could break it.

2. **The real mismatch is the layout, not the directory name.** Both functions assume what is backed up is a single subdirectory named `$slug` inside `$src`, and restore with `move_dir()`. The mu-plugin does not have that shape: `scripts/build.sh` deliberately zips mu-plugins *without* a plugin-named wrapper folder, so `copy_dir($working_dir, WPMU_PLUGIN_DIR)` lands a loader file `ionos-core.php` **and** a directory `ionos-core/` side by side at the top level of `mu-plugins/`. That is two top-level entries, one of them a bare file. Any reuse of `temp_backup` has to account for this - and whichever approach is chosen has to back up and restore both entries atomically, or a rollback can leave a new loader next to an old directory.
