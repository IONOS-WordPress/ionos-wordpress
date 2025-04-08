<?php

/**
 * Plugin Name:       ionos-essentials
 * Description:       The essentials plugin provides IONOS hosting specific functionality.
 * Requires at least: 6.6
 * Requires Plugins:
 * Requires PHP:      8.3
 * Version:           0.2.0
 * Update URI:        https://api.github.com/repos/IONOS-WordPress/ionos-wordpress/releases
 * Plugin URI:        https://github.com/IONOS-WordPress/ionos-wordpress/tree/main/packages/wp-plugin/essentials
 * License:           GPL-2.0-or-later
 * Author:            IONOS Group
 * Author URI:        https://www.ionos-group.com/brands.html
 * Domain Path:       /languages
 * Text Domain:       ionos-essentials
 */

namespace ionos\essentials;

const PLUGIN_FILE = __FILE__;
const PLUGIN_DIR  = __DIR__;

defined('ABSPATH') || exit();

\add_action(
  'init',
  fn () => \load_plugin_textdomain(domain: 'ionos-essentials', plugin_rel_path: basename(__DIR__) . '/languages/')
);

require_once __DIR__ . '/inc/update/index.php';

// features
require_once __DIR__ . '/inc/switch-page/index.php';
require_once __DIR__ . '/inc/dashboard/index.php';

// soc plugin components
require_once __DIR__ . '/inc/migration/index.php';
