# WordPress Integration Standards

## Requirements

- **WordPress 6.8+**
- Follow these standards (not standard WordPress conventions for inline/arrow functions)

## Hooks

```php
// Inline for single use
\add_action('admin_enqueue_scripts', function (string $hook): void {
  if ($hook !== 'toplevel_page_plugin') return;
  \wp_enqueue_script('script', \plugins_url('build/index.js', PLUGIN_FILE));
});

// Arrow function for simple filters
\add_filter('excerpt_length', fn (): int => 50);

// Named when reused
\add_action('save_post', __NAMESPACE__ . '\handle_save', 10, 2);
\add_action('edit_post', __NAMESPACE__ . '\handle_save', 10, 2);

function handle_save(int $post_id, \WP_Post $post): void {
  // Reused logic
}
```

### Dynamic Hooks

WordPress provides dynamic hooks that include variable parts. These are powerful for reacting to specific option changes, metadata updates, or post type operations.

**Common dynamic hook patterns:**

```php
// Option-specific hooks - triggered when specific option changes
\add_action(
  hook_name: 'update_option_' . MY_OPTION_NAME,
  callback: __NAMESPACE__ . '\handle_option_change',
  priority: 10,
  accepted_args: 3
);

function handle_option_change(mixed $old_value, mixed $new_value, string $option): void {
  // React to specific option change
  if ($old_value !== $new_value) {
    perform_sync_action($new_value);
  }
}

// Post type specific hooks
\add_action('save_post_product', __NAMESPACE__ . '\handle_product_save', 10, 3);
\add_action('delete_post_product', __NAMESPACE__ . '\handle_product_delete');

// Meta-specific hooks
\add_action('update_user_meta', __NAMESPACE__ . '\handle_meta_update', 10, 4);
\add_filter('get_user_metadata', __NAMESPACE__ . '\filter_user_meta', 10, 4);

// Custom taxonomy hooks
\add_action('create_product_category', __NAMESPACE__ . '\handle_term_create');
\add_action('edited_product_category', __NAMESPACE__ . '\handle_term_edit');
```

**Why use dynamic hooks:**

- More targeted than generic hooks
- Better performance (only fires for specific items)
- Clearer intent in code
- Reduces conditional logic in callbacks

## APIs

### Options

**Prefer string values over booleans** for WordPress options to avoid type ambiguity:

```php
// ✅ GOOD - String values are explicit and unambiguous
const FEATURE_ENABLED = 'enabled';
const FEATURE_DISABLED = 'disabled';

$status = \get_option('feature_status', FEATURE_DISABLED);
$is_enabled = $status === FEATURE_ENABLED;

// When updating
\update_option('feature_status', FEATURE_ENABLED, true);

// ❌ AVOID - Boolean values can be ambiguous when stored
// MySQL/WordPress may convert true/false to '1'/'' which can cause issues
\update_option('feature_enabled', true); // Stored as '1'
$enabled = \get_option('feature_enabled'); // Returns '1' (string, not boolean)
```

**Why string values:**

- Explicit state representation
- No type coercion issues
- Better for serialization
- Clearer intent in code
- Easier to extend (add more states later)

**Standard option operations:**

```php
// Get with default
$value = \get_option('option_name', 'default');

// Update with autoload flag
\update_option('option_name', $value, true);

// Delete
\delete_option('option_name');

// Check existence
if (\get_option('option_name') !== false) {
  // Option exists
}
```

### Transients

```php
$cached = \get_transient('cache_key');
if ($cached === false) {
  $cached = expensive_operation();
  \set_transient('cache_key', $cached, HOUR_IN_SECONDS);
}
```

### User Meta

```php
$meta = \get_user_meta(\get_current_user_id(), 'meta_key', true);
\update_user_meta($user_id, 'meta_key', $value);
```

## REST API

```php
\add_action('rest_api_init', function () {
  \register_rest_route('vendor/plugin/v1', '/endpoint', [
    'methods'             => \WP_REST_Server::READABLE,
    'callback'            => __NAMESPACE__ . '\handle_request',
    'permission_callback' => fn () => \current_user_can('manage_options'),
    'args'                => [
      'id' => [
        'required'          => true,
        'type'              => 'integer',
        'validate_callback' => fn ($v) => is_numeric($v) && $v > 0,
        'sanitize_callback' => 'absint',
      ],
    ],
  ]);
});

function handle_request(\WP_REST_Request $request): \WP_REST_Response {
  $id = $request->get_param('id');
  return new \WP_REST_Response(['success' => true, 'data' => $id], 200);
}
```

## Admin Pages

```php
\add_action('admin_menu', function () {
  \add_menu_page(
    page_title: 'Page Title',
    menu_title: 'Menu Title',
    capability: 'manage_options',
    menu_slug: 'my-plugin',
    callback: __NAMESPACE__ . '\render_page',
    icon_url: 'dashicons-admin-generic'
  );
});
```

## Asset Enqueuing

### wp-scripts Bundled Assets

**JavaScript and CSS entry point files (`*index.js`, `*index.css`) are bundled using `@wordpress/scripts`:**

- Entry files in `src/` are bundled to `build/` with the same relative path
- An `index.asset.php` file is auto-generated containing dependencies and version hash
- **Always `require` the `.asset.php` file before enqueuing**
- Use the asset file's `dependencies` and `version` in enqueue functions

```php
\add_action('admin_enqueue_scripts', function (string $hook): void {
  if ($hook !== 'toplevel_page_my-plugin') return;

  // Require the auto-generated asset file
  $asset_file = require_once __DIR__ . '/build/index.asset.php';

  // Register and enqueue script using asset dependencies and version
  \wp_enqueue_script(
    handle: 'my-plugin-script',
    src: \plugins_url('build/index.js', PLUGIN_FILE),
    deps: $asset_file['dependencies'],  // WordPress package dependencies
    ver: $asset_file['version'],        // Content hash for cache busting
    in_footer: true
  );

  // Enqueue bundled CSS (if generated)
  \wp_enqueue_style(
    handle: 'my-plugin-style',
    src: \plugins_url('build/index.css', PLUGIN_FILE),
    deps: [],
    ver: $asset_file['version']  // Same version as JS
  );

  // Pass data to JavaScript
  \wp_localize_script('my-plugin-script', 'pluginData', [
    'restUrl' => \rest_url(),
    'nonce'   => \wp_create_nonce('wp_rest'),
  ]);
});
```

**Example directory structure:**

```
plugin/
├── src/
│   ├── feature/
│   │   └── index.js        → Entry point
│   └── another/
│       ├── index.js        → Entry point
│       └── index.css       → Entry CSS
└── build/                  ← Generated by wp-scripts
    ├── feature/
    │   ├── index.js
    │   └── index.asset.php  ← Auto-generated
    └── another/
        ├── index.js
        ├── index.css
        └── index.asset.php  ← Auto-generated
```

**The `.asset.php` file contains:**

```php
<?php return [
  'dependencies' => ['wp-element', 'wp-i18n'], // WordPress packages used
  'version'      => '66ba82f2da957b8ff576',     // Content hash
];
```

## Translation

```php
// PHP
\__('Text', 'text-domain');
\_e('Text', 'text-domain');
\esc_html__('Text', 'text-domain');
sprintf(\__('Hello %s', 'text-domain'), $name);

// Load text domain
\add_action('init', function () {
  \load_plugin_textdomain(
    'text-domain',
    false,
    dirname(plugin_basename(PLUGIN_FILE)) . '/languages'
  );
});
```

```javascript
// JavaScript
import { __ } from '@wordpress/i18n';

const text = __('Text', 'text-domain');
const message = sprintf(__('Hello %s', 'text-domain'), name);
```

## Database

```php
global $wpdb;

// Query
$count = $wpdb->get_var($wpdb->prepare(
  "SELECT COUNT(*) FROM {$wpdb->posts} WHERE post_type = %s",
  'post'
));

// Insert
$wpdb->insert($wpdb->prefix . 'table', [
  'column1' => 'value1',
], ['%s']);

// Update
$wpdb->update(
  $wpdb->prefix . 'table',
  ['column1' => 'new_value'],
  ['id' => 123],
  ['%s'],
  ['%d']
);
```

## AJAX

```php
\add_action('wp_ajax_my_action', function () {
  check_ajax_referer('my-nonce');

  if (!\current_user_can('manage_options')) {
    \wp_send_json_error('Unauthorized', 403);
  }

  $data = \sanitize_text_field($_POST['data'] ?? '');
  \wp_send_json_success(process_data($data));
});
```

## Drop-in Plugins

Drop-in plugins are special WordPress files placed in `wp-content/` that override core WordPress functionality. They load very early in the WordPress lifecycle, before most plugins.

**Common drop-ins:**

- `object-cache.php` - Custom object caching implementation
- `db.php` - Custom database class
- `advanced-cache.php` - Advanced caching for page content
- `db-error.php` - Custom database error page
- `maintenance.php` - Custom maintenance mode page

### Object Cache Drop-in Pattern

**Use case:** Implement persistent object caching (Redis, Memcached, APCu, etc.)

**Implementation pattern:**

```php
<?php
// wp-content/object-cache.php

// Must implement WordPress cache functions
function wp_cache_add(int|string $key, mixed $data, string $group = '', int $expire = 0): bool { }
function wp_cache_set(int|string $key, mixed $data, string $group = '', int $expire = 0): bool { }
function wp_cache_get(int|string $key, string $group = '', bool $force = false, bool &$found = null): mixed { }
function wp_cache_delete(int|string $key, string $group = ''): bool { }
function wp_cache_flush(): bool { }

// Additional functions
function wp_cache_add_global_groups(string|array $groups): void { }
function wp_cache_add_non_persistent_groups(string|array $groups): void { }
```

**Sync pattern for file-based drop-ins:**

When a drop-in needs to be enabled/disabled based on an option:

```php
// In plugin code
function sync_dropin_file(): void {
  $dropin_path = WP_CONTENT_DIR . '/object-cache.php';
  $source_path = __DIR__ . '/includes/object-cache.php';

  $enabled = \get_option('feature_status') === 'enabled';

  if ($enabled && !file_exists($dropin_path)) {
    // Enable: Copy source to wp-content/
    copy($source_path, $dropin_path);
    \wp_cache_flush(); // Clear any cached data
  } elseif (!$enabled && file_exists($dropin_path)) {
    // Disable: Remove drop-in
    unlink($dropin_path);
    \wp_cache_flush();
  }
}

// Sync on option change
\add_action(
  hook_name: 'update_option_feature_status',
  callback: __NAMESPACE__ . '\sync_dropin_file',
  priority: 10,
  accepted_args: 3
);

// Sync on plugin activation/deactivation
register_activation_hook(PLUGIN_FILE, __NAMESPACE__ . '\sync_dropin_file');
register_deactivation_hook(PLUGIN_FILE, __NAMESPACE__ . '\sync_dropin_file');
```

**WP-CLI integration for drop-ins:**

```php
// Ensure WP-CLI can detect state changes immediately
if (defined('WP_CLI') && WP_CLI) {
  \add_action('init', __NAMESPACE__ . '\sync_dropin_file');
}
```

**Why WP-CLI sync is needed:**

- WP-CLI operations may bypass normal WordPress hooks
- File-based features need immediate filesystem sync
- Ensures `wp option update` commands work as expected

### Non-Persistent Cache Groups

Some data should never be cached persistently (even with object caching enabled):

```php
// During cache initialization
function register_cache_groups(): void {
  \wp_cache_add_non_persistent_groups([
    'counts',           // Post counts change frequently
    'plugins',          // Plugin data changes on activation
    'themes',           // Theme data changes on switch
    'comment',          // Comment data is user-specific
    'blog-details',     // Multisite blog details
    'blog-lookup',      // Multisite lookups
    'site-options',     // Multisite site options
    'site-transient',   // Temporary cross-site data
    'rss',              // Feed data is external
    'global-posts',     // Multisite global posts
  ]);
}

// Call early in object-cache.php
register_cache_groups();
```

**Why non-persistent groups:**

- Data is user-specific or request-specific
- Data changes too frequently for persistent caching
- Data is environment-specific (not shareable across servers)
- Prevents stale data issues in load-balanced environments

## Common Functions

```php
// URLs
\home_url('/path');
\admin_url('admin.php');
\plugins_url('file', __FILE__);

// Users
\is_user_logged_in();
\current_user_can('capability');
\get_current_user_id();

// Conditionals
\is_admin();
\is_single();
\is_page();
```

---

**See Also**: [PHP Standards](php-standards.md), [Security Standards](security.md), [PHPUnit Testing](phpunit-testing.md)
