# About

This directory gets distributed into /opt/WordPress/extra/ on stretch hosting

# wp options

- list of active custom plugins : `IONOS_CUSTOM_ACTIVE_PLUGINS_OPTION`
- list of deleted custom plugins : `IONOS_CUSTOM_DELETED_PLUGINS_OPTION`
- flag to copy extendable theme : `stretch_extra_extendable_theme_dir_initialized`
- APCu object cache enabled : `IONOS_APCU_OBJECT_CACHE_ENABLED_OPTION`

```bash
# reset stretch-extra options will result in running installation again
pnpm wp-env run cli wp option delete IONOS_CUSTOM_ACTIVE_PLUGINS_OPTION IONOS_CUSTOM_DELETED_PLUGINS_OPTION stretch_extra_extendable_theme_dir_initialized
```

```bash
# disable stretch-extra thirdparty plugin activation
# (=> this would result in activating both stretch-extra and real ionos-essentials for example)
pnpm wp-env run cli wp --quiet option update IONOS_CUSTOM_ACTIVE_PLUGINS_OPTION '[]' --format=json
```

## APCu Object Cache

The APCu object cache feature provides persistent in-memory caching for WordPress using the APCu PHP extension.

### Enable APCu object cache

```bash
pnpm wp-env run cli wp --quiet option update IONOS_APCU_OBJECT_CACHE_ENABLED_OPTION '1'
```

### Disable APCu object cache

```bash
pnpm wp-env run cli wp --quiet option delete IONOS_APCU_OBJECT_CACHE_ENABLED_OPTION
```

### Check APCu cache status

```bash
pnpm wp-env run cli wp --quiet option get IONOS_APCU_OBJECT_CACHE_ENABLED_OPTION
```

### Clear APCu cache (requires APCu extension)

This clears the APCu cache without disabling the object cache:

```bash
pnpm wp-env run cli wp --quiet eval "apcu_clear_cache(); echo 'APCu cache cleared';"
```

### Check if object-cache.php drop-in is active

```bash
pnpm wp-env run cli wp --quiet eval "echo file_exists(WP_CONTENT_DIR . '/object-cache.php') ? 'Active' : 'Not active';"
```

**Note**: The APCu extension must be installed and enabled in PHP for the object cache to work. When you enable the cache, the system will copy the `object-cache.php` drop-in file to `WP_CONTENT_DIR`. When you disable it, the drop-in file is removed and the APCu cache is flushed.
