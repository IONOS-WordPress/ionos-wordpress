<?php

namespace ionos\stretch_extra\secondary_plugin_dir;

// Prevent execution if not in WP-CLI
if (!defined('WP_CLI') || !WP_CLI) {
    return;
}

if (!defined('IONOS_CUSTOM_DIR')) {
    return;
}

$custom_theme_path = IONOS_CUSTOM_DIR . '/themes';

/**
 * Redirect theme root. Ignore /opt/WordPress/
 * Look only at mounted directory.
 */
\add_filter('theme_root', function($theme_root) use ($custom_theme_path) {
    return $custom_theme_path;
}, 999);

/**
 * Override theme roots. Ignore /opt/WordPress/.
 * Only the mounted path is registered.
 */
\add_filter('theme_roots', function($roots) use ($custom_theme_path) {
    // We return an array where only our custom path exists.
    return array(
        'themes' => $custom_theme_path
    );
}, 999);

/**
 * Scan the mounted folder for style.css headers.
 */
\register_theme_directory($custom_theme_path);

/**
 * Ensures that if WP-CLI tries to verify the theme root, it sees our path.
 */
\add_filter('pre_option_stylesheet_root', function() use ($custom_theme_path) {
    return $custom_theme_path;
});

\add_filter('pre_option_template_root', function() use ($custom_theme_path) {
    return $custom_theme_path;
});
