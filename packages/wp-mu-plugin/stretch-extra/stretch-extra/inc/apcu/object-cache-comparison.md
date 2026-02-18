# APCu Object Cache Implementation Comparison

Comparison of two APCu-based WordPress object cache drop-in implementations:

- **default stretch object-cache.php** — [`/object-cache.php`](/object-cache.php)
- **stretch-extra object-cache.php** — [`/packages/wp-mu-plugin/stretch-extra/stretch-extra/inc/apcu/object-cache.php`](/packages/wp-mu-plugin/stretch-extra/stretch-extra/inc/apcu/object-cache.php)

---

## Architecture

| | default stretch object-cache.php | stretch-extra object-cache.php |
|---|---|---|
| Approach | Procedural, global variables | `WP_Object_Cache` class + thin wrapper functions |
| State | `$GLOBALS['apcu_cache_local']`, `$GLOBALS['apcu_cache_non_persistent_groups']` | Encapsulated object state in `$GLOBALS['wp_object_cache']` |

The **stretch-extra** approach matches the standard WordPress object cache drop-in pattern (which expects a `WP_Object_Cache` class). The **default stretch** approach is functional but non-standard.

---

## Bugs

### default stretch object-cache.php

**Critical bug in `wp_cache_set`:**

```php
function wp_cache_set(string $key, $data, string $group='', $expire=0) {
    $group = apcu_cache_group($group);
    $data = apcu_cache_clone_value($data);
    apcu_cache_local_group($group)[$key] = $data;  // local OK
    $full_key = apcu_cache_key($group, $key);
    apcu_store($full_key, $data, $expire);           // BUG: always stores to APCu,
                                                     //      ignores non-persistent groups!
    return true;
}
```

Non-persistent groups (e.g. `'counts'`, `'plugins'`) are always written to APCu, leaking request-scoped data across requests.

**Wrong return type on `apcu_cache_is_persistent_group`:** declared as `: string`, returns `bool`.

### stretch-extra object-cache.php

No functional bugs found.

---

## Performance

| Aspect | default stretch object-cache.php | stretch-extra object-cache.php |
|---|---|---|
| Key prefix | `$table_prefix . '//' . $group . '//'` | `WP_CACHE_KEY_SALT` (or `$table_prefix` fallback) |
| Key format | `{prefix}//{group}//{key}` | `{prefix}//{group}//v{version}//{key}` |
| Group flush | Not supported | O(1) via version counter increment |
| Batch fetch | `apcu_fetch(array)` in `get_multiple` | Same, correctly scoped |
| Non-persistent writes | **Always writes to APCu** (bug) | Correctly skips APCu |
| Local cache lookup | `apcu_cache_local_group()` via reference | Direct `$this->cache[$group]` access |
| Extra APCu hit | None | 1 per group per request (version fetch on first access) |

The **stretch-extra** version pays a small per-group overhead for the version counter but gains correct non-persistent behavior and O(1) `flush_group`. In practice the group version overhead is negligible while the `set` bug in **default stretch** causes unnecessary APCu writes for non-persistent groups.

---

## Completeness (WordPress API Coverage)

| Function | default stretch | stretch-extra |
|---|---|---|
| `wp_cache_add` | ✅ | ✅ |
| `wp_cache_get` | ✅ | ✅ |
| `wp_cache_set` | ✅ (buggy) | ✅ |
| `wp_cache_delete` | ✅ | ✅ |
| `wp_cache_flush` | ✅ | ✅ |
| `wp_cache_incr` / `decr` | ✅ | ✅ |
| `wp_cache_replace` | ✅ | ✅ |
| `wp_cache_get_multiple` | ✅ | ✅ |
| `wp_cache_set_multiple` | ❌ | ✅ |
| `wp_cache_delete_multiple` | ❌ | ✅ |
| `wp_cache_flush_group` | ❌ | ✅ |
| `wp_cache_switch_to_blog` | no-op | ✅ proper multisite |
| `wp_cache_add_global_groups` | no-op | no-op (documented) |
| `wp_cache_close` | ✅ | ✅ |
| `wp_cache_reset` | no-op | delegates to `reset()` |

`wp_cache_flush_group`, `wp_cache_set_multiple`, and `wp_cache_delete_multiple` were introduced in WordPress 6.1. The **default stretch** version is missing all three.

---

## WordPress Compatibility

| Concern | default stretch | stretch-extra |
|---|---|---|
| `ABSPATH` guard | ❌ missing | ✅ `defined('ABSPATH') \|\| exit()` |
| `WP_CACHE_KEY_SALT` | ❌ ignored | ✅ used as primary prefix |
| Cross-install isolation | Only `$table_prefix` | `WP_CACHE_KEY_SALT` or `$table_prefix` |
| Multisite (`switch_to_blog`) | ❌ no-op | ✅ rebuilds prefix with blog ID, clears local cache |
| WP 6.1+ API | ❌ incomplete | ✅ full |
| Standard class pattern | ❌ procedural | ✅ `WP_Object_Cache` class |

Without `WP_CACHE_KEY_SALT`, the **default stretch** version relies solely on `$table_prefix` for isolation. Two WordPress installs sharing the same APCu instance with the same table prefix (a common hosting scenario) will corrupt each other's caches.

---

## Code Quality

| Aspect | default stretch | stretch-extra |
|---|---|---|
| Documentation | Minimal header only | Full docblocks explaining design rationale |
| Indentation | Tabs (inconsistent with project 2-space standard) | 2 spaces |
| Encapsulation | Global state via `$GLOBALS` | Private class members |
| PHP version target | Compatible but no modern features | Uses `??=`, named fallbacks, proper type hints |
| Project conventions | Does not follow AGENTS.md | Follows project standards |

---

## Summary

The **stretch-extra object-cache.php** is clearly the superior implementation:

- Fixes the critical `wp_cache_set` bug that writes non-persistent groups to APCu
- Implements `flush_group()` with an efficient O(1) version-counter strategy
- Covers the full WordPress 6.1+ cache API (`set_multiple`, `delete_multiple`, `flush_group`)
- Provides proper multisite support via `switch_to_blog`
- Uses `WP_CACHE_KEY_SALT` for correct cross-install isolation
- Follows project code conventions and the standard WordPress class-based drop-in pattern

The **default stretch object-cache.php** has a functional foundation but contains a critical correctness bug, is missing several WordPress 6.1+ API functions, has no multisite support, lacks key salt isolation, and does not match project coding standards.
