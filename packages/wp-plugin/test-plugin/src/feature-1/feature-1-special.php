<?php

namespace test_plugin\feature_1;

function hello2() {
  error_log('hello from packages/wp-plugin/test-plugin/src/feature-1/feature-1-special.php');
}

hello2();
