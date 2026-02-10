<?php

/**
 * WordPress Object Cache Implementation using APCu
 *
 * This file serves as a WordPress drop-in replacement for the default object cache.
 * It uses APCu (Alternative PHP Cache - User Cache) for persistent in-memory caching.
 *
 * When this file is placed in WP_CONTENT_DIR/object-cache.php, WordPress will automatically
 * use it instead of the default transient-based cache.
 *
 * APCu provides fast shared memory caching across PHP processes, significantly improving
 * performance for repeated database queries and computed values.
 */

defined('ABSPATH') || exit();

/**
 * WordPress Object Cache class using APCu
 */
class WP_Object_Cache {
  /**
   * Cache group prefix
   */
  private string $prefix = 'wp_';

  /**
   * Non-persistent groups (not cached in APCu)
   */
  private array $non_persistent_groups = [];

  /**
   * Local cache for current request
   */
  private array $cache = [];

  /**
   * Cache statistics
   */
  private array $stats = [
    'hits' => 0,
    'misses' => 0,
  ];

  /**
   * Constructor
   */
  public function __construct() {
    // Set default non-persistent groups
    $this->non_persistent_groups = [
      'counts',
      'plugins',
      'themes',
    ];
  }

  /**
   * Generate cache key
   */
  private function key(string $key, string $group = 'default'): string {
    if (empty($group)) {
      $group = 'default';
    }
    return $this->prefix . $group . ':' . $key;
  }

  /**
   * Check if group should be cached persistently
   */
  private function is_persistent_group(string $group): bool {
    return !in_array($group, $this->non_persistent_groups, true);
  }

  /**
   * Add data to cache
   */
  public function add(int|string $key, mixed $data, string $group = 'default', int $expire = 0): bool {
    if ($this->_exists($key, $group)) {
      return false;
    }

    return $this->set($key, $data, $group, $expire);
  }

  /**
   * Replace data in cache
   */
  public function replace(int|string $key, mixed $data, string $group = 'default', int $expire = 0): bool {
    if (!$this->_exists($key, $group)) {
      return false;
    }

    return $this->set($key, $data, $group, $expire);
  }

  /**
   * Set data in cache
   */
  public function set(int|string $key, mixed $data, string $group = 'default', int $expire = 0): bool {
    if (empty($group)) {
      $group = 'default';
    }

    // Always store in local cache
    if (!isset($this->cache[$group])) {
      $this->cache[$group] = [];
    }
    $this->cache[$group][$key] = $data;

    // Store in APCu if persistent group
    if ($this->is_persistent_group($group)) {
      $cache_key = $this->key($key, $group);
      return apcu_store($cache_key, $data, $expire);
    }

    return true;
  }

  /**
   * Get data from cache
   */
  public function get(int|string $key, string $group = 'default', bool $force = false, bool &$found = null): mixed {
    if (empty($group)) {
      $group = 'default';
    }

    // Check local cache first
    if (!$force && isset($this->cache[$group][$key])) {
      $found = true;
      $this->stats['hits']++;
      return $this->cache[$group][$key];
    }

    // Check APCu for persistent groups
    if ($this->is_persistent_group($group)) {
      $cache_key = $this->key($key, $group);
      $data = apcu_fetch($cache_key, $success);

      if ($success) {
        $found = true;
        $this->stats['hits']++;

        // Store in local cache
        if (!isset($this->cache[$group])) {
          $this->cache[$group] = [];
        }
        $this->cache[$group][$key] = $data;

        return $data;
      }
    }

    $found = false;
    $this->stats['misses']++;
    return false;
  }

  /**
   * Delete data from cache
   */
  public function delete(int|string $key, string $group = 'default'): bool {
    if (empty($group)) {
      $group = 'default';
    }

    // Remove from local cache
    if (isset($this->cache[$group][$key])) {
      unset($this->cache[$group][$key]);
    }

    // Remove from APCu
    if ($this->is_persistent_group($group)) {
      $cache_key = $this->key($key, $group);
      return apcu_delete($cache_key);
    }

    return true;
  }

  /**
   * Flush all cache
   */
  public function flush(): bool {
    $this->cache = [];
    return apcu_clear_cache();
  }

  /**
   * Flush cache for specific group
   */
  public function flush_group(string $group): bool {
    if (isset($this->cache[$group])) {
      unset($this->cache[$group]);
    }

    // For APCu, we need to iterate and delete matching keys
    // This is not as efficient, but APCu doesn't support group operations
    if ($this->is_persistent_group($group)) {
      $prefix = $this->prefix . $group . ':';
      $info = apcu_cache_info();

      if (isset($info['cache_list'])) {
        foreach ($info['cache_list'] as $entry) {
          if (isset($entry['info']) && str_starts_with($entry['info'], $prefix)) {
            apcu_delete($entry['info']);
          }
        }
      }
    }

    return true;
  }

  /**
   * Get multiple values
   */
  public function get_multiple(array $keys, string $group = 'default', bool $force = false): array {
    $values = [];

    foreach ($keys as $key) {
      $values[$key] = $this->get($key, $group, $force);
    }

    return $values;
  }

  /**
   * Set multiple values
   */
  public function set_multiple(array $data, string $group = 'default', int $expire = 0): array {
    $results = [];

    foreach ($data as $key => $value) {
      $results[$key] = $this->set($key, $value, $group, $expire);
    }

    return $results;
  }

  /**
   * Delete multiple values
   */
  public function delete_multiple(array $keys, string $group = 'default'): array {
    $results = [];

    foreach ($keys as $key) {
      $results[$key] = $this->delete($key, $group);
    }

    return $results;
  }

  /**
   * Increment numeric cache item value
   */
  public function incr(int|string $key, int $offset = 1, string $group = 'default'): int|false {
    if (empty($group)) {
      $group = 'default';
    }

    $value = $this->get($key, $group);

    if ($value === false) {
      return false;
    }

    if (!is_numeric($value)) {
      $value = 0;
    }

    $offset = (int) $offset;
    $value = (int) $value + $offset;

    $this->set($key, $value, $group);

    return $value;
  }

  /**
   * Decrement numeric cache item value
   */
  public function decr(int|string $key, int $offset = 1, string $group = 'default'): int|false {
    if (empty($group)) {
      $group = 'default';
    }

    $value = $this->get($key, $group);

    if ($value === false) {
      return false;
    }

    if (!is_numeric($value)) {
      $value = 0;
    }

    $offset = (int) $offset;
    $value = (int) $value - $offset;

    // Ensure value doesn't go below 0
    if ($value < 0) {
      $value = 0;
    }

    $this->set($key, $value, $group);

    return $value;
  }

  /**
   * Switch blog prefix (multisite support)
   */
  public function switch_to_blog(int $blog_id): void {
    $this->prefix = 'wp_' . $blog_id . '_';
    $this->cache = [];
  }

  /**
   * Add non-persistent groups
   */
  public function add_non_persistent_groups(array|string $groups): void {
    $groups = (array) $groups;
    $this->non_persistent_groups = array_unique(array_merge($this->non_persistent_groups, $groups));
  }

  /**
   * Get cache statistics
   */
  public function stats(): array {
    return $this->stats;
  }

  /**
   * Check if key exists (internal method)
   */
  private function _exists(int|string $key, string $group = 'default'): bool {
    $found = false;
    $this->get($key, $group, false, $found);
    return $found;
  }

  /**
   * Reset cache
   */
  public function reset(): void {
    $this->cache = [];
    $this->stats = [
      'hits' => 0,
      'misses' => 0,
    ];
  }
}

/**
 * Global cache object
 */
$GLOBALS['wp_object_cache'] = new WP_Object_Cache();

/**
 * WordPress cache functions
 *
 * These functions are wrapped in function_exists() checks for backwards compatibility:
 * - WordPress 6.9+: cache.php is loaded first and defines these functions, so they won't be redefined
 * - Older WordPress: object-cache.php completely replaces cache.php, so these functions must be defined
 */

if (!function_exists('wp_cache_add')) {
  /**
   * Adds data to the cache, if the cache key doesn't already exist.
   *
   * @param int|string $key The cache key to use for retrieval later.
   * @param mixed $data The data to add to the cache.
   * @param string $group Optional. The group to add the cache to. Default empty.
   * @param int $expire Optional. When the cache data should expire, in seconds. Default 0 (no expiration).
   * @return bool True on success, false if cache key already exists.
   */
  function wp_cache_add(int|string $key, mixed $data, string $group = '', int $expire = 0): bool {
    return $GLOBALS['wp_object_cache']->add($key, $data, $group, $expire);
  }
}

if (!function_exists('wp_cache_replace')) {
  /**
   * Replaces the contents of the cache with new data.
   *
   * @param int|string $key The cache key to use for retrieval later.
   * @param mixed $data The data to replace in the cache.
   * @param string $group Optional. The group to add the cache to. Default empty.
   * @param int $expire Optional. When the cache data should expire, in seconds. Default 0 (no expiration).
   * @return bool False if the cache key doesn't exist, true otherwise.
   */
  function wp_cache_replace(int|string $key, mixed $data, string $group = '', int $expire = 0): bool {
    return $GLOBALS['wp_object_cache']->replace($key, $data, $group, $expire);
  }
}

if (!function_exists('wp_cache_set')) {
  /**
   * Saves the data to the cache.
   *
   * @param int|string $key The cache key to use for retrieval later.
   * @param mixed $data The data to save in the cache.
   * @param string $group Optional. The group to add the cache to. Default empty.
   * @param int $expire Optional. When the cache data should expire, in seconds. Default 0 (no expiration).
   * @return bool True on success, false on failure.
   */
  function wp_cache_set(int|string $key, mixed $data, string $group = '', int $expire = 0): bool {
    return $GLOBALS['wp_object_cache']->set($key, $data, $group, $expire);
  }
}

if (!function_exists('wp_cache_get')) {
  /**
   * Retrieves the cache contents from the cache by key and group.
   *
   * @param int|string $key The cache key to retrieve.
   * @param string $group Optional. The group the cache is in. Default empty.
   * @param bool $force Optional. Whether to force an update of the local cache. Default false.
   * @param bool &$found Optional. Whether the key was found in the cache. Default null.
   * @return mixed The cache contents on success, false on failure.
   */
  function wp_cache_get(int|string $key, string $group = '', bool $force = false, bool &$found = null): mixed {
    return $GLOBALS['wp_object_cache']->get($key, $group, $force, $found);
  }
}

if (!function_exists('wp_cache_delete')) {
  /**
   * Removes the cache contents matching key and group.
   *
   * @param int|string $key The cache key to delete.
   * @param string $group Optional. The group the cache is in. Default empty.
   * @return bool True on successful removal, false on failure.
   */
  function wp_cache_delete(int|string $key, string $group = ''): bool {
    return $GLOBALS['wp_object_cache']->delete($key, $group);
  }
}

if (!function_exists('wp_cache_flush')) {
  /**
   * Removes all cache items.
   *
   * @return bool True on success, false on failure.
   */
  function wp_cache_flush(): bool {
    return $GLOBALS['wp_object_cache']->flush();
  }
}

if (!function_exists('wp_cache_flush_group')) {
  /**
   * Removes all cache items in a group.
   *
   * @param string $group The group to flush.
   * @return bool True on success, false on failure.
   */
  function wp_cache_flush_group(string $group): bool {
    return $GLOBALS['wp_object_cache']->flush_group($group);
  }
}

if (!function_exists('wp_cache_get_multiple')) {
  /**
   * Retrieves multiple values from the cache in one call.
   *
   * @param array $keys Array of keys to retrieve.
   * @param string $group Optional. The group the cache is in. Default empty.
   * @param bool $force Optional. Whether to force an update of the local cache. Default false.
   * @return array Array of values, indexed by key.
   */
  function wp_cache_get_multiple(array $keys, string $group = '', bool $force = false): array {
    return $GLOBALS['wp_object_cache']->get_multiple($keys, $group, $force);
  }
}

if (!function_exists('wp_cache_set_multiple')) {
  /**
   * Sets multiple values to the cache in one call.
   *
   * @param array $data Array of key => value pairs to set.
   * @param string $group Optional. The group to add the cache to. Default empty.
   * @param int $expire Optional. When the cache data should expire, in seconds. Default 0 (no expiration).
   * @return array Array of return values, indexed by key.
   */
  function wp_cache_set_multiple(array $data, string $group = '', int $expire = 0): array {
    return $GLOBALS['wp_object_cache']->set_multiple($data, $group, $expire);
  }
}

if (!function_exists('wp_cache_delete_multiple')) {
  /**
   * Deletes multiple values from the cache in one call.
   *
   * @param array $keys Array of keys to delete.
   * @param string $group Optional. The group the cache is in. Default empty.
   * @return array Array of return values, indexed by key.
   */
  function wp_cache_delete_multiple(array $keys, string $group = ''): array {
    return $GLOBALS['wp_object_cache']->delete_multiple($keys, $group);
  }
}

if (!function_exists('wp_cache_incr')) {
  /**
   * Increments numeric cache item's value.
   *
   * @param int|string $key The cache key to increment.
   * @param int $offset Optional. The amount by which to increment. Default 1.
   * @param string $group Optional. The group the cache is in. Default empty.
   * @return int|false The item's new value on success, false on failure.
   */
  function wp_cache_incr(int|string $key, int $offset = 1, string $group = ''): int|false {
    return $GLOBALS['wp_object_cache']->incr($key, $offset, $group);
  }
}

if (!function_exists('wp_cache_decr')) {
  /**
   * Decrements numeric cache item's value.
   *
   * @param int|string $key The cache key to decrement.
   * @param int $offset Optional. The amount by which to decrement. Default 1.
   * @param string $group Optional. The group the cache is in. Default empty.
   * @return int|false The item's new value on success, false on failure.
   */
  function wp_cache_decr(int|string $key, int $offset = 1, string $group = ''): int|false {
    return $GLOBALS['wp_object_cache']->decr($key, $offset, $group);
  }
}

if (!function_exists('wp_cache_switch_to_blog')) {
  /**
   * Switches the internal blog ID.
   *
   * This changes the blog ID used to create keys in blog-specific groups.
   *
   * @param int $blog_id Blog ID.
   */
  function wp_cache_switch_to_blog(int $blog_id): void {
    $GLOBALS['wp_object_cache']->switch_to_blog($blog_id);
  }
}

if (!function_exists('wp_cache_add_non_persistent_groups')) {
  /**
   * Adds a group or set of groups to the list of non-persistent groups.
   *
   * @param string|array $groups A group or an array of groups to add.
   */
  function wp_cache_add_non_persistent_groups(array|string $groups): void {
    $GLOBALS['wp_object_cache']->add_non_persistent_groups($groups);
  }
}

if (!function_exists('wp_cache_reset')) {
  /**
   * Resets cache keys.
   */
  function wp_cache_reset(): void {
    $GLOBALS['wp_object_cache']->reset();
  }
}
