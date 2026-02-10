<?php

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

$output = '
+-----------------------------------------------------------+
| APCu SYSTEM STATUS                                        |
+-----------------------------------------------------------+
| Status:           ACTIVE                                  |
| Uptime:           ' . number_format($info['start_time']) . ' (Epoch)              |
| PHP Version:      ' . PHP_VERSION . '                                  |
+-----------------------------------------------------------+
| MEMORY USAGE                                              |
+-----------------------------------------------------------+
| Total RAM:        ' . str_pad(round($total_mem / 1024 / 1024, 2) . ' MB', 20) . '           |
| Used RAM:         ' . str_pad(round($used_mem / 1024 / 1024, 2) . " MB ({$load_pct}%)", 20) . '    |
| Free RAM:         ' . str_pad(round($free_mem / 1024 / 1024, 2) . ' MB', 20) . "           |
| Graph:            [{$bar}]                                |
+-----------------------------------------------------------+
| PERFORMANCE METRICS                                       |
+-----------------------------------------------------------+
| Hits:             " . str_pad(number_format($hits), 20) . '           |
| Misses:           ' . str_pad(number_format($misses), 20) . '           |
| Hit-Rate:         ' . str_pad($hit_rate . '%', 20) . '           |
| Cached Keys:      ' . str_pad(number_format($info['num_entries']), 20) . '           |
+-----------------------------------------------------------+
';

echo $output;

// Check for issues
if ($hit_rate < 80 && $total > 50) {
  echo "(!) WARNING: Low hit rate. Check if your cache keys are volatile.\n";
}
if ($load_pct > 90) {
  echo "(!) DANGER: Memory is nearly full (apc.shm_size).\n";
}

echo "\n[End of Report]\n";
