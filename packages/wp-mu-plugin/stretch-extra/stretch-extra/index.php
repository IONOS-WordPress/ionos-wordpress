<?php

/*
 * this file is the main entrypoint for stretch-extra
 */

namespace ionos\stretch_extra;

defined('ABSPATH') || exit();

const IONOS_CUSTOM_DIR  = __DIR__;

require_once __DIR__ . '/inc/migration.php';
require_once __DIR__ . '/inc/secondary-plugin-dir.php';
require_once __DIR__ . '/inc/secondary-theme-dir.php';
