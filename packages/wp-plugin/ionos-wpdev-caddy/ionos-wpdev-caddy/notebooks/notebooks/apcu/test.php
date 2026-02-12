<?php

/**
 * Object Cache Validation Test
 *
 * Quick test to verify object cache functionality.
 * Run via WP-CLI: wp eval-file test-cache.php
 */

defined('ABSPATH') || exit();

// Test configuration
$test_key   = 'cache_test_' . time();
$test_value = [
  'data'      => 'test',
  'timestamp' => time(),
];
$test_group = 'test_group';

printf("Testing APCu Object Cache...\n\n");

// Test 1: Set cache
$set_result = \wp_cache_set($test_key, $test_value, $test_group, 300);
printf("✓ Set cache: %s\n", $set_result ? 'SUCCESS' : 'FAILED');

// Test 2: Get cache
$get_result = \wp_cache_get($test_key, $test_group);
$matches    = $get_result === $test_value;
printf("✓ Get cache: %s\n", $matches ? 'SUCCESS' : 'FAILED');

if (! $matches) {
  printf("  Expected: %s\n", print_r($test_value, true));
  printf("  Got: %s\n", print_r($get_result, true));
}

// Test 3: Check if persistent (APCu)
$is_persistent = \wp_using_ext_object_cache();
printf("✓ Persistent cache: %s\n", $is_persistent ? 'ENABLED (APCu)' : 'DISABLED (default)');

// Test 4: Delete cache
$delete_result = \wp_cache_delete($test_key, $test_group);
printf("✓ Delete cache: %s\n", $delete_result ? 'SUCCESS' : 'FAILED');

// Test 5: Verify deletion
$verify_deleted = \wp_cache_get($test_key, $test_group);
$is_deleted     = $verify_deleted === false;
printf("✓ Verify deletion: %s\n\n", $is_deleted ? 'SUCCESS' : 'FAILED');

// Summary
$all_passed = $set_result && $matches && $delete_result && $is_deleted;
printf("Result: %s\n", $all_passed ? '✅ All tests passed' : '❌ Some tests failed');

if ($is_persistent) {
  printf("\nAPCu Stats:\n");
  $info = \apcu_cache_info(true);
  printf("  Memory used: %s\n", size_format($info['mem_size'] ?? 0));
  printf("  Cached entries: %d\n", $info['num_entries'] ?? 0);
}
