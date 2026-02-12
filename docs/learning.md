# Implementation Learnings - APCu Object Cache Feature

This document captures important patterns and conventions discovered during the implementation of the APCu object cache feature that were not fully documented in the agent guidelines.

## WordPress Option Values

**Pattern**: WordPress options in this codebase use **string values**, not booleans.

**Example from migration.php**:

```php
if (\get_option(IONOS_MIGRATION_OPTION) === '1.0.0') {
```

**APCu implementation**:

```php
const IONOS_APCU_OBJECT_CACHE_ENABLED_OPTION = 'IONOS_APCU_OBJECT_CACHE_ENABLED_OPTION';

// Check if enabled
if (\get_option(IONOS_APCU_OBJECT_CACHE_ENABLED_OPTION) === '1') {
  // Cache is enabled
}
```

**Why**: Using strings is more explicit and avoids PHP type juggling issues. Empty string, `null`, and `false` are all different states that can be represented distinctly.

**Agent Documentation Status**: ❌ Not documented. Should be added to PHP standards.

---

## Named Parameters for WordPress Hooks

**Pattern**: Use **named parameters** for `add_action()` and `add_filter()` calls to improve code clarity.

**Example**:

```php
\add_action(
  hook_name: 'update_option_' . IONOS_APCU_OBJECT_CACHE_ENABLED_OPTION,
  callback: __NAMESPACE__ . '\handle_option_change',
  priority: 10,
  accepted_args: 3
);
```

**Why**: Named parameters make it clear what each argument represents, especially for functions with multiple parameters where some might be skipped.

**Agent Documentation Status**: ✅ Documented in AGENTS.md but examples in existing code (migration.php) don't use them. The requirement was specific to this implementation.

---

## Namespace Callback Pattern

**Pattern**: When passing callbacks to WordPress hooks from within a namespace, use `__NAMESPACE__ . '\function_name'`.

**Example**:

```php
namespace ionos\stretch_extra\apcu;

function handle_option_change() { }

\add_action(
  hook_name: 'init',
  callback: __NAMESPACE__ . '\handle_option_change'
);
```

**Why**: This ensures the correct namespaced function is called. Without the namespace prefix, WordPress would look for the function in the global namespace.

**Agent Documentation Status**: ✅ Documented in PHP standards as "Late Binding" pattern.

---

## Inline Functions in WordPress Hooks

**Pattern**: Functions used only once in `add_filter()` or `add_action()` can be **inlined as anonymous functions**. If the function body consists of only a **single statement**, use an **arrow function** (`fn() =>`).

**Example - Anonymous function**:

```php
// Multi-line function body
\add_action(
  hook_name: 'init',
  callback: function(): void {
    $option = \get_option('some_option');
    process_option($option);
  }
);
```

**Example - Arrow function**:

```php
// Single statement - use arrow function
\add_filter(
  hook_name: 'the_content',
  callback: fn(string $content): string => wpautop($content)
);

\add_action(
  hook_name: 'init',
  callback: fn(): void => register_custom_post_types()
);
```

**Why**:

- **Inline functions** reduce code clutter when callbacks are only used once
- **Arrow functions** are more concise for single-expression callbacks
- Both improve code locality by keeping related code together

**When to use named functions instead**:

- Function is reused in multiple places
- Function body is complex or multi-line
- Function needs to be tested independently
- Function should be documented separately

**Agent Documentation Status**: ✅ Documented in AGENTS.md: "Arrow functions `fn() =>` for single expressions, anonymous `function() {}` for multi-line" and "Inline/anonymous functions if only used once, named functions when reused"

---

## Dynamic Hook Names (update*option*)

**Pattern**: WordPress provides dynamic hooks for option changes using the pattern `update_option_{$option_name}`.

**Example**:

```php
\add_action(
  hook_name: 'update_option_' . IONOS_APCU_OBJECT_CACHE_ENABLED_OPTION,
  callback: __NAMESPACE__ . '\handle_option_change',
  priority: 10,
  accepted_args: 3
);

function handle_option_change(mixed $old_value, mixed $new_value, string $option): void {
  // Handle the change
}
```

**Parameters**:

1. `$old_value` - The previous option value
2. `$new_value` - The new option value
3. `$option` - The option name

**Why**: This is more efficient than hooking into the generic `update_option` hook and checking the option name.

**Agent Documentation Status**: ❌ Not documented. Should be added to WordPress integration guide.

---

## WP-CLI Compatibility Pattern

**Pattern**: Implement a **sync mechanism** on `init` hook to ensure state consistency when options are changed via WP-CLI.

**Example**:

```php
\add_action(
  hook_name: 'init',
  callback: __NAMESPACE__ . '\sync_cache_state',
  priority: 1
);

function sync_cache_state(): void {
  $enabled = \get_option(IONOS_APCU_OBJECT_CACHE_ENABLED_OPTION) === '1';
  $drop_in_exists = file_exists(get_destination_path());

  if ($enabled && !$drop_in_exists) {
    enable_cache();
  } elseif (!$enabled && $drop_in_exists) {
    disable_cache();
  }
}
```

**Why**: When options are changed via WP-CLI, the `update_option_` hook fires, but if you restart the environment or the option was changed before your plugin loaded, you need to sync the state. This ensures the drop-in file always matches the option value.

**Agent Documentation Status**: ❌ Not documented. This is a critical pattern for WP-CLI compatibility.

---

## WordPress Object Cache Drop-in

**Pattern**: WordPress automatically loads `WP_CONTENT_DIR/object-cache.php` if it exists, replacing the default transient-based cache.

**Implementation Requirements**:

1. Define a `WP_Object_Cache` class
2. Implement all standard cache methods (`get`, `set`, `delete`, `flush`, etc.)
3. Define global functions (`wp_cache_get`, `wp_cache_set`, etc.) that proxy to the class
4. Initialize a global `$wp_object_cache` instance

**Example structure**:

```php
class WP_Object_Cache {
  public function get($key, $group = 'default', $force = false, &$found = null) { }
  public function set($key, $data, $group = 'default', $expire = 0) { }
  // ... more methods
}

$GLOBALS['wp_object_cache'] = new WP_Object_Cache();

function wp_cache_get($key, $group = '', $force = false, &$found = null) {
  return $GLOBALS['wp_object_cache']->get($key, $group, $force, $found);
}
```

**Why**: This is the standard WordPress plugin architecture for drop-ins. The file must be named exactly `object-cache.php` and placed in `WP_CONTENT_DIR`.

**Agent Documentation Status**: ❌ Not documented. Should be added as a pattern in WordPress integration guide.

---

## Non-Persistent Cache Groups

**Pattern**: Some cache groups should **not** be persisted across requests (e.g., `counts`, `plugins`, `themes`).

**Example**:

```php
private array $non_persistent_groups = ['counts', 'plugins', 'themes'];

private function is_persistent_group(string $group): bool {
  return !in_array($group, $this->non_persistent_groups, true);
}
```

**Why**: These groups contain data that changes frequently or is request-specific. Caching them persistently can cause stale data issues or race conditions.

**Agent Documentation Status**: ❌ Not documented. This is WordPress best practice for object cache implementations.

---

## Notebook File Patterns

**Observation**: The ionos-wpdev-caddy plugin has a notebook system in `packages/wp-plugin/ionos-wpdev-caddy/ionos-wpdev-caddy/notebooks/notebooks/`.

**Structure**:

- Each feature has its own directory (e.g., `apcu/`)
- Contains small PHP scripts for testing/debugging
- Scripts use WordPress functions directly
- Common patterns: `info.php`, `state.php`, `stats.php`, `toggle.php`, `reset.php`

**Example files**:

- `state.php` - Check current feature state
- `toggle.php` - Enable/disable feature
- `stats.php` - Display formatted statistics
- `info.php` - Show raw system information
- `reset.php` - Clear/reset feature data

**Agent Documentation Status**: ❌ Not documented. This is a project-specific testing pattern.

---

## Recommendations for Agent Documentation

### High Priority

1. **Add to PHP Standards**: Document the string value pattern for WordPress options
2. **Add to WordPress Integration**: Document dynamic hooks like `update_option_{$option}`
3. **Add to WordPress Integration**: Document WP-CLI sync pattern for file-based features
4. **Add to WordPress Integration**: Document drop-in plugin architecture (object-cache.php, db.php, etc.)

### Medium Priority

1. **Add to WordPress Integration**: Document non-persistent cache groups pattern
2. **Add to Project README**: Document notebook testing pattern

### Low Priority

1. **Add examples to PHP Standards**: Show more examples of named parameters with WordPress functions

---

## Summary

The APCu object cache implementation revealed several undocumented patterns in the codebase:

✅ **Worked Well**:

- Namespace organization
- Function-first approach
- Late binding with `__NAMESPACE__`

❌ **Not Documented**:

- String values for options (not booleans)
- WP-CLI sync pattern
- Drop-in plugin architecture
- Non-persistent cache groups
- Notebook testing pattern
- Dynamic WordPress hooks

These patterns should be added to the agent documentation to help future implementations follow established conventions.
