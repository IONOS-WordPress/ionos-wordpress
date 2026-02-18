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

if (!function_exists('apcu_enabled') || !apcu_enabled()) {
  return; // APCu not available - let WordPress fall back to wp-includes/cache.php
}

/**
 * WordPress Object Cache class using APCu
 */
class WP_Object_Cache {
  /**
   * Key prefix derived from $table_prefix to isolate this WP install's cache entries,
   * preventing collisions when multiple WordPress installs share one APCu instance.
   */
  private string $prefix;

  /**
   * Per-request runtime cache keyed by [$group][$key].
   */
  private array $cache = [];

  /**
   * Groups that must not be stored in APCu (runtime-only).
   * Stored as [$group => true] for O(1) lookup.
   */
  private array $non_persistent_groups = [];

  /**
   * Per-group version numbers used to implement efficient flush_group().
   * Incrementing a group's version makes all its existing APCu entries
   * unreachable without having to iterate the cache.
   */
  private array $group_versions = [];

  public function __construct() {
    global $table_prefix;
    $this->prefix = $table_prefix ?? 'wp_';
  }

  /**
   * Build the APCu key for a given group and cache key.
   * Includes the group's current version to support efficient flush_group().
   */
  private function key($key, string $group): string {
    return $this->prefix . '//' . $group . '//v' . $this->get_group_version($group) . '//' . $key;
  }

  /**
   * Return the current version number for a group.
   * Fetches from APCu on first access per request; cached in memory thereafter.
   */
  private function get_group_version(string $group): int {
    if (!isset($this->group_versions[$group])) {
      $version = apcu_fetch($this->prefix . '//~ver~' . $group, $success);
      if (!$success) {
        // Lazily initialize the version key in APCu so subsequent requests
        // get a hit instead of a miss. apcu_add() is atomic: if two processes
        // race on first access, only one stores; both end up with version 0.
        apcu_add($this->prefix . '//~ver~' . $group, 0);
        $version = 0;
      }
      $this->group_versions[$group] = (int) $version;
    }
    return $this->group_versions[$group];
  }

  /**
   * Clone objects on return to enforce value semantics.
   * Without this a caller can mutate a retrieved object and inadvertently
   * corrupt the in-process cache for the rest of the request.
   */
  private function clone_value($value) {
    return is_object($value) ? clone $value : $value;
  }

  /**
   * Return true if the group should be stored persistently in APCu.
   */
  private function is_persistent_group(string $group): bool {
    return !array_key_exists($group, $this->non_persistent_groups);
  }

  /**
   * Add data to cache only if the key does not already exist.
   */
  public function add($key, $data, string $group = 'default', int $expire = 0): bool {
    if (wp_suspend_cache_addition()) {
      return false;
    }

    $group = $group ?: 'default';

    if (isset($this->cache[$group]) && array_key_exists($key, $this->cache[$group])) {
      return false;
    }

    $data = $this->clone_value($data);

    if ($this->is_persistent_group($group)) {
      // apcu_add() is atomic: it fails if the key already exists in shared memory.
      if (!apcu_add($this->key($key, $group), $data, $expire)) {
        return false;
      }
    }

    $this->cache[$group] ??= [];
    $this->cache[$group][$key] = $data;
    return true;
  }

  /**
   * Replace existing cache data (no-op if key does not exist).
   */
  public function replace($key, $data, string $group = 'default', int $expire = 0): bool {
    $group = $group ?: 'default';
    $data = $this->clone_value($data);

    if (!$this->is_persistent_group($group)) {
      if (!isset($this->cache[$group]) || !array_key_exists($key, $this->cache[$group])) {
        return false;
      }
    } else {
      $full_key = $this->key($key, $group);
      if ((!isset($this->cache[$group]) || !array_key_exists($key, $this->cache[$group])) && !apcu_exists($full_key)) {
        return false;
      }
      apcu_store($full_key, $data, $expire);
    }

    $this->cache[$group] ??= [];
    $this->cache[$group][$key] = $data;
    return true;
  }

  /**
   * Store data in the cache (overwrites existing).
   */
  public function set($key, $data, string $group = 'default', int $expire = 0): bool {
    $group = $group ?: 'default';
    $data = $this->clone_value($data);

    $this->cache[$group] ??= [];
    $this->cache[$group][$key] = $data;

    if ($this->is_persistent_group($group)) {
      return apcu_store($this->key($key, $group), $data, $expire);
    }

    return true;
  }

  /**
   * Retrieve data from the cache.
   * Objects are cloned on return to prevent callers from mutating the cached copy.
   */
  public function get($key, string $group = 'default', bool $force = false, bool &$found = null) {
    $group = $group ?: 'default';

    if (!$force && isset($this->cache[$group]) && array_key_exists($key, $this->cache[$group])) {
      $found = true;
      return $this->clone_value($this->cache[$group][$key]);
    }

    if ($this->is_persistent_group($group)) {
      $data = apcu_fetch($this->key($key, $group), $success);
      if ($success) {
        $found = true;
        $this->cache[$group] ??= [];
        $this->cache[$group][$key] = $this->clone_value($data);
        return $this->cache[$group][$key];
      }
    }

    $found = false;
    return false;
  }

  /**
   * Retrieve multiple cache values using a single APCu batch call for efficiency.
   */
  public function get_multiple(array $keys, string $group = 'default', bool $force = false): array {
    $group = $group ?: 'default';
    $result = [];
    $not_found = [];
    $persistent = $this->is_persistent_group($group);

    $this->cache[$group] ??= [];

    foreach ($keys as $key) {
      if (!$force && array_key_exists($key, $this->cache[$group])) {
        $result[$key] = $this->clone_value($this->cache[$group][$key]);
      } else {
        $not_found[$this->key($key, $group)] = $key;
      }
    }

    if ($persistent && !empty($not_found)) {
      // Single APCu call retrieves all missing keys at once.
      $fetched = apcu_fetch(array_keys($not_found)) ?: [];
      foreach ($fetched as $full_key => $value) {
        $key = $not_found[$full_key];
        unset($not_found[$full_key]);
        $this->cache[$group][$key] = $this->clone_value($value);
        $result[$key] = $value;
      }
    }

    foreach ($not_found as $key) {
      $result[$key] = false;
    }

    return $result;
  }

  /**
   * Store multiple cache values.
   */
  public function set_multiple(array $data, string $group = 'default', int $expire = 0): array {
    $result = [];
    foreach ($data as $key => $value) {
      $result[$key] = $this->set($key, $value, $group, $expire);
    }
    return $result;
  }

  /**
   * Delete a cache entry.
   */
  public function delete($key, string $group = 'default'): bool {
    $group = $group ?: 'default';

    $found = isset($this->cache[$group]) && array_key_exists($key, $this->cache[$group]);
    if ($found) {
      unset($this->cache[$group][$key]);
    }

    if ($this->is_persistent_group($group)) {
      if (apcu_delete($this->key($key, $group))) {
        return true;
      }
    }

    return $found;
  }

  /**
   * Delete multiple cache entries.
   */
  public function delete_multiple(array $keys, string $group = 'default'): array {
    $result = [];
    foreach ($keys as $key) {
      $result[$key] = $this->delete($key, $group);
    }
    return $result;
  }

  /**
   * Flush all cache entries.
   */
  public function flush(): bool {
    $this->cache = [];
    $this->group_versions = [];
    return apcu_clear_cache();
  }

  /**
   * Flush all cache entries for a specific group in O(1) time.
   *
   * Uses a version counter stored in APCu: incrementing the version makes all
   * existing APCu keys for this group unreachable without iterating the cache.
   * Stale entries are evicted naturally by APCu's TTL/LRU mechanism.
   */
  public function flush_group(string $group): bool {
    unset($this->cache[$group]);

    if ($this->is_persistent_group($group)) {
      $version_key = $this->prefix . '//~ver~' . $group;
      $new_version = apcu_inc($version_key, 1, $success);
      if (!$success) {
        apcu_store($version_key, 1);
        $new_version = 1;
      }
      $this->group_versions[$group] = (int) $new_version;
    }

    return true;
  }

  /**
   * Increment a numeric cache value.
   * Uses atomic apcu_inc() for persistent groups to avoid read-modify-write races.
   */
  public function incr($key, int $offset = 1, string $group = 'default') {
    $group = $group ?: 'default';

    if (isset($this->cache[$group]) && array_key_exists($key, $this->cache[$group])) {
      $value = $this->cache[$group][$key];
      if (is_numeric($value)) {
        $value = (int) $value + $offset;
        $this->cache[$group][$key] = $value;
        if ($this->is_persistent_group($group)) {
          apcu_store($this->key($key, $group), $value);
        }
        return $value;
      }
    }

    if (!$this->is_persistent_group($group)) {
      $this->cache[$group] ??= [];
      $this->cache[$group][$key] = $offset;
      return $offset;
    }

    $full_key = $this->key($key, $group);
    $value = apcu_inc($full_key, $offset);
    if (!is_numeric($value)) {
      $value = $offset;
      apcu_store($full_key, $value);
    }
    $this->cache[$group] ??= [];
    $this->cache[$group][$key] = $value;
    return $value;
  }

  /**
   * Decrement a numeric cache value (floor of zero).
   * Uses atomic apcu_dec() for persistent groups to avoid read-modify-write races.
   */
  public function decr($key, int $offset = 1, string $group = 'default') {
    $group = $group ?: 'default';

    if (isset($this->cache[$group]) && array_key_exists($key, $this->cache[$group])) {
      $value = $this->cache[$group][$key];
      if (is_numeric($value)) {
        $value = (int) $value - $offset;
        if ($value < 0) {
          $value = 0;
        }
        $this->cache[$group][$key] = $value;
        if ($this->is_persistent_group($group)) {
          apcu_store($this->key($key, $group), $value);
        }
        return $value;
      }
    }

    if (!$this->is_persistent_group($group)) {
      $this->cache[$group] ??= [];
      $this->cache[$group][$key] = 0;
      return 0;
    }

    $full_key = $this->key($key, $group);
    $value = apcu_dec($full_key, $offset);
    if (!is_numeric($value) || $value < 0) {
      $value = 0;
      apcu_store($full_key, $value);
    }
    $this->cache[$group] ??= [];
    $this->cache[$group][$key] = $value;
    return $value;
  }

  /**
   * Switch blog context (multisite support).
   * Rebuilds the key prefix from $table_prefix so that per-blog entries are
   * properly isolated even when multiple WP installs share an APCu instance.
   */
  public function switch_to_blog(int $blog_id): void {
    global $table_prefix;
    $this->prefix = ($table_prefix ?? 'wp_') . $blog_id . '_';
    $this->cache = [];
    $this->group_versions = [];
  }

  /**
   * Register groups that must not be stored in APCu (runtime-only).
   */
  public function add_non_persistent_groups($groups): void {
    foreach ((array) $groups as $group) {
      $this->non_persistent_groups[$group] = true;
    }
  }

  /**
   * Clear the in-process runtime cache (deprecated wp_cache_reset() support).
   */
  public function reset(): void {
    $this->cache = [];
  }
}

function wp_cache_init(): void {
  $GLOBALS['wp_object_cache'] = new WP_Object_Cache();
}

/**
 * @param int|string $key
 * @param mixed $data
 */
function wp_cache_add($key, $data, string $group = '', int $expire = 0): bool {
  return $GLOBALS['wp_object_cache']->add($key, $data, $group, $expire);
}

/**
 * @param int|string $key
 * @param mixed $data
 */
function wp_cache_replace($key, $data, string $group = '', int $expire = 0): bool {
  return $GLOBALS['wp_object_cache']->replace($key, $data, $group, $expire);
}

/**
 * @param int|string $key
 * @param mixed $data
 */
function wp_cache_set($key, $data, string $group = '', int $expire = 0): bool {
  return $GLOBALS['wp_object_cache']->set($key, $data, $group, $expire);
}

/**
 * @param int|string $key
 * @return mixed
 */
function wp_cache_get($key, string $group = '', bool $force = false, bool &$found = null) {
  return $GLOBALS['wp_object_cache']->get($key, $group, $force, $found);
}

/**
 * @param int|string $key
 */
function wp_cache_delete($key, string $group = ''): bool {
  return $GLOBALS['wp_object_cache']->delete($key, $group);
}

function wp_cache_flush(): bool {
  return $GLOBALS['wp_object_cache']->flush();
}

function wp_cache_flush_group(string $group): bool {
  return $GLOBALS['wp_object_cache']->flush_group($group);
}

function wp_cache_get_multiple(array $keys, string $group = '', bool $force = false): array {
  return $GLOBALS['wp_object_cache']->get_multiple($keys, $group, $force);
}

function wp_cache_set_multiple(array $data, string $group = '', int $expire = 0): array {
  return $GLOBALS['wp_object_cache']->set_multiple($data, $group, $expire);
}

function wp_cache_delete_multiple(array $keys, string $group = ''): array {
  return $GLOBALS['wp_object_cache']->delete_multiple($keys, $group);
}

/**
 * @param int|string $key
 * @return int|false
 */
function wp_cache_incr($key, int $offset = 1, string $group = '') {
  return $GLOBALS['wp_object_cache']->incr($key, $offset, $group);
}

/**
 * @param int|string $key
 * @return int|false
 */
function wp_cache_decr($key, int $offset = 1, string $group = '') {
  return $GLOBALS['wp_object_cache']->decr($key, $offset, $group);
}

function wp_cache_switch_to_blog(int $blog_id): void {
  $GLOBALS['wp_object_cache']->switch_to_blog($blog_id);
}

/**
 * @param mixed[]|string $groups
 */
function wp_cache_add_non_persistent_groups($groups): void {
  $GLOBALS['wp_object_cache']->add_non_persistent_groups($groups);
}

function wp_cache_close(): bool {
  return true;
}

function wp_cache_add_global_groups(array $global_groups): void {
  // Global groups are only relevant for multisite network-wide caching.
  // All groups are stored with the per-site key prefix by default.
}

function wp_cache_reset(): void {
  // Deprecated. Clears only the in-process runtime cache.
  $GLOBALS['wp_object_cache']->reset();
}
