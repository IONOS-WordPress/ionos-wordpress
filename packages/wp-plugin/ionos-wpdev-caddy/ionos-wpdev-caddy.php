<?php

/**
 * Plugin Name:       WPDev Caddy
 * Description:       Plugin helps IONOS WordPress development team finding issues and bugs.
 * Requires at least: 6.7
 * Requires Plugins:
 * Requires PHP:      8.3
 * Version:           1.0.0
 * Update URI:        https://github.com/IONOS-WordPress/ionos-wordpress/releases/download/%40ionos-wordpress%2Flatest/ionos-support-info.json
 * Plugin URI:        https://github.com/IONOS-WordPress/ionos-wordpress/tree/main/packages/wp-plugin/ionos-support
 * License:           GPL-2.0-or-later
 * Author:            IONOS Group
 * Author URI:        https://www.ionos-group.com/brands.html
 * Domain Path:       /ionos-wpdev-caddy/languages
 * Text Domain:       ionos-wpdev-caddy
 */

namespace ionos\wpdev\caddy;

use WP_Admin_Bar;

use function ionos\wpdev\caddy\notebook\get_notebooks;

defined('ABSPATH') || exit();

const PLUGIN_DIR      = __DIR__;
const PLUGIN_FILE     = __FILE__;

const MENU_PAGE_SLUG  = 'ionos-wpdev-caddy';
const MENU_PAGE_TITLE = 'WPDev Caddy';
const MENU_PAGE_URI   = 'admin.php?page=' . MENU_PAGE_SLUG;

\add_action(
  hook_name: 'admin_menu',
  callback: function () {
    $page_hook_suffix = \add_menu_page(
      page_title : MENU_PAGE_TITLE,
      menu_title : MENU_PAGE_TITLE,
      capability : 'manage_options',
      menu_slug  : MENU_PAGE_SLUG,
      callback   : __NAMESPACE__ . '\_render_admin_page',
    );
  }
);

function _render_admin_page(): void
{
  printf(
    <<<HTML
    <div class="wrap">
      <h1>WPDev Caddy</h1>
      <p>WPDev Caddy helps IONOS WordPress development team finding issues and bugs.</p>

      <h2>Notebooks</h2>
      <p>Notebooks are small tools to help with debugging and development.</p>
      <ul>
    HTML,
  );

  foreach (get_notebooks() as $notebook) {
    printf(
      '<li><a href="%s">%s</a></li>',
      \admin_url('admin.php?page=' . $notebook['slug']),
      \esc_html($notebook['name']),
    );
  }
  echo '</ul></div>';
}

\add_action('wp_ajax_' . MENU_PAGE_SLUG, function () {
  // since we utilize the automatically available nonce from wp-api-fetch we need to use the expected key '_ajax_nonce' here
  check_ajax_referer('wp_rest', '_ajax_nonce');

  $php_code = \wp_unslash($_POST['php_code']) ?? '<?php';

  $temp_file = sys_get_temp_dir() . '/' . MENU_PAGE_SLUG . '.php';
  if ($temp_file === false) {
    \wp_send_json_error([
      'message' => 'Could not create temporary file.',
    ]);
  }

  file_put_contents($temp_file, $php_code);
  ob_start();
  try {
    require_once $temp_file;
    $output = ob_get_clean();
    \wp_send_json_success($output);
  } catch (\Throwable $e) {
    $output = ob_get_clean();
    \wp_send_json_error($e . PHP_EOL . $output);
  } finally {
    unlink($temp_file);
  }
});

// ui sugar - not really needed

\add_action(
  hook_name: 'admin_bar_menu',
  callback: function (WP_Admin_Bar $wp_admin_bar) {
    $wp_admin_bar->add_node([
      'id'    => MENU_PAGE_SLUG,
      'title' => MENU_PAGE_TITLE,
      'href'  => \admin_url(MENU_PAGE_URI),
      'meta'  => [
        'class' => MENU_PAGE_SLUG,
      ],
    ]);
  },
  priority : 999,
);

// add a link to the plugin page to the plugin description in plugins.php
\add_filter(
  hook_name: 'plugin_row_meta',
  callback: function ($links, $file) {
    if ($file === \plugin_basename(PLUGIN_FILE)) {
      $settings_link = '<a href="' . \admin_url(MENU_PAGE_URI) . '">' . \esc_html('Settings') . '</a>';
      $links[]       = $settings_link;
    }
    return $links;
  },
  accepted_args: 2,
);

require_once __DIR__ . '/ionos-wpdev-caddy/notebooks/index.php';
