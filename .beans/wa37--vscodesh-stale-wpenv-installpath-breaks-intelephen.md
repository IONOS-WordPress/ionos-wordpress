---
# wa37
title: '_vscode.sh: stale WPENV_INSTALLPATH breaks intelephense WordPress-core autocompletion'
status: completed
type: bug
priority: normal
created_at: 2026-08-17T13:38:08Z
updated_at: 2026-08-18T08:05:33Z
parent: qi52
---

scripts/includes/_vscode.sh:84 still generates .vscode/settings.json with:

    "${WPENV_INSTALLPATH:-}/WordPress"

in intelephense.environment.includePaths. WPENV_INSTALLPATH is never set anywhere in the new (non-wp-env) scripts - confirmed via grep across the repo, it only ever came from the deleted wp-env-era start.sh/stop.sh/clean.sh blocks. scripts/_prepare.sh:18's comment ("recreate .vscode/settings.json with a valid WPENV_INSTALLPATH") is itself now stale.

## Impact

Every 'pnpm install' (which runs _prepare.sh) or 'pnpm start' regenerates settings.json with includePaths resolving to a bare '/WordPress' (empty var + ':-' default) instead of the real WordPress core location. Intelephense silently loses WordPress-core symbol resolution for every developer using VS Code - no error, just missing autocomplete and false "undefined function" warnings for WP core functions.

## Fix

Point the include path at wherever WordPress core actually lives now (e.g. ${MNT_HOME}/wordpress-core/<version_dir>, matching what scripts/start.sh / scripts/includes/_docker-mounts.sh use), or drop the stale entry if it's no longer meaningful.

## Location

scripts/includes/_vscode.sh:84
scripts/_prepare.sh:18 (stale comment referencing WPENV_INSTALLPATH)

## Summary of Changes

- `scripts/includes/_vscode.sh`: sources `_docker-mounts.sh` for `ionos.wordpress.wordpress_version_dir()`, computes `wordpress_core_dir="${MNT_HOME}/wordpress-core/$(ionos.wordpress.wordpress_version_dir "$WORDPRESS_VERSION")"` (the same host path `scripts/start.sh` mounts into the dev container), and uses it in `intelephense.environment.includePaths` instead of the dead `${WPENV_INSTALLPATH:-}/WordPress`.
- `scripts/_prepare.sh`: updated the stale comment referencing WPENV_INSTALLPATH.

## Verification

Ran `scripts/_prepare.sh` for real: generated `.vscode/settings.json` now contains `"./mnt/wordpress-core/WordPress-WordPress-7.0.4"` - confirmed that directory exists and contains real WordPress core (`wp-includes/version.php` present).
