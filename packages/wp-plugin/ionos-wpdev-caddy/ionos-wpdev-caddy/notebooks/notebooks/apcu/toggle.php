<?php

/*
  toggles apcu object cache on or off by changing the IONOS_APCU_OBJECT_CACHE_ENABLED_option option value
*/

$option = 'IONOS_APCU_OBJECT_CACHE_ENABLED_OPTION';
$value  = get_option($option, null);

switch ($value) {
  case null:
    printf("stretch-extra apcu object cache was not initialized.\n");
    // no break
  case '1':
  case 1:
  case true:
    printf("disabling stretch-extra apcu object cache...\n");
    update_option($option, false, true);
    break;
  case '0':
  case 0:
  case false:
    printf("enabling stretch-extra apcu object cache...\n");
    update_option($option, true, true);
    break;
  default:
    printf("Error: option '%s' contains invalid value(=%s).\n", $option, print_r($value, true));
}

printf(get_option($option));
