<?php

/**
 * Plugin Name:       stretch-extra
 * Description:       stretch-extra provisions additional Stretch hosting specific PHP code into WordPress.
 * Requires at least: 6.6
 * Requires Plugins:
 * Requires PHP:      8.3
 * Version:           1.0.0
 * Plugin URI:        https://github.com/IONOS-WordPress/ionos-wordpress/tree/main/packages/wp-mu-plugin/stretch-extra
 * License:           GPL-2.0-or-later
 * Author:            IONOS Group
 * Author URI:        https://www.ionos-group.com/brands.html
 * Domain Path:       /stretch-extra/languages
 * Text Domain:       stretch-extra
 */

if (!array_key_exists('SFS', $_SERVER)) {
  // Not running on SFS WordPress hosting; do not load extra code.
  return;
}

@include_once '/opt/WordPress/extra/index.php';

