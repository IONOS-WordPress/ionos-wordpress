<?php
/**
 * Plugin Name: IONOS APCu Object Cache Drop-In
 * Description: A high-performance APCu-based object cache backend for WordPress.
 * Version: 1.0.0
 * License: MIT
 */

defined('ABSPATH') || exit;

/**
 * Global functions to interface with the WP_Object_Cache instance.
 */
function wp_cache_add($key, $data, $group = '', $expire = 0) {
    return WP_Object_Cache::get_instance()->add($key, $data, $group, $expire);
}

function wp_cache_set($key, $data, $group = '', $expire = 0) {
    return WP_Object_Cache::get_instance()->set($key, $data, $group, $expire);
}

function wp_cache_get($key, $group = '', $force = false, &$found = null) {
    return WP_Object_Cache::get_instance()->get($key, $group, $force, $found);
}

function wp_cache_delete($key, $group = '') {
    return WP_Object_Cache::get_instance()->delete($key, $group);
}

function wp_cache_replace($key, $data, $group = '', $expire = 0) {
    return WP_Object_Cache::get_instance()->replace($key, $data, $group, $expire);
}

function wp_cache_flush() {
    return WP_Object_Cache::get_instance()->flush();
}

function wp_cache_add_non_persistent_groups($groups) {
    WP_Object_Cache::get_instance()->add_non_persistent_groups($groups);
}

function wp_cache_add_global_groups($groups) {
    WP_Object_Cache::get_instance()->add_global_groups($groups);
}

function wp_cache_incr($key, $offset = 1, $group = '') {
    return WP_Object_Cache::get_instance()->incr($key, $offset, $group);
}

function wp_cache_decr($key, $offset = 1, $group = '') {
    return WP_Object_Cache::get_instance()->decr($key, $offset, $group);
}

function wp_cache_close() {
    return WP_Object_Cache::get_instance()->close();
}

function wp_cache_switch_to_blog($blog_id) {
    WP_Object_Cache::get_instance()->switch_to_blog($blog_id);
}

function wp_cache_get_multiple($keys, $group = '', $force = false) {
    return WP_Object_Cache::get_instance()->get_multiple($keys, $group, $force);
}

function wp_cache_set_multiple(array $data, $group = '', $expire = 0) {
    return WP_Object_Cache::get_instance()->set_multiple($data, $group, $expire);
}

function wp_cache_delete_multiple(array $keys, $group = '') {
    return WP_Object_Cache::get_instance()->delete_multiple($keys, $group);
}

class WP_Object_Cache {

    /**
     * Holds the singleton instance.
     */
    private static $instance;

    /**
     * Internal runtime cache (Non-persistent).
     */
    private $cache = [];

    /**
     * Cache key prefix to prevent collisions.
     */
    private $prefix;

    /**
     * Groups that should not be persisted to APCu.
     */
    private $non_persistent_groups = ['comment', 'counts', 'queries'];

    /**
     * Groups that are global across all sites in a multisite network.
     */
    private $global_groups = [];

    /**
     * Singleton accessor.
     */
    public static function get_instance() {
        if (!self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    /**
     * Constructor: Initializes prefix and checks for APCu availability.
     */
    private function __construct() {
        if (!function_exists('apcu_enabled') || !apcu_enabled()) {
            return;
        }

        // Generate a unique prefix based on the site's unique salts to avoid
        // cross-site pollution on shared environments.
        $this->prefix = defined('AUTH_KEY') ? substr(md5(AUTH_KEY), 0, 8) : substr(md5(ABSPATH), 0, 8);
    }

    /**
     * Builds a unique key for APCu storage.
     */
    private function build_key($key, $group) {
        $group = (empty($group)) ? 'default' : $group;
        return $this->prefix . ':' . $group . ':' . $key;
    }

    /**
     * Adds groups to the non-persistent list.
     */
    public function add_non_persistent_groups($groups) {
        $groups = (array) $groups;
        $this->non_persistent_groups = array_merge($this->non_persistent_groups, $groups);
        $this->non_persistent_groups = array_unique($this->non_persistent_groups);
    }

    /**
     * Retrieves data from cache. Checks local memory first, then APCu.
     */
    public function get($key, $group = '', $force = false, &$found = null) {
        $derived_key = $this->build_key($key, $group);

        // Check internal memory first
        if (isset($this->cache[$derived_key])) {
            $found = true;
            return is_object($this->cache[$derived_key]) ? clone $this->cache[$derived_key] : $this->cache[$derived_key];
        }

        // If group is non-persistent, skip APCu
        if (in_array($group, $this->non_persistent_groups)) {
            $found = false;
            return false;
        }

        $result = apcu_fetch($derived_key, $found);
        if ($found) {
            $this->cache[$derived_key] = $result;
        }

        return $result;
    }

    /**
     * Sets data in both local memory and APCu.
     */
    public function set($key, $data, $group = '', $expire = 0) {
        $derived_key = $this->build_key($key, $group);

        // Always store in local memory for current request
        $this->cache[$derived_key] = $data;

        if (in_array($group, $this->non_persistent_groups)) {
            return true;
        }

        return apcu_store($derived_key, $data, (int) $expire);
    }

    /**
     * Adds data only if the key doesn't exist.
     */
    public function add($key, $data, $group = '', $expire = 0) {
        $derived_key = $this->build_key($key, $group);

        if (isset($this->cache[$derived_key]) || apcu_exists($derived_key)) {
            return false;
        }

        return $this->set($key, $data, $group, $expire);
    }

    /**
     * Replaces data only if the key already exists.
     */
    public function replace($key, $data, $group = '', $expire = 0) {
        $derived_key = $this->build_key($key, $group);

        if (!apcu_exists($derived_key)) {
            return false;
        }

        return $this->set($key, $data, $group, $expire);
    }

    /**
     * Deletes data from local memory and APCu.
     */
    public function delete($key, $group = '') {
        $derived_key = $this->build_key($key, $group);

        unset($this->cache[$derived_key]);
        return apcu_delete($derived_key);
    }

    /**
     * Clears all data from APCu and local memory.
     */
    public function flush() {
        $this->cache = [];
        return apcu_clear_cache();
    }

    /**
     * Adds groups to the global groups list (for multisite).
     */
    public function add_global_groups($groups) {
        $groups = (array) $groups;
        $this->global_groups = array_merge($this->global_groups, $groups);
        $this->global_groups = array_unique($this->global_groups);
    }

    /**
     * Increments numeric cache contents.
     */
    public function incr($key, $offset = 1, $group = '') {
        $derived_key = $this->build_key($key, $group);
        $offset = (int) $offset;

        // For non-persistent groups, work with memory cache only
        if (\in_array($group, $this->non_persistent_groups, true)) {
            if (!isset($this->cache[$derived_key])) {
                return false;
            }
            if (!is_numeric($this->cache[$derived_key])) {
                $this->cache[$derived_key] = 0;
            }
            $this->cache[$derived_key] += $offset;
            return $this->cache[$derived_key];
        }

        // Try to get current value
        $current = apcu_fetch($derived_key, $success);
        if (!$success) {
            return false;
        }

        // Ensure numeric value
        if (!is_numeric($current)) {
            $current = 0;
        }

        $new_value = $current + $offset;
        apcu_store($derived_key, $new_value);
        $this->cache[$derived_key] = $new_value;

        return $new_value;
    }

    /**
     * Decrements numeric cache contents.
     */
    public function decr($key, $offset = 1, $group = '') {
        $derived_key = $this->build_key($key, $group);
        $offset = (int) $offset;

        // For non-persistent groups, work with memory cache only
        if (\in_array($group, $this->non_persistent_groups, true)) {
            if (!isset($this->cache[$derived_key])) {
                return false;
            }
            if (!is_numeric($this->cache[$derived_key])) {
                $this->cache[$derived_key] = 0;
            }
            $this->cache[$derived_key] -= $offset;
            return $this->cache[$derived_key];
        }

        // Try to get current value
        $current = apcu_fetch($derived_key, $success);
        if (!$success) {
            return false;
        }

        // Ensure numeric value
        if (!is_numeric($current)) {
            $current = 0;
        }

        $new_value = $current - $offset;
        apcu_store($derived_key, $new_value);
        $this->cache[$derived_key] = $new_value;

        return $new_value;
    }

    /**
     * Closes the cache (no-op for APCu).
     */
    public function close() {
        return true;
    }

    /**
     * Switches the internal blog ID (for multisite support).
     */
    public function switch_to_blog($blog_id) {
        // In a multisite environment, you would update the prefix here
        // For now, this is a no-op
    }

    /**
     * Retrieves multiple values from cache.
     */
    public function get_multiple($keys, $group = '', $force = false) {
        $values = [];
        foreach ($keys as $key) {
            $values[$key] = $this->get($key, $group, $force);
        }
        return $values;
    }

    /**
     * Sets multiple values in cache.
     */
    public function set_multiple(array $data, $group = '', $expire = 0) {
        $success = true;
        foreach ($data as $key => $value) {
            if (!$this->set($key, $value, $group, $expire)) {
                $success = false;
            }
        }
        return $success;
    }

    /**
     * Deletes multiple values from cache.
     */
    public function delete_multiple(array $keys, $group = '') {
        $success = true;
        foreach ($keys as $key) {
            if (!$this->delete($key, $group)) {
                $success = false;
            }
        }
        return $success;
    }
}
