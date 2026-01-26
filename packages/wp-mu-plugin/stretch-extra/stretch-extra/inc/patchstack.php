<?php

// Do not allow the file to be called directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// Do not run if already executed.
if ( defined( 'PS_FW_MU_RAN' ) ) {
    return;
}

// Do not run if we're not supposed to.
if ( get_option( 'patchstack_license_activated', 0 ) == 0 || get_option( 'patchstack_basic_firewall', 0 ) != 1 || get_option( 'patchstack_license_free', 0 ) == 1 ) {
    return;
}

// Load essential WP core file.
require_once ABSPATH . 'wp-admin/includes/plugin.php';

// Determine if the Patchstack plugin folder exists.
$pluginDir = WP_PLUGIN_DIR . '/patchstack';
if ( ! is_dir( $pluginDir ) || ! is_plugin_active( 'patchstack/patchstack.php' ) ) {
    return;
}

// Determine if the core file exists.
if ( ! file_exists( $pluginDir . '/includes/core.php' ) || ! file_exists( $pluginDir . '/includes/firewall.php' ) ) {
    return;
}

// Make sure to load essential files.
if ( ! class_exists( 'P_Firewall' ) || ! class_exists( 'P_Core' ) ) {
    // Require the core and firewall files.
    require_once $pluginDir . '/includes/core.php';
    require_once $pluginDir . '/includes/firewall.php';

    // For rare situations where it did not load properly.
    if ( ! class_exists( 'P_Firewall' ) || ! class_exists( 'P_Core' ) ) {
        return;
    }
}

// Initialize and launch.
try {
    $core = new P_Core( null );
    new P_Firewall( true, $core, false, true );
} catch (\Exception $e) {
    //
}

define( 'PS_FW_MU_RAN', true );
