<?php

namespace ionos\essentials\wpscan;

require_once __DIR__ . '/class-wpscan.php';
require_once __DIR__ . '/views/summary.php';
require_once __DIR__ . '/views/issues.php';

\add_action('init', function () {
  global $wpscan;
  $wpscan = new WPScan();
});

function has_issues(): bool
{
  global $wpscan;

  return ! empty($wpscan->get_issues());
}
