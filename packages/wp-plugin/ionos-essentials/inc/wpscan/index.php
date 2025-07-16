<?php

namespace ionos\essentials\wpscan;

require_once __DIR__ . '/class-wpscan.php';
$wpscan = new WPScan();

require_once __DIR__ . '/views/summary.php';
require_once __DIR__ . '/views/issues.php';
