<?php

namespace ionos\essentials\loop;

defined('ABSPATH') || exit();

require_once __DIR__ . '/class-plugin.php';

/**
 * Add consent action on activation.
 */
\add_action('init', function () {

  if (\get_option('ionos_loop_consent') != '1') {
    add_option('ionos_loop_consent', '1');
  }

  Plugin::init();

  \add_action('ionos_loop_consent_given', [Plugin::class, 'register_at_data_collector']);
});

// register_activation_hook( __FILE__, '\ionos\essentials\loop\ionos_add_consent_action' );
