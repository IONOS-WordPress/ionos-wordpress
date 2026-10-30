<?php

/*
 * entry point of the ionos-core plugin code, required by the loader ../ionos-core.php.
 * every part of the plugin is required from here, so the loader never needs to know them (see ../ionos-core.php).
 */

namespace ionos\ionos_core;

defined('ABSPATH') || exit();

// mu-plugins have no automatic text domain path : the languages ship inside this directory.
// loaded on 'init' (not while mu-plugins load) so the locale of the current user is known
\add_action('init', fn () => \load_muplugin_textdomain('ionos-core', basename(__DIR__) . '/languages'));

require_once __DIR__ . '/marketplace/index.php';
require_once __DIR__ . '/loop/index.php';
