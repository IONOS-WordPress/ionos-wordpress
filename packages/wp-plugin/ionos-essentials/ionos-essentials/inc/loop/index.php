<?php

namespace ionos\essentials\loop;

defined('ABSPATH') || exit();

require_once __DIR__ . '/class-plugin.php';

// loop consent option (true|false) from user / legacy loop plugin
const IONOS_LOOP_CONSENT_OPTION = 'ionos_loop_consent';
// option to keep last datacollector access timestamp
const IONOS_LOOP_LAST_DATACOLLECTOR_ACCESS_OPTION = 'ionos_loop_last_datacollector_access';
const IONOS_LOOP_REST_NAMESPACE = '/ionos/essentials/loop/v1';
const IONOS_LOOP_REST_ENDPOINT = '/loop-data';
const IONOS_LOOP_DATACOLLECTOR_REGISTRATION_URL = 'https://webapps-loop.hosting.ionos.com/api/register';

/**
 * Add consent action on activation.
 */
\add_action('init', function () {

  if (\get_option('ionos_loop_consent') != '1') {
    add_option('ionos_loop_consent', '1');
  }

  Plugin::init();

  // \add_action('ionos_loop_consent_given', [Plugin::class, 'register_at_data_collector']);
  if ( \get_option(IONOS_LOOP_CONSENT_OPTION, false) === true) {
    _register_datacollector_endpoint();
  }
});

/*
  registers our endpoint at the data collector

  @return bool true if registration was successful
*/
function _register_datacollector_endpoint() : bool
{
  // skip registration for wp-env/local/dev environments
  if (in_array(\wp_get_environment_type(), ['local', 'development'], true)) {
    return true;
  }

  $response = \wp_remote_post(
    IONOS_LOOP_DATACOLLECTOR_REGISTRATION_URL,
    [
    'body'    => \wp_json_encode([
      'url' => \get_home_url() . '/index.php?rest_route=' . IONOS_LOOP_REST_NAMESPACE . IONOS_LOOP_REST_ENDPOINT,
    ]),
    'headers' => [
      'content-type' => 'application/json',
    ],
  ]);

  if (! \is_wp_error($response)) {
    // @TODO : what should we do if registration failed ?
    // try again after a interval or what
  }

  return ! \is_wp_error($response);
}

// register_activation_hook( __FILE__, '\ionos\essentials\loop\ionos_add_consent_action' );
