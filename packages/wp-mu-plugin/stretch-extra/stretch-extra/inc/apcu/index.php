<?php

namespace ionos\stretch_extra\apcu;

use const ionos\stretch_extra\IONOS_CUSTOM_DIR;

defined('ABSPATH') || exit();

const IONOS_APCU_OBJECT_CACHE_ENABLED_OPTION = 'IONOS_APCU_OBJECT_CACHE_ENABLED_OPTION';

// initialize IONOS_APCU_OBJECT_CACHE_ENABLED_OPTION
// if the option does not exist, set it to true if apcu is enabled, otherwise false
// @TODO: this can be removed once the stretch database initialization contains it
\add_action('muplugins_loaded', function () {
  $value = get_option(IONOS_APCU_OBJECT_CACHE_ENABLED_OPTION, null);

  if (null === $value) {
    $value = (bool) function_exists('apcu_enabled') && apcu_enabled();
    add_option(IONOS_APCU_OBJECT_CACHE_ENABLED_OPTION, $value, '', true);
  }
});

// delete the apcu object cache drop-in of option gets disabled
// @TODO: this can be removed if apcu should be always on and the object-cache.php drop-in is part of the stretch wordpress package
\add_action('update_option_' . IONOS_APCU_OBJECT_CACHE_ENABLED_OPTION, function ($old_value, $new_value) {
  # if apcu extension is not available, fallback to default object cache
  if (! function_exists('apcu_enabled') || ! apcu_enabled()) {
    return;
  }

  if ($new_value == false) {
    $object_cache_php = WP_CONTENT_DIR . '/object-cache.php';
    if (! file_exists($object_cache_php)) {
      return;
    }
    unlink($object_cache_php);
  } elseif ($new_value == true) {
    # if not object-cache.php already exists in the WP_CONTENT_DIR, copy it to enable the apcu object cache drop-in
    $object_cache_php = WP_CONTENT_DIR . '/object-cache.php';
    if (! file_exists($object_cache_php)) {
      copy(IONOS_CUSTOM_DIR . '/object-cache.php', $object_cache_php);
    }
  }
}, 10, 2);
