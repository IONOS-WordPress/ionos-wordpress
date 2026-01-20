<?php

namespace ionos\essentials\wpscan;

defined('ABSPATH') || exit();
use function ionos\stretch_extra\secondary_plugin_dir\get_custom_plugins;
require_once __DIR__ . '/controller/class-wpscan.php';
require_once __DIR__ . '/controller/class-wpscanmiddleware.php';
require_once __DIR__ . '/views/summary.php';
require_once __DIR__ . '/views/issues.php';

\add_action('init', function () {
  global $wpscan;
  $wpscan = new WPScan();
});

function get_wpscan(): WPScan
{
  global $wpscan;

  return $wpscan;
}

\add_action('rest_api_init', function () {
  \register_rest_route('ionos/essentials/wpscan', '/recommended-action', [
    'methods'             => 'POST',
    'permission_callback' => fn () => current_user_can('update_plugins'),
    'callback'            => 'ionos\essentials\wpscan\recommended_action',
  ]);
});

\add_action('wp_ajax_ionos-wpscan-instant-check', 'ionos\essentials\wpscan\instant_check', 10, 1);

function instant_check()
{
  check_ajax_referer('ionos-wpscan-instant-check');
  $slug = $_POST['slug'] ?? '';
  $type = $_POST['type'] ?? '';
  if (empty($slug)) {
    \wp_send_json_error(null, 500);
  }

  $custom_plugins = get_custom_plugins(true);
  $custom_installed_plugin_slugs = array_column($custom_plugins, 'slug');

  if ( in_array($slug, $custom_installed_plugin_slugs)) {
    \wp_send_json_success("nothing_found", 200);
  } else {
    $middleware   = new WPScanMiddleware();
    $issue_type = $middleware->get_instant_data($type, $slug);
    if (false === $issue_type) {
      \wp_send_json_error(null, 500);
    }
    \wp_send_json_success($issue_type, 200);
  }
}

function recommended_action(\WP_REST_Request $request)
{
  $data = $request->get_json_params()['data'] ?? [];
  if (empty($data)) {
    \wp_send_json_error(null, 500);
  }

  $data   = json_decode($data);
  $slug   = $data->slug   ?? '';
  $path   = $data->path   ?? '';
  $action = $data->action ?? '';
  $type   = $data->type   ?? '';

  if (empty($slug) || empty($action) || empty($type)) {
    \wp_send_json_error(null, 500);
  }

  switch ($type . '-' . $action) {
    case 'plugin-delete':
      \deactivate_plugins($path, true);
      $response = \delete_plugins([$path]);
      break;
    case 'plugin-update':
      require_once ABSPATH . 'wp-admin/includes/class-wp-upgrader.php';
      $upgrader = new \Plugin_Upgrader(new \WP_Ajax_Upgrader_Skin());

      $upgrader->upgrade($path);
      \delete_transient('ionos_wpscan_issues');
      break;
    case 'theme-delete':
      $theme = \wp_get_theme();

      if (strToLower($theme->get('Name')) === strToLower($slug)) {
        \wp_send_json_success(__('Active theme cannot be deleted', 'ionos-essentials'), 200);
      }

      require_once ABSPATH . 'wp-admin/includes/theme.php';
      $response = \delete_theme($slug);
      break;
    case 'theme-update':
      include_once ABSPATH . 'wp-admin/includes/class-wp-upgrader.php';
      $upgrader = new \Theme_Upgrader(new \WP_Ajax_Upgrader_Skin());

      $upgrader->upgrade($slug);
      \delete_transient('ionos_wpscan_issues');
      break;

    default:
      \wp_send_json_error(null, 500);
  }

  if (isset($response) && is_wp_error($response) || isset($upgrader->skin->result) && is_wp_error(
    $upgrader->skin->result
  )) {
    \wp_send_json_error(null, 500);
  }

  $message  = ucFirst($type) . ' ';
  $message .= ('delete' === $action) ? __('was deleted', 'ionos-essentials') : __('was updated', 'ionos-essentials');
  \wp_send_json_success($message, 200);
}

\add_action('init', function () {
  if (! \wp_next_scheduled('ionos_wpscan')) {
    \wp_schedule_event(time(), 'daily', 'ionos_wpscan');
  }
});

\add_action('ionos_wpscan', function () {
  $wpscan = new WPScan();
  error_log('Running WPScan cron job');
});

\register_deactivation_hook(__FILE__, function () {
  $timestamp = \wp_next_scheduled('ionos_wpscan');
  if ($timestamp) {
    \wp_unschedule_event($timestamp, 'ionos_wpscan');
  }
});
