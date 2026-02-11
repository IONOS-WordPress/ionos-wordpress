<?php

/**
 * APCu Statistics Display
 *
 * Formats and displays APCu cache statistics in a pretty ASCII table format.
 */

$info = apcu_cache_info();
$mem  = apcu_sma_info();

// Calculations
$total_mem = $mem['num_seg'] * $mem['seg_size'];
$free_mem  = $mem['avail_mem'];
$used_mem  = $total_mem - $free_mem;
$hits      = $info['num_hits'];
$misses    = $info['num_misses'];
$total     = $hits + $misses;
$hit_rate  = $total > 0 ? round(($hits / $total) * 100, 2) : 0;
$load_pct  = round(($used_mem / $total_mem) * 100, 0);

// Simple ASCII Progress Bar for Memory
$bar_width = 20;
$progress  = (int) round($load_pct / (100 / $bar_width));
$bar       = str_repeat('█', $progress) . str_repeat('░', $bar_width - $progress);

// Formatting constants
$box_width   = 59;
$label_width = 18;
$value_width = $box_width - $label_width - 5; // 5 = '| ' (2) + ' ' (1) + ' |' (2)

/**
 * Format a row with label and value, properly aligned.
 *
 * @param string $label Left-aligned label
 * @param string $value Right-aligned value
 * @return string Formatted row
 */
$format_row = fn (string $label, string $value): string =>
  '│ ' . str_pad($label . ':', $label_width) . ' ' . str_pad($value, $value_width, ' ', STR_PAD_LEFT) . ' │';

/**
 * Create divider lines with box-drawing characters.
 *
 * @return array Formatted dividers
 */
$top_border    = '┌' . str_repeat('─', $box_width - 2) . '┐';
$middle_border = '├' . str_repeat('─', $box_width - 2) . '┤';
$bottom_border = '└' . str_repeat('─', $box_width - 2) . '┘';

/**
 * Create a header row (centered text).
 *
 * @param string $text Header text
 * @return string Formatted header
 */
$format_header = fn (string $text): string =>
  '│ ' . str_pad($text, $box_width - 4, ' ', STR_PAD_BOTH) . ' │';

// Build output
$output = implode("\n", [
  $top_border,
  $format_header('APCu SYSTEM STATUS'),
  $middle_border,
  $format_row('Status', 'ACTIVE'),
  $format_row('Uptime', number_format($info['start_time']) . ' (Epoch)'),
  $format_row('PHP Version', PHP_VERSION),
  $middle_border,
  $format_header('MEMORY USAGE'),
  $middle_border,
  $format_row('Total RAM', round($total_mem / 1024 / 1024, 1) . ' MB'),
  $format_row('Used RAM', round($used_mem / 1024 / 1024, 1) . ' MB (' . $load_pct . '%)'),
  $format_row('Free RAM', round($free_mem / 1024 / 1024, 1) . ' MB'),
  $format_row('Graph', '[' . $bar . ']'),
  $middle_border,
  $format_header('PERFORMANCE METRICS'),
  $middle_border,
  $format_row('Hits', number_format($hits)),
  $format_row('Misses', number_format($misses)),
  $format_row('Hit Rate', $hit_rate . '%'),
  $format_row('Cached Keys', number_format($info['num_entries'])),
  $bottom_border,
]);

echo $output . "\n";

// Check for issues
if ($hit_rate < 80 && $total > 50) {
  echo "(!) WARNING: Low hit rate. Check if your cache keys are volatile.\n";
}
if ($load_pct > 90) {
  echo "(!) DANGER: Memory is nearly full (apc.shm_size).\n";
}
