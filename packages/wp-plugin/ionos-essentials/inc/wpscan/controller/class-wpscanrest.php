<?php

namespace ionos\essentials\wpscan;

class WPScanRest
{
  public function __construct()
  {
    add_action('rest_api_init', function () {
      \register_rest_route('ionos/essentials', '/wpscan', [
        'methods'             => 'POST',
        'callback'            => [$this, 'bulk_request'],
        'permission_callback' => function () {
          return current_user_can('update_plugins');
        },
      ]);
    });
  }

  public function bulk_request(\WP_REST_Request $request)
  {
    $data = $request->get_json_params()['data'] ?? [];
    if (empty($data)) {
      return new \WP_REST_Response([
        'status'  => 'error',
        'message' => __('No data provided', 'ionos-essentials'),
      ], 400);
    }
    $data   = json_decode($data);
    $slug   = $data->slug   ?? '';
    $path   = $data->path   ?? '';
    $action = $data->action ?? '';
    $type   = $data->type   ?? '';

    if (empty($slug) || empty($action) || empty($type)) {
      return new \WP_REST_Response([
        'status'  => 'error',
        'message' => __('Missing required parameters', 'ionos-essentials'),
      ], 400);
    }

    $status_code = 200;
    $message     = \__('Operation completed successfully', 'ionos-essentials');
    $status      = 'success';

    if ('plugin' === $type) {
      if ('delete' === $action) {
        \deactivate_plugins($path, true);
        $response = \delete_plugins([$path]);

        if (is_wp_error($response)) {
          $status_code = 500;
          $status      = 'error';
          $message     = __('Failed to delete plugin', 'ionos-essentials');
        }
      }
      if ('update' === $action) {
        include_once ABSPATH . 'wp-admin/includes/class-wp-upgrader.php';
        $upgrader = new \Plugin_Upgrader(new \WP_Ajax_Upgrader_Skin());

        $upgrader->upgrade($path);
        if (is_wp_error($upgrader->skin->result)) {
          $status_code = 500;
          $status      = 'error';
          $message     = __('Failed to update plugin', 'ionos-essentials');
        }

        \delete_transient('ionos_wpscan_issues');
      }
    }

    if ('theme' === $type) {
      if ('delete' === $action) {
        $theme = \wp_get_theme();

        if (strToLower($theme->get('Name')) === strToLower($slug)) {
          $status_code = 500;
          $status      = 'error';
          $message     = __('Active theme cannot be deleted', 'ionos-essentials');
        } else {
          require_once ABSPATH . 'wp-admin/includes/theme.php';

          $response = \delete_theme($slug);
          if (is_wp_error($response)) {
            $status_code = 500;
            $status      = 'error';
            $message     = __('Failed to delete theme', 'ionos-essentials');
          }
        }
      }

      if ('update' === $action) {
        include_once ABSPATH . 'wp-admin/includes/class-wp-upgrader.php';
        $upgrader = new \Theme_Upgrader(new \WP_Ajax_Upgrader_Skin());

        $upgrader->upgrade($slug);

        if (is_wp_error($upgrader->skin->result)) {
          $status_code = 500;
          $status      = 'error';
          $message     = __('Failed to update theme', 'ionos-essentials');
        }

        \delete_transient('ionos_wpscan_issues');
      }
    }

    return new \WP_REST_Response([
      'status_code'    => $status_code,
      'status'         => $status,
      'message'        => $message,
    ], $status_code);
  }
}
