<?php

/*
  checks current apcu object cache status
*/

if (! function_exists('apcu_enabled')) {
  printf('apcu extension not loaded');
}

if (! apcu_enabled()) {
  printf('apcu extension not enabled');
}

$value = get_option('IONOS_APCU_OBJECT_CACHE_ENABLED_OPTION', null);
printf("stretch-extra apcu %s\n", match ($value) {
  null    => 'not initialized',
  true    => 'enabled',
  false   => 'disabled',
  default => sprintf('contains invalid value(=%s)', print_r($value)),
});

$object_cache_php = WP_CONTENT_DIR . '/object-cache.php';
if (! file_exists($object_cache_php)) {
  printf("Error: file '%s' does not exist.\n", $object_cache_php);
}
