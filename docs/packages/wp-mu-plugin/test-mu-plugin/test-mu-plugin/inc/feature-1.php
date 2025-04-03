<?php

namespace ionos\test_mu_plugin\feature_1;

function feature_1(): void
{
  // phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log
  error_log('hello from ionos\test_mu_plugin\feature_1');
}

feature_1();
