<?php
 
/**
 * Return list of disallowed plugin slugs
 */
function get_disallowed_plugins() {
    return [
        'bwp-minify/bwp-minify.php' => 'BWP Minify (as of 1.3.3) is not ready for use. The plugin writes a configuration file that must be edited manually to support plugins and themes installed via symlinks. Because it breaks sites upon activation, we have automatically deactivated the plugin to keep your site working. In the interest of making BWP Minify compatible, we provided <a href="https://github.com/OddOneOut/bwp-minify/pull/67">this patch</a> to the author in May 2016. If you choose to fix the configuration file yourself, you may skip the automatic deactivation by renaming <code>bwp-minify/bwp-minify.php</code>.',
        'e-mail-broadcasting/e-mail-broadcasting.php' => 'The use of "E-Mail Broadcasting" is not allowed.',
        'send-email-from-admin/send-email-from-admin.php' => 'The use of "Send Email From Admin" is not allowed.',
        'mailit/mailit.php' => 'The use of "Mail It!" is not allowed.',
        'nginx-helper/nginx-helper.php' => 'The use of Nginx Helper can interfere with caching, which is automatically provided for this site. Nginx Helper has been deactivated.',
        'stopbadbots/stopbadbots.php' => 'The use of Stop Bad Bots is not allowed.',
        'w3-total-cache/w3-total-cache.php' => 'The use of W3 Total Cache can interfere with caching, which is automatically provided for this site. W3 Total Cache has been deactivated.',
        'wp-fastest-cache/wpFastestCache.php' => 'The use of WP Fastest Cache can interfere with caching, which is automatically provided for this site. WP Fastest Cache has been deactivated.',
        'wp-super-cache/wp-cache.php' => 'The use of WP Super Cache can interfere with caching, which is automatically provided for this site. WP Super Cache has been deactivated.',
        'wp-rest-api-log/wp-rest-api-log.php' => 'WP REST API Log inflates post table size beyond normal usage levels.',
        'website-file-changes-monitor/website-file-changes-monitor.php' => 'Melapress File Monitor inflates the options table size beyond normal usage levels.',
    ];
}

/**
 * Disable install button for disallowed plugins
 */
function disable_plugin_install_link( $action_links, $plugin ) {
    $disallowed = get_disallowed_plugins();

    // Compare using slug (derived from plugin file keys)
    $disallowed_slugs = array_map( 'dirname', array_keys( $disallowed ) );

    if ( in_array( $plugin['slug'], $disallowed_slugs, true ) ) {
        return [
            '<a class="install-now button button-disabled" href="#">Not Supported</a>'
        ];
    }

    return $action_links;
}

/**
 * Disable activate button for disallowed plugins
 */
function disable_plugin_activate_link( $actions, $plugin_file ) {
    $disallowed = get_disallowed_plugins();

    if ( isset( $actions['activate'] ) && array_key_exists( $plugin_file, $disallowed ) ) {
        $actions['activate'] = 'Disabled';
        unset( $actions['edit'] );
    }

    return $actions;
}

/**
 * Deactivate disallowed plugins if they are active
 */
function deactivate_disallowed_plugins() {
    $disallowed = get_disallowed_plugins();
    $messages   = [];

    foreach ( $disallowed as $plugin_file => $message ) {
        if ( ! is_plugin_active( $plugin_file ) ) {
            continue;
        }

        deactivate_plugins( $plugin_file );
        $messages[] = $message;
    }

    if ( ! empty( $messages ) ) {
        add_action( 'admin_notices', function() use ( $messages ) {
            foreach ( $messages as $message ) {
                echo '<div class="notice notice-warning"><p>' . wp_kses_post( $message ) . '</p></div>';
            }
        });
    }
}

// Replace "Install" plugin link for plugins that not should not be activated (plugin-install.php)
add_filter( 'plugin_install_action_links', 'disable_plugin_install_link', 0, 2 );
// Replace "Activate" plugin link for plugins that should not be activated (plugins.php)
add_filter( 'plugin_action_links', 'disable_plugin_activate_link', 10, 2 );
add_filter( 'network_admin_plugin_action_links', 'disable_plugin_activate_link', 10, 2 );// Deal with disallowed plugins on the platform.

add_action( 'admin_init', 'deactivate_disallowed_plugins', 10 );