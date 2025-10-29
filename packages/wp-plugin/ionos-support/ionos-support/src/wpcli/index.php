<?php

namespace ionos\support\wpcli;

use const ionos\support\PLUGIN_DIR;
use const ionos\support\PLUGIN_FILE;

/*
  provides wp-cli functionality via REST API and developer console of the browser
*/

defined('ABSPATH') || exit();

const WPCLI_DIR = __DIR__ . '/lib/wpcli';
const WPCLI_PHAR = WPCLI_DIR . '/wpcli.phar';
const WPCLI_URL = 'https://raw.githubusercontent.com/wp-cli/builds/gh-pages/phar/wp-cli.phar';

\add_action('admin_init', function () {
  if (file_exists(WPCLI_PHAR)) {
    return;
  }

  $response = \wp_remote_get(WPCLI_URL);

  if (\is_wp_error($response)) {
    error_log($response->get_error_message());
    \add_action('admin_notices', fn() =>
      printf('<div class="notice notice-error is-dismissible"><p>%s</p></div>', \esc_html($response->get_error_message()))
    );

    return;
  }

  !is_dir(WPCLI_DIR) && mkdir(WPCLI_DIR, 0755, true);

  file_put_contents(WPCLI_PHAR, $response['body']);

  chmod(WPCLI_PHAR, 0755);
});

const WPCLI_REST_NAMESPACE = 'ionos/support/wpcli/v1';
const WPCLI_REST_ROUTE_EXEC = '/exec';
const WPCLI_REST_ROUTE_UNSERIALIZE = '/unserialize';
const WPCLI_REST_ROUTE_SERIALIZE = '/serialize';

\add_action('rest_api_init', function() {
  \register_rest_route(WPCLI_REST_NAMESPACE, WPCLI_REST_ROUTE_EXEC, [
    'methods' => 'POST',
    'callback' => function (\WP_REST_Request $request) {
      [ 'command' => $command ] = $request->get_json_params();

      $descriptors = [
        0 => ['pipe', 'r'],
        1 => ['pipe', 'w'],
        2 => ['pipe', 'w'],
      ];

      $process = proc_open("php " . WPCLI_PHAR . " $command", $descriptors, $pipes);

      if (!is_resource($process)) {
        \wp_send_json_error('Failed to execute command');
        return;
      }

      $stdout = stream_get_contents($pipes[1]);
      $stderr = stream_get_contents($pipes[2]);

      fclose($pipes[0]);
      fclose($pipes[1]);
      fclose($pipes[2]);

      proc_close($process);

      \wp_send_json_success([
        'stdout' => $stdout,
        'stderr' => $stderr,
      ]);
    },
    'permission_callback' => fn () => \current_user_can('manage_options'),
    'args' => [
      'command' => [
        'required' => true,
        'validate_callback' => function ($param, $request, $key) {
          if (!is_string($param)) {
            \wp_send_json_error("Invalid parameter: $key must be a string", 400);
            return false;
          }
          return true;
        },
      ],
    ],
  ]);

  \register_rest_route(WPCLI_REST_NAMESPACE, WPCLI_REST_ROUTE_SERIALIZE, [
    'methods' => 'POST',
    'callback' => function (\WP_REST_Request $request) {
      $data = $request->get_json_params();

      $serialized = serialize($data);

      \wp_send_json_success($serialized);
    },
    'permission_callback' => '__return_true',
  ]);

  \register_rest_route(WPCLI_REST_NAMESPACE, WPCLI_REST_ROUTE_UNSERIALIZE, [
    'methods' => 'POST',
    'callback' => function (\WP_REST_Request $request) {
      $data = $request->get_json_params();

      $unserialized = unserialize($data);

      \wp_send_json_success($unserialized);
    },
    'permission_callback' => '__return_true',
  ]);
});


function _wpcli($command) {
  $descriptors = [
    0 => ['pipe', 'r'],
    1 => ['pipe', 'w'],
    2 => ['pipe', 'w'],
  ];

  $process = proc_open("php " . WPCLI_PHAR . " $command", $descriptors, $pipes);

  if (!is_resource($process)) {
    \wp_die(sprintf('Failed to execute command "%s": %s', $command, \wp_json_encode($command)));
    return;
  }

  $stdout = stream_get_contents($pipes[1]);
  $stderr = stream_get_contents($pipes[2]);

  fclose($pipes[0]);
  fclose($pipes[1]);
  fclose($pipes[2]);

  proc_close($process);

  return [
    'stdout' => $stdout,
    'stderr' => $stderr,
  ];
}

\add_action('admin_enqueue_scripts', function () {
  $WPCLI_VERSION = null;

  [ 'stdout' => $stdout, 'stderr' => $stderr ] = _wpcli('--version');
  $WPCLI_VERSION = trim(strlen($stderr) > 0 ? $stderr : $stdout);

  $assets = require_once PLUGIN_DIR . '/ionos-support/build/wpcli/index.asset.php';
  \wp_enqueue_script(
    'ionos-support-wpcli',
    \plugin_dir_url(PLUGIN_FILE) . '/ionos-support/build/wpcli/index.js',
    $assets['dependencies'],
    $assets['version'],
    true
  );

  \wp_add_inline_script(
    'ionos-support-wpcli',
    sprintf(
      'window.wp.cli(%s);',
      \wp_json_encode([
        'VERSION' => $WPCLI_VERSION,
        'REST_NAMESPACE' => WPCLI_REST_NAMESPACE,
        'REST_ROUTE_EXEC' => WPCLI_REST_ROUTE_EXEC,
        'REST_ROUTE_SERIALIZE' => WPCLI_REST_ROUTE_SERIALIZE,
        'REST_ROUTE_UNSERIALIZE' => WPCLI_REST_ROUTE_UNSERIALIZE,
      ])
    ),
  );
});
