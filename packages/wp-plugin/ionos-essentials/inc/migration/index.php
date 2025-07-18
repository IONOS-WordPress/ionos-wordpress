<?php

/*
 * the migration logic uses an auto loaded option to store the last installed version data
 * this way we can run the migration logic only once after the plugin was installed or updated
 *
 * we don't use the register_activation_hook and upgrader_process_complete hooks to be mu-plugin and stretch compliant
 * in both cases we dont get notified this way.
 * we use instead the admin_init hook to check if the plugin was installed/updated.
 * to make it more efficient we use configure the option to be autoloaded
 */

namespace ionos\essentials\migration;

use Plugin_Upgrader;
use WP_Ajax_Upgrader_Skin;

use const ionos\essentials\PLUGIN_FILE;
use const ionos\essentials\security\IONOS_SECURITY_FEATURE_OPTION;
use const ionos\essentials\security\IONOS_SECURITY_FEATURE_OPTION_CREDENTIALS_CHECKING;
use const ionos\essentials\security\IONOS_SECURITY_FEATURE_OPTION_DEFAULT;
use const ionos\essentials\security\IONOS_SECURITY_FEATURE_OPTION_PEL;
use const ionos\essentials\security\IONOS_SECURITY_FEATURE_OPTION_XMLRPC;

/*
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
  $last_install_data      = \get_option(WP_OPTION_LAST_INSTALL_DATA);
  // default for first time activation: "0.0.0"
  $last_installed_version = $last_install_data[WP_OPTION_LAST_INSTALL_DATA_KEY_PLUGIN_VERSION] ?? '0.0.0';
  $current_version        = \get_plugin_data(PLUGIN_FILE)['Version'];

  $current_install_data = [
    WP_OPTION_LAST_INSTALL_DATA_KEY_PLUGIN_VERSION => $current_version,
  ];

  switch (true) {
    // plugin data match current version
    case version_compare($last_installed_version, $current_version, '=='):
      // nothing to do
      return;

    case version_compare($last_installed_version, '1.0.0', '<'):

      // keep consent for ionos loop to use it later on in dashboard
      $ionos_loop_consent_given = \get_option('ionos_loop_consent', false);

      $plugins_to_remove = [
        'ionos-loop/ionos-loop.php',
        'ionos-journey/ionos-journey.php',
        'ionos-navigation/ionos-navigation.php',
      ];
      \deactivate_plugins($plugins_to_remove);
      \delete_plugins($plugins_to_remove);

      // re add ionos loop consent data
      \add_option('ionos_loop_consent', $ionos_loop_consent_given);
      // no break because we want to run all migrations sequentially
    case version_compare($last_installed_version, '1.0.4', '<'):
      \update_option('ionos_migration_step', 1);
      // no break
    case version_compare($last_installed_version, '1.0.9', '<'):
      // deactivate and uninstall the ionos-assistant plugin
      \deactivate_plugins('ionos-assistant/ionos-assistant.php');
      \delete_plugins(['ionos-assistant/ionos-assistant.php']);
      update_plugin('ionos-marketplace/ionos-marketplace.php', false);
      \update_option('ionos_migration_step', 2);
      // no break
    case version_compare($last_installed_version, '1.0.10', '<'):
      \deactivate_plugins('ionos-security/ionos-security.php');
      \delete_plugins(['ionos-security/ionos-security.php']);

      $xmlrpc_guard_enabled      = 1 === \get_option('xmlrpc_guard_enabled', 1);
      $pel_enabled               = 1 === get_option('pel_enabled', 1);
      $credentials_check_enabled = 1 === get_option('credentials_check_enabled', 1);

      \delete_option('xmlrpc_guard_enabled');
      \delete_option('pel_enabled');
      \delete_option('credentials_check_enabled');

      // @TODO: migrate wpscan option for mail notification

      $security_options                                                     = IONOS_SECURITY_FEATURE_OPTION_DEFAULT;
      $security_options[IONOS_SECURITY_FEATURE_OPTION_XMLRPC]               = $xmlrpc_guard_enabled;
      $security_options[IONOS_SECURITY_FEATURE_OPTION_PEL]                  = $pel_enabled;
      $security_options[IONOS_SECURITY_FEATURE_OPTION_CREDENTIALS_CHECKING] = $credentials_check_enabled;

      \add_option(IONOS_SECURITY_FEATURE_OPTION, $security_options, '', true);
  }
  \update_option(option: WP_OPTION_LAST_INSTALL_DATA, value: $current_install_data, autoload: true);
}

function update_plugin($plugin_slug, $activate = true)
{
  if (\current_user_can('update_plugins')) {
    include_once ABSPATH . 'wp-admin/includes/plugin.php';
    include_once ABSPATH . 'wp-admin/includes/update.php';
    include_once ABSPATH . 'wp-admin/includes/class-wp-upgrader.php';

    \wp_update_plugins();

    $upgrader = new \Plugin_Upgrader(new \WP_Ajax_Upgrader_Skin());

    $upgrader->upgrade($plugin_slug);
    if ($activate) {
      \activate_plugin($plugin_slug);
    }
  }
}
