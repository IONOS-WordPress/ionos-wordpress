<?php

namespace ionos_wordpress\essentials\migration;

use const ionos_wordpress\essentials\PLUGIN_FILE;

/*
 * the migration logic uses an auto loaded option to store the last installed version data
 * this way we can run the migration logic only once after the plugin was installed or updated
 *
 * we don't use the register_activation_hook and upgrader_process_complete hooks to be mu-plugin and stretch compliant
 * in both cases we dont get notified this way.
 * we use instead the admin_init hook to check if the plugin was installed/updated.
 * to make it more efficient we use configure the option to be autoloaded
 */

/**
 * wp option where the installation data is stored
 * the value is a associative array with keys from WP_OPTION_LAST_INSTALL_DATA_KEY_* constants
 * we use a array to be able to store multiple values in the future
 */

const WP_OPTION_LAST_INSTALL_DATA = 'ionos-essentials-last-install-data';

// key to store the plugin version in the installation data
const WP_OPTION_LAST_INSTALL_DATA_KEY_PLUGIN_VERSION = 'plugin-version';

/*
 * we hook our migration into admin-init to check if we were installed/updated
 * and if so, we run the migration.
 *
 * Attention: if our plugin once will take effect in published posts, we should hook into
 * 'init' instead of 'admin-init' to make sure the migration runs always.
 */
\add_action('admin_init', __NAMESPACE__ . '\_install');

// can be left off if no uninstall logic is needed
\register_uninstall_hook(__FILE__, __NAMESPACE__ . '\_uninstall');

function _uninstall()
{
  // if you want to keep it, you can remove the following line
  // keeping it will bloat the wordpress installation load time even if the plugin is not installed anymore
  \delete_option(WP_OPTION_LAST_INSTALL_DATA);

  // do whatever is needed to cleanup data of this plugin when it gets uninstalled
}

function _install()
{
  $last_install_data      = \get_option(WP_OPTION_LAST_INSTALL_DATA, false);
  $last_installed_version = $last_install_data[WP_OPTION_LAST_INSTALL_DATA_KEY_PLUGIN_VERSION] ?? false;
  $current_version        = \get_plugin_data(PLUGIN_FILE)['Version'];

  $current_install_data = [
    WP_OPTION_LAST_INSTALL_DATA_KEY_PLUGIN_VERSION => $current_version,
  ];

  switch ($last_installed_version) {
    // plugin data match current version
    case $current_version:
      // nothing to do
      break;

      // first time activation
    case false:
    case version_compare($last_installed_version, '1.0.0', '<'):
      \uninstall_plugin('ionos-loop/ionos-loop.php');
      \uninstall_plugin('ionos-journey/ionos-journey.php');
      \uninstall_plugin('ionos-navigation/ionos-navigation.php');

      //   /*
      //     example migration cases:
      //   */

      // case version_compare($last_installed_version, '1.1.0', '<'):
      //   // do migration from version $last_installed_version -> 1.1.0
      // case version_compare($last_installed_version, '1.2.0', '<'):
      //   // do migration from version 1.1.0 -> 1.2.0
      // case version_compare($last_installed_version, '3.0.0', '<'):
      //   // do migration from version 1.2.0 -> 3.0.0

      //   /* -- */

      break;

    default:
      // handle a unknown version or a version that does not need migration
      break;
  }

  if (false === $last_installed_version) {
    \add_option(option : WP_OPTION_LAST_INSTALL_DATA, value : $current_install_data, autoload: true);
  } elseif ($last_installed_version !== $current_version) {
    \update_option(option : WP_OPTION_LAST_INSTALL_DATA, value: $current_install_data, autoload: true);
  }
}
