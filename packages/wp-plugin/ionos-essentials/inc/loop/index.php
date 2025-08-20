<?php

namespace ionos\essentials\loop;


defined('ABSPATH') || exit();


require_once __DIR__ . '/class-plugin.php';

/**
 * Inits the main plugin class.
 */
\add_action( 'init', function () {
  Plugin::init();
} );



/**
 * Add consent action on activation.
 */
function ionos_add_consent_action() {

  if ( get_option( 'ionos_loop_consent' ) === false ) {
    add_option( 'ionos_loop_consent', true );
  }
	\add_action( 'ionos_loop_consent_given', [ Plugin::class, 'register_at_data_collector' ] );
}

register_activation_hook( __FILE__, '\ionos\essentials\loop\ionos_add_consent_action' );

