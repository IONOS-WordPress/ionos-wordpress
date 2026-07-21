<?php

/*
 * this file is the main entrypoint for stretch-extra
 */

namespace ionos\stretch_extra;

defined('ABSPATH') || exit();

const IONOS_CUSTOM_DIR  = __DIR__;

/** Path where the handler symlink is created on activation. */
const MAINTENANCE_HANDLER_LINK_PATH = WP_CONTENT_DIR . '/maintenance.php';

if (! defined('IONOS_IS_STRETCH')) {
  define('IONOS_IS_STRETCH', str_starts_with(getcwd(), '/home/www/public'));
}

// Set SFS server variable to fake stretch-extra context
if (! defined('IONOS_IS_STRETCH_SFS') && defined('IONOS_IS_STRETCH')) {
  define('IONOS_IS_STRETCH_SFS', array_key_exists('SFS', $_SERVER));
}

if (! defined('IONOS_IS_STRETCH_SFS')) {
  $_SERVER['SFS'] = 'stretch-extra';
}

\add_action('plugins_loaded', function () {
  \load_muplugin_textdomain(domain: 'stretch-extra', mu_plugin_rel_path: 'stretch-extra/languages/');
});

require_once __DIR__ . '/inc/maintenance/index.php';

if (file_exists(MAINTENANCE_HANDLER_LINK_PATH)) {
  include_once MAINTENANCE_HANDLER_LINK_PATH;
}

require_once __DIR__ . '/inc/migration.php';
require_once __DIR__ . '/inc/secondary-plugin-dir.php';
require_once __DIR__ . '/inc/secondary-theme-dir.php';
require_once __DIR__ . '/inc/apcu.php';
require_once __DIR__ . '/inc/marketplace/marketplace.php';
require_once __DIR__ . '/inc/plugin-block-list/plugin-block-list.php';
require_once __DIR__ . '/inc/modify-commands/modify-commands-plugins.php';
require_once __DIR__ . '/inc/modify-commands/modify-commands-themes.php';
