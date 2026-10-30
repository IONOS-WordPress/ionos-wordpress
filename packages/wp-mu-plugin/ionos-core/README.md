# IONOS Core MU-Plugin

- updates itself
- loop functions
- marketplace functions

The plugin consists of the loader `ionos-core.php` and the directory `ionos-core/` (code, languages, assets). The loader
contains the self update and requires `ionos-core/index.php`, which requires all other parts.

## Installation

Download the zip of the release (`ionos-core-<version>-php7.4.zip`, published to the S3 folder by the release pipeline,
see [release](../../../docs/7-release.md)) and extract it into the mu-plugins directory:

```
cd wp-content/mu-plugins
unzip -o ionos-core-<version>-php7.4.zip
```

## Update

Once installed, the plugin updates itself on the `wp_update_plugins` cron event (about every 12 hours) if
`ionos-core.info.json` in its S3 folder announces a newer version (see `ionos-core.php`):

1. the new version is staged next to the live one (`ionos-core.new/`, `ionos-core.php.new`)
2. `ionos-core/` is renamed to `ionos-core.bak/` and `ionos-core.new/` to `ionos-core/`
3. `ionos-core.php.new` is renamed to `ionos-core.php` - this completes the update, `ionos-core.bak/` is deleted

An update that is interrupted at any point (even by a kill) leaves the previous loader with its previous `Version` in
place, so the next update check runs the update again; it first restores `ionos-core/` from `ionos-core.bak/` if needed
and removes all leftovers. While `ionos-core/` is missing, the loader logs an error and loads nothing but the update.
