<?php

namespace wp_plugin\test_plugin\feature_2\backend;

function hello()
{
  // phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log
  error_log('hello from packages/wp-plugin/test-plugin/src/feature-2/backend/feature-2.php');
}

hello();
