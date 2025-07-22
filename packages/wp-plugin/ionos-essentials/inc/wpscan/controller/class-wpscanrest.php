<?php

namespace ionos\essentials\wpscan;

class WPScanRest
{
  public function __construct()
  {
    add_action('rest_api_init', function () {
      \register_rest_route('ionos/essentials', '/wpscan', [
        'methods'             => 'POST',
        'callback'            => [$this, 'recommended_action'],
        'permission_callback' => function () {
          return current_user_can('update_plugins');
        },
      ]);
    });
  }

  public function recommended_action(\WP_REST_Request $request)
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
        include_once ABSPATH . 'wp-admin/includes/class-wp-upgrader.php';
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

    if (isset($response) && is_wp_error($response) || isset($upgrader->skin->result) && is_wp_error($upgrader->skin->result)) {
      \wp_send_json_error(null, 500);
    }

    $message  = ucFirst($type) . ' ';
    $message .= ('delete' === $action) ? __('was deleted', 'ionos-essentials') : __('was updated', 'ionos-essentials');
    \wp_send_json_success($message, 200);
  }
}
