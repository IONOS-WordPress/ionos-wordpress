<?php

/**
 * Plugin Name:       Support
 * Description:       The support plugin provides IONOS hosting support specific functionality.
 * Requires at least: 6.8
 * Requires Plugins:
 * Requires PHP:      8.3
 * Version:           1.0.0
 * Update URI:        https://github.com/IONOS-WordPress/ionos-wordpress/releases/download/%40ionos-wordpress%2Flatest/ionos-support-info.json
 * Plugin URI:        https://github.com/IONOS-WordPress/ionos-wordpress/tree/main/packages/wp-plugin/ionos-support
 * License:           GPL-2.0-or-later
 * Author:            IONOS Group
 * Author URI:        https://www.ionos-group.com/brands.html
 * Domain Path:       /languages
 * Text Domain:       ionos-support
 */

namespace ionos\support;

defined('ABSPATH') || exit();

const PLUGIN_DIR = __DIR__;
const PLUGIN_FILE = __FILE__;

require_once __DIR__ . '/ionos-support/build/wpcli/index.php';

function custom_toolbar_link($wp_admin_bar) {
    $args = array(
        'id'    => 'mein-benutzerdefinierter-button', // Eindeutige ID für den Button
        'title' => 'Mein Button',                     // Der sichtbare Text
        'href'  => 'https://example.com',             // Der Link
        'meta'  => array(
            'class' => 'mein-benutzerdefinierter-button-klasse', // CSS-Klasse für Styling
            'target' => '_blank'                                 // Link in neuem Tab öffnen
        )
    );
    $wp_admin_bar->add_node($args);
}
add_action(
  hook_name: 'admin_bar_menu',
  callback: function($wp_admin_bar) {
    $wp_admin_bar->add_node([
        'id'    => 'mein-haupt-button',
        'title' => 'IONOS Support',
        'href'  => '#'
    ]);

    $wp_admin_bar->add_node([
      'id'    => 'untermenue-link-1',
      'title' => 'Link 1',
      'href'  => "javascript:alert('Hello, World!');",
      'parent'=> 'mein-haupt-button'
    ]);

    $wp_admin_bar->add_node([
      'id'    => 'untermenue-link-2',
      'title' => 'Link 2',
      'href'  => 'https://wikipedia.org',
      'parent'=> 'mein-haupt-button'
    ]);
  },
  priority : 999
);
