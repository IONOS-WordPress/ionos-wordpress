<?php

$tenant = strtolower(get_option('ionos_group_brand_name', false));

$config_file = __DIR__ . '/config/' . $tenant . '.php';

if ($tenant && file_exists($config_file)) {
  require($config_file);

  require('view.php');
}
