<?php
/**
 * MU Plugin: Block disallowed plugins from UI and WP-CLI
 */

/**
 * List of disallowed plugins (plugin_file => reason)
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
    $disallowed_slugs = array_map( 'dirname', array_keys( get_disallowed_plugins() ) );

    if ( in_array( $plugin['slug'], $disallowed_slugs, true ) ) {
        return [
            '<a class="install-now button button-disabled" href="#">Not Supported</a>'
        ];
    }

    return $action_links;
}
add_filter( 'plugin_install_action_links', 'disable_plugin_install_link', 0, 2 );

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
add_filter( 'plugin_action_links', 'disable_plugin_activate_link', 10, 2 );
add_filter( 'network_admin_plugin_action_links', 'disable_plugin_activate_link', 10, 2 );

/**
 * Deactivate disallowed plugins if they are active
 */
function deactivate_disallowed_plugins() {
    $disallowed = get_disallowed_plugins();
    foreach ( $disallowed as $plugin_file => $message ) {
        if ( is_plugin_active( $plugin_file ) ) {
            deactivate_plugins( $plugin_file );
            add_action( 'admin_notices', function() use ( $message ) {
                echo '<div class="notice notice-error is-dismissible"><p>' . wp_kses_post( $message ) . '</p></div>';
            });
        }
    }
}
add_action( 'admin_init', 'deactivate_disallowed_plugins', 0 );


/**
 * Disallow installation of blocked plugins via the installer
 */
function block_disallowed_post_install( $true, $hook_extra, $result ) {
    $disallowed = get_disallowed_plugins();

    if ( empty( $result['destination'] ) || ! is_dir( $result['destination'] ) ) {
        return $true;
    }

    $plugin_folder = basename( $result['destination'] );
    $files = scandir( $result['destination'] );

    // ✅ Print normal text after "Unpacking the package…"
    echo '<p style="margin:0 0 8px 0; font-style:italic; color:#555;">Validating against blocked plugins…</p>';

    foreach ( $files as $file ) {
        if ( substr( $file, -4 ) === '.php' ) {
            $plugin_file = "$plugin_folder/$file";

            if ( isset( $disallowed[ $plugin_file ] ) ) {
                // Delete unpacked folder immediately
                $it = new RecursiveDirectoryIterator( $result['destination'], RecursiveDirectoryIterator::SKIP_DOTS );
                $files_iter = new RecursiveIteratorIterator( $it, RecursiveIteratorIterator::CHILD_FIRST );
                foreach( $files_iter as $fileinfo ) {
                    $todo = ($fileinfo->isDir() ? 'rmdir' : 'unlink');
                    $todo($fileinfo->getRealPath());
                }
                rmdir( $result['destination'] );

                // ✅ Return WP_Error styled like a notice-error but **inline in installer**
                $error_message = '<div style="padding:12px; border-left:4px solid #d63638; background-color:rgba(214,54,56,0.05); margin:0 0 12px 0;">' .
                                 '<p>This plugin is not supported on our Managed WordPress platform. <a href="' . admin_url('plugins.php') . '">Full list of blocked plugins</a>.</p>' . // TODO link to documentation
                                 '</div>';

                return new WP_Error( 'plugin_blocked', $error_message );
            }
        }
    }

    return $true;
}
add_filter( 'upgrader_post_install', 'block_disallowed_post_install', 10, 3 );