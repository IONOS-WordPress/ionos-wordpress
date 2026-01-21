<?php

/*
 * this file is the main entrypoint for stretch-extra
 */

namespace ionos\stretch_extra;

defined('ABSPATH') || exit();

const IONOS_CUSTOM_DIR  = __DIR__;

if (! defined('IONOS_IS_STRETCH')) {
  define('IONOS_IS_STRETCH', strncmp(getcwd(), '/home/www/public', strlen('/home/www/public')) === 0);
}

// Set SFS server variable to fake stretch-extra context
if (! defined('IONOS_IS_STRETCH_SFS') && defined('IONOS_IS_STRETCH')) {
  define('IONOS_IS_STRETCH_SFS', array_key_exists('SFS', $_SERVER));
}

if (! defined('IONOS_IS_STRETCH_SFS')) {
  $_SERVER['SFS'] = 'stretch-extra';
}

require_once __DIR__ . '/inc/migration.php';
require_once __DIR__ . '/inc/secondary-plugin-dir.php';
require_once __DIR__ . '/inc/secondary-theme-dir.php';

