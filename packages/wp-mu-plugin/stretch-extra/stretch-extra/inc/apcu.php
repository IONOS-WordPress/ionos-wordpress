<?php

/**
 * APCu Object Cache Feature
 *
 * This feature implements WordPress object caching using the APCu PHP extension.
 *
 * How it works:
 * - Monitors the IONOS_APCU_OBJECT_CACHE_ENABLED_OPTION WordPress option
 * - When enabled (option = '1'), copies object-cache.php drop-in to WP_CONTENT_DIR
 * - When disabled (option != '1'), removes object-cache.php from WP_CONTENT_DIR and flushes APCu cache
 * - Works in both web and WP-CLI contexts
 * - Validates APCu extension availability before enabling
 * - The object-cache.php drop-in file intercepts WordPress object cache calls and uses APCu for storage
 * - Automatic sync on init ensures drop-in state matches option value (useful after WP-CLI changes)
 */

namespace ionos\stretch_extra\apcu;

defined('ABSPATH') || exit();

const IONOS_APCU_OBJECT_CACHE_ENABLED_OPTION = 'IONOS_APCU_OBJECT_CACHE_ENABLED_OPTION';

const OBJECT_CACHE_PATH = WP_CONTENT_DIR . '/object-cache.php';

function is_apcu_available(): bool {
  return function_exists('apcu_enabled') && apcu_enabled();
}

function enable_cache(): bool {
  if (!is_apcu_available()) {
    return false;
  }

  return copy(__DIR__ . '/apcu/object-cache.php', OBJECT_CACHE_PATH);
}

function disable_cache(): bool  {
  // Clear APCu cache if available
  if (is_apcu_available()) {
    apcu_clear_cache();
  }

  if (file_exists(OBJECT_CACHE_PATH)) {
    return unlink(OBJECT_CACHE_PATH);
  }

  return true;
}

/**
 * Handle option value changes - enables or disables cache based on value
 */
function handle_option_change(mixed $value): void {
  if ($value === '1') {
    enable_cache();
  }
  else {
    disable_cache();
  }
}

// Handle option creation (first time set via wp-cli or update_option with autoload)
\add_action(
  hook_name: 'add_option_' . IONOS_APCU_OBJECT_CACHE_ENABLED_OPTION,
  callback: fn(string $option, mixed $value) => handle_option_change($value),
  accepted_args: 2
);

// Handle option updates (subsequent changes)
\add_action(
  hook_name: 'update_option_' . IONOS_APCU_OBJECT_CACHE_ENABLED_OPTION,
  callback: fn(mixed $old_value, mixed $new_value, string $option) => handle_option_change($new_value),
  accepted_args: 3,
);

// Handle option deletion (when using wp-cli delete_option)
\add_action(
  hook_name: 'delete_option_' . IONOS_APCU_OBJECT_CACHE_ENABLED_OPTION,
  callback: __NAMESPACE__ . '\disable_cache',
);

/*
  WORKAROUND for wp-env :
  pnpm wp-env run cli wp commands run in the cli (!) container but not in the web container
  Therefore, when the option is changed via wp-cli, the web container does not reflect the change
  (=> create/delete WP_CONTENT_DIR/object-cache.php).
  this workaround ensures that on init, the web container checks the option value and syncs the state
  (=> create/delete WP_CONTENT_DIR/object-cache.php).
*/
if (!defined('WP_CLI') && getenv('WP_TESTS_DIR')!== false && in_array(wp_get_environment_type(), ['local'], true)) {
  \add_action('init', function() {
    // Ensure cache state matches option value on init (useful after wp-cli changes)
    $enabled = \get_option(IONOS_APCU_OBJECT_CACHE_ENABLED_OPTION) === '1';
    if ($enabled && !file_exists(OBJECT_CACHE_PATH)) {
      enable_cache();
    } else if (!$enabled && file_exists(OBJECT_CACHE_PATH)) {
      disable_cache();
    }
  });
}
