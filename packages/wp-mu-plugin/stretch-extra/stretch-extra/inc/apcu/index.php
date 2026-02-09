<?php

namespace ionos\stretch_extra\apcu;

defined('ABSPATH') || exit();

const IONOS_APCU_OBJECT_CACHE_ENABLED_OPTION = 'IONOS_APCU_OBJECT_CACHE_ENABLED_OPTION';

// if apcu extension is not available, fallback to default object cache
if (! function_exists('apcu_enabled') || !apcu_enabled()) {
  return;
}

// delete the apcu object cache drop-in of option gets disabled
// copy the apcu object cache drop-in if option gets enabled and it does not exist in the WP_CONTENT_DIR
// @TODO: this can be removed if apcu should be always on and the object-cache.php drop-in is part of the stretch wordpress package
function apcu_object_cache_enabled_option_changed($value) : void {
  $object_cache_php = WP_CONTENT_DIR . '/object-cache.php';
  if ($value == false) {
    if (! file_exists($object_cache_php)) {
      return;
    }

    unlink($object_cache_php);
  } elseif ($value == true) {
    # if not object-cache.php already exists in the WP_CONTENT_DIR, copy it to enable the apcu object cache drop-in
    if (file_exists($object_cache_php)) {
      return;
    }

    copy(__DIR__ . '/object-cache.php', $object_cache_php);
  }
}

\add_action(
  hook_name: 'update_option_' . IONOS_APCU_OBJECT_CACHE_ENABLED_OPTION,
  callback: fn($old_value, $new_value) => apcu_object_cache_enabled_option_changed($new_value),
  accepted_args: 2,
);

\add_action(
  hook_name: 'delete_option_' . IONOS_APCU_OBJECT_CACHE_ENABLED_OPTION,
  callback: fn($option) => apcu_object_cache_enabled_option_changed(false),
  accepted_args: 1,
);

// initialize IONOS_APCU_OBJECT_CACHE_ENABLED_OPTION
// if the option does not exist, set it to true if apcu is enabled, otherwise false
// @TODO: this can be removed once the stretch database initialization contains it
\add_action('muplugins_loaded', function () {
  $value = \get_option(IONOS_APCU_OBJECT_CACHE_ENABLED_OPTION, null);

  if ($value !== null) {
    return;
  }

  // \add_option(
  //   option: IONOS_APCU_OBJECT_CACHE_ENABLED_OPTION,
  //   value : true,
  //   autoload :true,
  // );

  // apcu_object_cache_enabled_option_changed(true);
});
