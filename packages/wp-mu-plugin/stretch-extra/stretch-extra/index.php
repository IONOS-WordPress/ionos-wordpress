<?php

/*
 * this file is the main entrypoint for stretch-extra
 */

namespace ionos\stretch_extra;

defined('ABSPATH') || exit();

/*
   require_once
   - all PHP files
   - and directories with index.php
   from inc directory
*/

const MU_FEATURES = __DIR__ . '/inc';

if (!is_dir(MU_FEATURES)) {
  error_log(
    sprintf(
      'skip loading mu plugin features : directory(=%s) does not exist',
       MU_FEATURES
    )
  );
  return;
}

$items = scandir(MU_FEATURES);

foreach ($items as $item) {
  if ($item === '.' || $item === '..') {
    continue;
  }

  $item_path = MU_FEATURES . '/' . $item;

  // If it's a PHP file, require it
  if (is_file($item_path) && pathinfo($item_path, PATHINFO_EXTENSION) === 'php') {
    require_once $item_path;
  }

  // If it's a directory containing index.php, require the index.php
  elseif (is_dir($item_path)) {
    $index_file = $item_path . '/index.php';
    if (file_exists($index_file)) {
      require_once $index_file;
    }
  }
}

