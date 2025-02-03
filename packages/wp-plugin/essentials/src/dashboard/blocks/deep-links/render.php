<?php

$tenant = strtolower(\get_option('ionos_group_brand', 'ionos'));

$config_file = __DIR__ . '/config/' . $tenant . '.php';

if (file_exists($config_file)) {
  require($config_file);

  $market = strToLower(\get_option($tenant . '_market', 'de'));
  $domain = $market_domains[$market] ?? reset($market_domains);

  require('view.php');
}
