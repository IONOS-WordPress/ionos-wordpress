# About

This plugin tweaks WordPress to allow loading regular plugins from 2 directories:

- the regular WordPress plugin directory (`WP_PLUGIN_DIR`)

- `IONOS_CUSTOM_PLUGINS_DIR`

It works without modifying WordPress Core by just using a few WordPRess hooks and actions.

Custom loaded plugins might not recognized by other WordPress plugins as regular plugins

But it has some caveats:

- if a plugin uses for some reason `WP_PLUGIN_DIR` to retrive the path to a plugin loaded from `IONOS_CUSTOM_PLUGINS_DIR` it will fail

- deactivation / activation works using wp-admin, deletion is disabled

- custom loaded plugins are not updateable using WordPress

- `wp-cli` cannot be used to enable / disable customloaded plugins since `wp-cli` dont _know_ about these plugins

# Todo

- if a plugin is _installed_ as custom plugin it must be prevented to install the same plugin as regular plugin by the user

  Example: if `wordpress-seo` was made available as custom plugin we need to prevent the installation as regular plugin

# Development

- delete custom active plugins :

  ```
  pnpm -s wp-env run cli wp --quiet option delete ionos_custom_plugins_active
  ```

- show error log

  ```
  # show raw log
  pnpm run wp-env run wordpress tail -f /var/www/html/wp-content/debug.log
  # outputs lines like this => [30-Nov-2025 08:36:43 UTC] pa Plugin loaded.


  # show log with stripped date header
  pnpm run wp-env run wordpress tail -f /var/www/html/wp-content/debug.log | sed 's/^\[.*\] //g'
  # outputs lines like this => pa Plugin loaded.
  ```

- `wp-env` : to debug into a plugin you have to add the custom plugin directory to the generated `vscode` launch configuration (located in `.vscode/launch.json`)

  ```
  ...
  "/var/www/html/wp-content/mu-plugins/ionos-core/custom-plugins":"${workspaceFolder}/packages/wp-mu-plugin/ionos-core/custom-plugins",
  ...
  ```

  > The `vscode` launch configuration is every time you execute `pnpm start` regenerated - so you need to add it again manually
