<?php

namespace ionos\stretch_extra\secondary_plugin_dir;

use const ionos\stretch_extra\IONOS_CUSTOM_DIR;

defined('ABSPATH') || exit();

const IONOS_CUSTOM_THEMES_DIR  = IONOS_CUSTOM_DIR . '/themes';

// Hook into the 'init' action. 'plugins_loaded' is also a good option.
\add_action( 'init', function() {
  \register_theme_directory( IONOS_CUSTOM_THEMES_DIR );
});
