<?php

namespace ionos\test_plugin\feature_2;

function hello(): void
{
  error_log('hello from packages/wp-plugin/test-plugin/src/feature-2/feature-2.php');
}

hello();

require_once __DIR__ . '/backend/feature-2.php';
require_once __DIR__ . '/frontend/feature-2.php';
