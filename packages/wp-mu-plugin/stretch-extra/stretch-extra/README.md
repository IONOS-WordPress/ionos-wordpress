# About

This directory gets distributed into /opt/WordPress/extra/ on stretch hosting

# wp options

- list of active custom plugins : `IONOS_CUSTOM_ACTIVE_PLUGINS_OPTION`
- list of deleted custom plugins : `IONOS_CUSTOM_DELETED_PLUGINS_OPTION`
- flag to copy extendable theme : `stretch_extra_extendable_theme_dir_initialized`

```bash
# reset stretch-extra options will result in running installation again
pnpm wp-env run cli wp option delete IONOS_CUSTOM_ACTIVE_PLUGINS_OPTION IONOS_CUSTOM_DELETED_PLUGINS_OPTION stretch_extra_extendable_theme_dir_initialized
```

```bash
# disable stretch-extra thirdparty plugin activation
# (=> this would result in activating both stretch-extra and real ionos-essentials for example)
pnpm wp-env run cli wp --quiet option update IONOS_CUSTOM_ACTIVE_PLUGINS_OPTION '[]' --format=json
```
