<?php

$config_file = __DIR__ . '/config.php';

if (file_exists($config_file)) {
  require($config_file);

  require('view.php');
}
