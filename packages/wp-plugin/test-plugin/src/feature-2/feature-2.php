<?php

namespace test_plugin\feature_2;

function hello()
{
  // phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log
  error_log('hello from packages/wp-plugin/test-plugin/src/feature-2/feature-2.php');
}

hello();
