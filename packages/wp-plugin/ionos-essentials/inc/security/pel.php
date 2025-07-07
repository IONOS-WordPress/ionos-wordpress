<?php

namespace ionos\essentials\security;

// exit if accessed directly
if (! defined('ABSPATH')) {
  exit();
}

$options = \get_option('IONOS_SECURITY_FEATURE_OPTION', false);
$options['IONOS_SECURITY_FEATURE_OPTION_PEL'] = false;
\update_option('IONOS_SECURITY_FEATURE_OPTION', $options);

add_action( 'login_init', function () {
  if ( ! \get_option( 'IONOS_SECURITY_FEATURE_OPTION[IONOS_SECURITY_FEATURE_OPTION_PEL]', false ) ) {
    return;
  }
});

add_action('implement_security_feature_page', function () {

  if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $value = true;
    if ( isset($_POST['ionos_security_pel_enabled']) ) {
      $value = false;
    }

    \update_option('IONOS_SECURITY_FEATURE_OPTION[IONOS_SECURITY_FEATURE_OPTION_PEL]', $value);
  }

});
