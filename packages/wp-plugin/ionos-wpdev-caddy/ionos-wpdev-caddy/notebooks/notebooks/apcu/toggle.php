<?php

/*
  toggles apcu object cache on or off by changing the IONOS_APCU_OBJECT_CACHE_ENABLED_option option value
*/

$option = 'IONOS_APCU_OBJECT_CACHE_ENABLED_OPTION';
$value  = get_option($option, null);

switch ($value) {
  case '1':
    printf("disabling stretch-extra apcu object cache...\n");
    delete_option($option);
    break;
  case null:
  default:
    printf("enabling stretch-extra apcu object cache...\n");
    update_option($option, '1');
    break;
}

printf("%s(=%s)\n", $option, print_r(get_option($option, null), true));

$object_cache_php = WP_CONTENT_DIR . '/object-cache.php';
printf("\$WP_CONTENT_DIR/object-cache.php exists: %s\n", print_r(file_exists($object_cache_php)));
