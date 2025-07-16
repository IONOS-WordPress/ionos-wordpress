<?php

namespace ionos\essentials\wpscan;

require_once __DIR__ . '/class-wpscan.php';
require_once __DIR__ . '/views/summary.php';
require_once __DIR__ . '/views/issues.php';

$wpscan = new WPScan();

function has_issues(): bool
{
  global $wpscan;

  return ! empty($wpscan->get_issues());
}
