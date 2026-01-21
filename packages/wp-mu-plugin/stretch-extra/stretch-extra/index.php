<?php

/*
 * this file is the main entrypoint for stretch-extra
 */

namespace ionos\stretch_extra;

defined('ABSPATH') || exit();

const IONOS_CUSTOM_DIR  = __DIR__;


if (! defined('IONOS_IS_STRETCH')) {
    echo("huhu1");
  define('IONOS_IS_STRETCH', strncmp(getcwd(), '/home/www/public', strlen('/home/www/public')) === 0);
}
echo("huhu2");
// Set SFS server variable to fake stretch-extra context
if (! defined('IONOS_IS_STRETCH_SFS') && defined('IONOS_IS_STRETCH')) {
    echo("huhu3");
  define('IONOS_IS_STRETCH_SFS', array_key_exists('SFS', $_SERVER));
}
echo("huhu4");
if (! defined('IONOS_IS_STRETCH_SFS')) {
    echo("huhu5");
  $_SERVER['SFS'] = 'stretch-extra';
}

require_once __DIR__ . '/inc/migration.php';
require_once __DIR__ . '/inc/secondary-plugin-dir.php';
require_once __DIR__ . '/inc/secondary-theme-dir.php';

