<?php

/*
  Quick APCu configuration display
  To ensure APCu doesn't just "lock up" when it gets full, you should tune these settings in your server configuration:
  Setting          Recommended Value    Why?
  apc.shm_size     128M to 256M         Gives WP enough room so it doesn't constantly evict.
  apc.ttl          3600                 Defines how long a "stale" entry can sit if the cache is full.
  apc.user_ttl     3600                 Same as above, but specifically for user data (like WP objects).
  apc.gc_ttl       3600                 The "Garbage Collection" interval.
 */

if (! \extension_loaded('apcu')) {
  die('APCu extension not loaded');
}

echo "APCu Configuration:\n";
echo \str_repeat('-', 60) . "\n";

$directives = [
  'apc.enabled',
  'apc.shm_size',
  'apc.shm_segments',
  'apc.entries_hint',
  'apc.ttl',
  'apc.gc_ttl',
  'apc.mmap_file_mask',
  'apc.slam_defense',
  'apc.enable_cli',
  'apc.use_request_time',
  'apc.serializer',
  'apc.coredump_unmap',
  'apc.preload_path',
];

\array_walk($directives, fn ($d) => printf("%-25s %s\n", $d . ':', \ini_get($d) ?: '(not set)'));

// Cache stats
if (\ini_get('apc.enabled')) {
  $info = \apcu_cache_info(true);
  echo "\nCache Stats:\n";
  echo \str_repeat('-', 60) . "\n";
  printf(
    "Entries: %d | Hits: %d | Misses: %d | Memory: %.2f%%\n",
    $info['num_entries'],
    $info['num_hits'],
    $info['num_misses'],
    (($info['mem_size'] - $info['avail_mem']) / $info['mem_size']) * 100
  );
}
