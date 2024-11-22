<?php

namespace wp_plugin\test_plugin\feature_2\frontend;

function hello2()
{
  // phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log
  error_log('hello from packages/wp-plugin/test-plugin/src/feature-2/frontend/feature-2-special.php');
}

hello2();
