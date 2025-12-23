# About

This directory gets distributed into /opt/WordPress/extra/ on stretch hosting

# wp options

- list of active custom plugins : `IONOS_CUSTOM_ACTIVE_PLUGINS_OPTION`
- flag to copy extendable theme : `stretch_extra_extendable_theme_dir_initialized`

```bash
# reset stretch-extra options will result in running installation again
pnpm wp-env run cli wp option delete IONOS_CUSTOM_ACTIVE_PLUGINS_OPTION stretch_extra_extendable_theme_dir_initialized
```
