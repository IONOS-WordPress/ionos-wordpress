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
  public const APCU_OBJECT_CACHE_INSTANTIATED = true;

  private string $prefix = 'wp_';
  private array $cache = [];
  private array $stats = ['hits' => 0, 'misses' => 0];
  private array $non_persistent_groups = ['counts', 'plugins', 'themes'];

  /**
   * Generate cache key
   */
  private function key(string $key, string $group = 'default'): string {
    return $this->prefix . ($group ?: 'default') . ':' . $key;
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
    $group = $group ?: 'default';

    // Always store in local cache
    $this->cache[$group] ??= [];
    $this->cache[$group][$key] = $data;

    // Store in APCu if persistent group
    return $this->is_persistent_group($group)
      ? apcu_store($this->key($key, $group), $data, $expire)
      : true;
  }

  /**
   * Get data from cache
   */
  public function get(int|string $key, string $group = 'default', bool $force = false, bool &$found = null): mixed {
    $group = $group ?: 'default';

    // Check local cache first
    if (!$force && isset($this->cache[$group][$key])) {
      $found = true;
      $this->stats['hits']++;
      return $this->cache[$group][$key];
    }

    // Check APCu for persistent groups
    if ($this->is_persistent_group($group)) {
      $data = apcu_fetch($this->key($key, $group), $success);

      if ($success) {
        $found = true;
        $this->stats['hits']++;
        $this->cache[$group] ??= [];
        return $this->cache[$group][$key] = $data;
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
    $group = $group ?: 'default';
    unset($this->cache[$group][$key]);

    return $this->is_persistent_group($group)
      ? apcu_delete($this->key($key, $group))
      : true;
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
    unset($this->cache[$group]);

    // For APCu, we need to iterate and delete matching keys
    // This is not as efficient, but APCu doesn't support group operations
    if ($this->is_persistent_group($group)) {
      $prefix = $this->prefix . $group . ':';
      $info = apcu_cache_info();

      foreach ($info['cache_list'] ?? [] as $entry) {
        if (isset($entry['info']) && str_starts_with($entry['info'], $prefix)) {
          apcu_delete($entry['info']);
        }
      }
    }

    return true;
  }

  /**
   * Get multiple values
   */
  public function get_multiple(array $keys, string $group = 'default', bool $force = false): array {
    return array_combine($keys, array_map(fn($key) => $this->get($key, $group, $force), $keys));
  }

  /**
   * Set multiple values
   */
  public function set_multiple(array $data, string $group = 'default', int $expire = 0): array {
    return array_map(fn($key, $value) => $this->set($key, $value, $group, $expire), array_keys($data), $data);
  }

  /**
   * Delete multiple values
   */
  public function delete_multiple(array $keys, string $group = 'default'): array {
    return array_combine($keys, array_map(fn($key) => $this->delete($key, $group), $keys));
  }

  /**
   * Increment numeric cache item value
   */
  public function incr(int|string $key, int $offset = 1, string $group = 'default'): int|false {
    $value = $this->get($key, $group ?: 'default');
    if ($value === false) {
      return false;
    }

    $value = (int) $value + $offset;
    $this->set($key, $value, $group);

    return $value;
  }

  /**
   * Decrement numeric cache item value
   */
  public function decr(int|string $key, int $offset = 1, string $group = 'default'): int|false {
    $value = $this->get($key, $group ?: 'default');
    if ($value === false) {
      return false;
    }

    $value = max(0, (int) $value - $offset);
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
    $this->stats = ['hits' => 0, 'misses' => 0];
  }
}

function wp_cache_init(): void {
  $GLOBALS['wp_object_cache'] = new WP_Object_Cache();
}

function wp_cache_add(int|string $key, mixed $data, string $group = '', int $expire = 0): bool {
  return $GLOBALS['wp_object_cache']->add($key, $data, $group, $expire);
}

function wp_cache_replace(int|string $key, mixed $data, string $group = '', int $expire = 0): bool {
  return $GLOBALS['wp_object_cache']->replace($key, $data, $group, $expire);
}

function wp_cache_set(int|string $key, mixed $data, string $group = '', int $expire = 0): bool {
  return $GLOBALS['wp_object_cache']->set($key, $data, $group, $expire);
}

function wp_cache_get(int|string $key, string $group = '', bool $force = false, bool &$found = null): mixed {
  return $GLOBALS['wp_object_cache']->get($key, $group, $force, $found);
}

function wp_cache_delete(int|string $key, string $group = ''): bool {
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

function wp_cache_incr(int|string $key, int $offset = 1, string $group = ''): int|false {
  return $GLOBALS['wp_object_cache']->incr($key, $offset, $group);
}

function wp_cache_decr(int|string $key, int $offset = 1, string $group = ''): int|false {
  return $GLOBALS['wp_object_cache']->decr($key, $offset, $group);
}

function wp_cache_switch_to_blog(int $blog_id): void {
  $GLOBALS['wp_object_cache']->switch_to_blog($blog_id);
}

function wp_cache_add_non_persistent_groups(array|string $groups): void {
  $GLOBALS['wp_object_cache']->add_non_persistent_groups($groups);
}

function wp_cache_reset(): void {
  $GLOBALS['wp_object_cache']->reset();
}
