<?php

namespace ionos\essentials\wpscan;

require_once __DIR__ . '/controller/class-wpscan.php';
require_once __DIR__ . '/controller/class-wpscanmiddleware.php';
require_once __DIR__ . '/controller/class-wpscanrest.php';
require_once __DIR__ . '/views/summary.php';
require_once __DIR__ . '/views/issues.php';

\add_action('init', function () {
  global $wpscan;
  $wpscan = new WPScan();
});

function get_wpscan(): WPScan
{
  global $wpscan;

  return $wpscan;
}
