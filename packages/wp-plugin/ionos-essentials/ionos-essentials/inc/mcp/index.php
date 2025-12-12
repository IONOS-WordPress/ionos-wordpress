<?php

namespace ionos\essentials\mcp;

defined('ABSPATH') || exit();

const APPLICATION_NAME = 'Essentials MCP';

\add_action('init', function () {
  $mcp_settings = \get_option('wordpress_mcp_settings', [
    'enabled' => false,
  ]);
  define('IONOS_ESSENTIALS_MCP_SERVER_ACTIVE', (defined('WORDPRESS_MCP_PATH') && $mcp_settings['enabled']));
});

\add_action('rest_api_init', function () {
  \register_rest_route(
    'ionos/essentials/mcp',
    '/action',
    [
      'methods'             => 'POST',
      'permission_callback' => fn () => 0 !== \get_current_user_id(),
      'callback'            => function ($request) {

        if (! wp_verify_nonce($request->get_header('X-WP-Nonce'), 'wp_rest')) {
          return new \WP_Error('rest_forbidden', 'Invalid nonce.', [
            'status' => 403,
          ]);
        }

        $params   = $request->get_json_params();
        $activate = $params['activate'] ? true : false;

        if ($params['revokeAppPassword']) {
          revoke_application_password();
        }

        $mcp_settings = \get_option('wordpress_mcp_settings', []);
        $mcp_settings = [
          'enabled'             => $activate,
          'enable_create_tools' => true,
          'enable_update_tools' => true,
          'enable_delete_tools' => true,
        ];

        \update_option('wordpress_mcp_settings', $mcp_settings);

        if (false === $activate) {
          return rest_ensure_response(new \WP_REST_Response([
            'active' => '0',
          ], 200));
        }

        if (! activate_mcp_server()) {
          return rest_ensure_response(new \WP_REST_Response([
            'active' => '0',
            'error'  => 'MCP server plugin is not active.',
          ], 500));
        }

        if (\WP_Application_Passwords::application_name_exists_for_user(wp_get_current_user()->ID, APPLICATION_NAME)) {
          return rest_ensure_response(new \WP_REST_Response([
            'active' => '1',
          ], 200));
        }

        $snippet = [
          'servers' => [
            'wordpress' => [
              'command'    => 'npx',
              'args'       => [
                '-y',
                '@automattic/mcp-wordpress-remote@latest'
              ],
              'env'        => [
                'WP_API_URL'      => get_site_url(),
                'WP_API_USERNAME' => wp_get_current_user()
                  ->user_login,
                'WP_API_PASSWORD' => get_new_application_password(),
              ],
            ],
          ],
        ];

        return rest_ensure_response(new \WP_REST_Response([
          'active'   => '1',
          'snippet'  => json_encode($snippet, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES),
        ], 200));
      },
    ]
  );
}, 1);

add_action('application_password_did_authenticate', function ($user, $item) {
  if ($item['name'] !== APPLICATION_NAME) {
    return;
  }

  $data = \get_option('ionos_loop_mcp_tracking', []);

  $data[$user->user_login] = ($data[$user->user_login] ?? 0) + 1;

  \update_option('ionos_loop_mcp_tracking', $data);
}, 10, 2);

function activate_mcp_server(): bool
{
  if (defined('WORDPRESS_MCP_PATH')) {
    return true;
  }

  if (! file_exists(WP_PLUGIN_DIR . '/wordpress-mcp/wordpress-mcp.php')) {
    $upgrader = new \Plugin_Upgrader(new \WP_Ajax_Upgrader_Skin());
    $result   = $upgrader->install(
      'https://github.com/Automattic/wordpress-mcp/releases/download/v0.2.5/wordpress-mcp.zip'
    );
    if (\is_wp_error($result)) {
      error_log('Failed to install MCP server plugin: ' . $result->get_error_message());
      return false;
    }
  }

  if (! is_plugin_active('wordpress-mcp/wordpress-mcp.php')) {
    \activate_plugin('wordpress-mcp/wordpress-mcp.php');
  }

  return true;
}

function get_new_application_password(): string
{
  $user         = wp_get_current_user();
  $applications = \WP_Application_Passwords::get_user_application_passwords($user->ID);

  revoke_application_password();

  $new_app = \WP_Application_Passwords::create_new_application_password($user->ID, [
    'name' => APPLICATION_NAME,
  ]);

  if (is_wp_error($new_app)) {
    error_log('Failed to create application password: ' . $new_app->get_error_message());
    return '';
  }

  return \WP_Application_Passwords::chunk_password($new_app[0]);
}

function revoke_application_password(): void
{
  $user         = wp_get_current_user();
  $applications = \WP_Application_Passwords::get_user_application_passwords($user->ID);

  foreach ($applications as $app) {
    if ($app['name'] === APPLICATION_NAME) {
      \WP_Application_Passwords::delete_application_password($user->ID, $app['uuid']);
      break;
    }
  }
}
