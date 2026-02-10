<?php

/*
  checks current apcu object cache status
*/

if (! function_exists('apcu_enabled')) {
  printf("apcu extension not loaded\n");
}

if (function_exists('apcu_enabled') && !apcu_enabled()) {
  printf("apcu extension not enabled\n");
}

$value = get_option('IONOS_APCU_OBJECT_CACHE_ENABLED_OPTION', null);
printf("stretch-extra apcu %s\n", match ($value) {
  null    => 'not initialized',
  '1'     => 'enabled',
  default => 'disabled',
});

$object_cache_php = WP_CONTENT_DIR . '/object-cache.php';
printf("\$WP_CONTENT_DIR/object-cache.php exists: %s\n", print_r(file_exists($object_cache_php)));