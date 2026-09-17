---
# p3cu
title: Make the ionos-core mu-plugin update rollback capable
status: draft
type: feature
priority: normal
created_at: 2026-09-17T11:56:09Z
updated_at: 2026-09-17T11:56:09Z
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
