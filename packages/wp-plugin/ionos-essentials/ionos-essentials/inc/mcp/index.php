<?php

namespace ionos\essentials\mcp;

defined('ABSPATH') || exit();

$mcp_settings = \get_option('wordpress_mcp_settings', []);
define('IONOS_ESSENTIALS_MCP_SERVER_ACTIVE', $mcp_settings['enabled'] ?? false);

\add_action('rest_api_init', function () {
  \register_rest_route(
    'ionos/essentials/mcp',
    '/action',
    [
      'methods'             => 'POST',
      'permission_callback' => fn () => 0 !== \get_current_user_id(),
      'callback'            => function ($request) {

        if (!wp_verify_nonce($request->get_header('X-WP-Nonce'), 'wp_rest')) {
          return new \WP_Error('rest_forbidden', 'Invalid nonce.', [
            'status' => 403,
          ]);
        }

        $params   = $request->get_json_params();
        $activate = $params['activate'] ?? 'false';

        $mcp_settings            = \get_option('wordpress_mcp_settings', []);
        $mcp_settings['enabled'] = $activate ? true : false;
        \update_option('wordpress_mcp_settings', $mcp_settings);

        if (false === $activate) {
          return rest_ensure_response(new \WP_REST_Response([
            'active' => '0',
          ], 200));
        }

        $snippet = [
          'servers' => [
            'wordpress' => [
              'type'    => 'http',
              'url'     => rest_url('wp/v2/wpmcp/streamable'),
              'headers' => [
                'Authorization' => 'Bearer eyJ0eXAiOiJKV1QiLCJhbGciOiJIUzI1NiJ9.eyJpc3MiOiJodHRwczovL3hreGtmdC1ranNvOX3x7nazugwvaJzghEvR8BBt10fc0GEU3040ghM',
              ],
            ],
          ],
        ];

        sleep(3); // simulate processing time

        return rest_ensure_response(new \WP_REST_Response([
          'active'   => '1',
          'snippet'  => json_encode($snippet, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES),

        ], 200));
      },
    ]
  );
}, 1);
