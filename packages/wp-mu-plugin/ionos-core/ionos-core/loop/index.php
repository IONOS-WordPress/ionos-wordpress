<?php

namespace ionos\ionos_core;

function loop_data_response(\WP_REST_Request $request): \WP_REST_Response
{
  return new \WP_REST_Response([
    'data' => [
      'hostname' => gethostname(),
      'supplier' => 'ionos-core',
    ],
  ]);
}

add_action('admin_notices', function () {
  $my_version = \get_file_data(__DIR__ . '/../../ionos-core.php', ['version' => 'Version'])['version'] ?? null;
  echo '<div class="notice notice-info is-dismissible"><p>' . esc_html__(
    'Welcome to IONOS Core. This is version ' . $my_version . '.',
    'ionos-core'
  ) . '</p></div>';
});

add_filter('rest_endpoints', function (array $endpoints): array {
  $endpoints['/ionos/essentials/loop/v1/loop-data'] = [
    [
      'methods'             => ['GET'],
      'callback'            => __NAMESPACE__ . '\loop_data_response',
      'permission_callback' => '__return_true',
      'args'                => [],
    ],
  ];
  return $endpoints;
});
