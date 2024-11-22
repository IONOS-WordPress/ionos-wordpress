<?php

namespace test_plugin\feature_1;

function hello(): void
{
  // phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log
  error_log('hello from packages/wp-plugin/test-plugin/src/feature-1/feature-1.php');
}

hello();
