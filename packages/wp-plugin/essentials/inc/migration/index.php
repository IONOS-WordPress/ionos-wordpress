<?php

namespace ionos_wordpress\essentials;

const WP_OPTION_LAST_INSTALLED_VERSION = 'ionos-essentials-last-installed-version';

\register_activation_hook(PLUGIN_FILE, __NAMESPACE__ . '\_install');
\add_action(
  hook_name: 'upgrader_process_complete',
  callback: function(\WP_Upgrader|\stdClass $upgrader_object, array $options) : void {
    if ( $options['action'] !== 'update' || $options['type'] !== 'plugin' ) {
      return;
    }

    $PLUGIN_SLUG = \plugin_basename(PLUGIN_FILE);

    if( in_array($PLUGIN_SLUG, $options['plugins'], true) ) {
      _install();
    }
  },
  accepted_args: 2
);

\register_uninstall_hook(__FILE__,__NAMESPACE__ . '\_uninstall');

function _uninstall() {
  \delete_option(WP_OPTION_LAST_INSTALLED_VERSION);

  // do whatever is needed to cleanup data of this plugin when it gets uninstalled
}

function _install() {
  $last_installed_version = \get_option(WP_OPTION_LAST_INSTALLED_VERSION);
  $current_version = \get_plugin_data(PLUGIN_FILE)['Version'];

  // @TODO: test upgrade mechanism works as expected

  switch ($last_installed_version) {
    case false:
      // first time activation
      // @TODO: on first essential request (from "" to "1.0.0" or later) remove loop, journey & navigation if installed
      break;
    case $current_version:
      // nothing to do
      break;

    //   /*
    //     example migration cases:
    //   */

    // case version_compare($last_installed_version, '1.1.0', '<'):
    //   // do migration from version $last_installed_version -> 1.1.0
    // case version_compare($last_installed_version, '1.2.0', '<'):
    //   // do migration from version 1.1.0 -> 1.2.0
    // case version_compare($last_installed_version, '3.0.0', '<'):
    //   // do migration from version 1.2.0 -> 3.0.0
    //   break;

    //   /* -- */

    default:
      // handle a unknown version or a version that does not need migration
      break;
  }

  if ($last_installed_version === false) {
    \add_option(WP_OPTION_LAST_INSTALLED_VERSION, $current_version);
  } else if($last_installed_version !== $current_version ) {
    \update_option(WP_OPTION_LAST_INSTALLED_VERSION, $current_version);
  }
}
