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

printf("%s(=%s)\n\n", $option, print_r(get_option($option, null), true));

printf("[wp-env] CAVEAT: object-cache.php is only %s on next request !\n", ($value === '1' ? 'enabled' : 'disabled'));
