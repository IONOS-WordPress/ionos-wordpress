<?php

namespace ionos\essentials\wpscan;

require_once __DIR__ . '/class-wpscan.php';
require_once __DIR__ . '/views/summary.php';
require_once __DIR__ . '/views/issues.php';

\add_action('init', function () {
  global $wpscan;
  $wpscan = new WPScan();
});


add_action('upgrader_process_complete', function ($upgrader, $options) {
  delete_transient('ionos_wpscan_issues');
}, 10, 2);

function get_wpscan(): WPScan
{
  global $wpscan;

  return $wpscan;
}
