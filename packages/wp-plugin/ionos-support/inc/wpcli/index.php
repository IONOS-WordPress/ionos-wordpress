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

  $phar = new \Phar(WPCLI_PHAR);
  $phar->extractTo(WPCLI_DIR);
});

const WPCLI_REST_ENDPOINT = __NAMESPACE__ . '/v1';
const WPCLI_REST_ENDPOINT_EXECUTE = '/execute';
const WPCLI_REST_ENDPOINT_RUN = '/run';

\add_action('rest_api_init', function() {
  \register_rest_route(WPCLI_REST_ENDPOINT, WPCLI_REST_ENDPOINT_EXECUTE, [
    'methods' => 'POST',
    'callback' => __NAMESPACE__ . '/_wpcli_exec',
    'permission_callback' => function () {
      return \current_user_can('manage_options');
    },
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

  \register_rest_route(WPCLI_REST_ENDPOINT, WPCLI_REST_ENDPOINT_RUN, [
    'methods' => 'POST',
    'callback' => __NAMESPACE__ . '/_wpcli_run',
    'permission_callback' => function () {
      return \current_user_can('manage_options');
    },
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
});

function _wpcli_exec(\WP_REST_Request $request) {
  [ 'command' => $command ] = $request->get_json_params();

  $descriptorspec = array(
    0 => array('pipe', 'r'),
    1 => array('pipe', 'w'),
    2 => array('pipe', 'w'),
  );

  $process = proc_open("php " . WPCLI_PHAR . " $command", $descriptorspec, $pipes);

  if (!is_resource($process)) {
    \wp_send_json_error('Failed to execute command');
    return;
  }

  $output = stream_get_contents($pipes[1]);
  $error = stream_get_contents($pipes[2]);

  fclose($pipes[0]);
  fclose($pipes[1]);
  fclose($pipes[2]);

  proc_close($process);

  return \wp_send_json_success([
    'output' => $output,
    'error' => $error,
  ]);
}

function _wpcli_run(\WP_REST_Request $request) {
  [ 'command' => $command ] = $request->get_json_params();

  require_once WPCLI_PHAR;

  $output = [];
  $error = [];

  try {
    $result = \WP_CLI::run_command($command, ['return' => true]);
    $output = $result->getOutput();
    $error = $result->getError();
  } catch (\Exception $e) {
    $error[] = $e->getMessage();
  }

  return \wp_send_json_success([
    'output' => implode("\n", $output),
    'error' => implode("\n", $error),
  ]);
}

\add_action('admin_enqueue_scripts', function () {
  $WPCLI_VERSION = null;

  if(file_exists(WPCLI_DIR . '/vendor/wp-cli/wp-cli/VERSION')) {
    $WPCLI_VERSION = file_get_contents(WPCLI_DIR . '/vendor/wp-cli/wp-cli/VERSION');
  }

  # require_once WPCLI_DIR . '/vendor/autoload.php';

  $assets = require_once PLUGIN_DIR . '/build/wpcli/index.asset.php';
  \wp_enqueue_script(
    'ionos-support-wpcli',
    \plugin_dir_url(PLUGIN_FILE) . 'build/wpcli/index.js',
    $assets['dependencies'],
    $assets['version'],
    true
  );

  \wp_add_inline_script(
    'ionos-support-wpcli',
    sprintf('window.wp.cli({ version : %s});', \wp_json_encode($WPCLI_VERSION)),
  );
});
