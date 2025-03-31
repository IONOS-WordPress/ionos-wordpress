this sop component is responsible for handling the migration of the plugin

migration is done incrementally based on the version of the plugin

the plugin saves the current installed plugin version in the wordpress options table.

on activation the plugin checks if the plugin was installed before and if so, it checks which version was installed last.

based on the last installed version it can then run the necessary migration steps incrementally to update the plugin to the current version.

to test the migration, you can reset the current installed version using

```sh
pnpm wp-env run cli wp option delete ionos-essentials-last-install-data
```
