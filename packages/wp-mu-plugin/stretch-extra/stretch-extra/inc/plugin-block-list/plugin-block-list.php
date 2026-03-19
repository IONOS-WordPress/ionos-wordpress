<?php
echo "---------------test12345 new changes here";

function disable_plugin_install_link( $action_links, $plugin ) {

    // Check secondary plugins
    if ( function_exists( 'get_all_custom_plugins' ) ) {
        $disallowed_plugins = get_all_custom_plugins();
    } else {
        $disallowed_plugins = [];
    }

    // Extra disallowed slugs
    $extra_disallowed_plugins = [
        'gslider-blocks',
        'lordicon'
    ];

    $all_disallowed = array_merge( array_keys( $disallowed_plugins ), $extra_disallowed_plugins );

    // Check if this plugin is in the list
    if ( in_array( $plugin['slug'], $all_disallowed, true ) ) {
        return [
            '<a class="install-now button button-disabled" href="#">Not Supported</a>'
        ];
    }

    return $action_links;
}

add_filter( 'plugin_install_action_links', 'disable_plugin_install_link', 0, 2 );