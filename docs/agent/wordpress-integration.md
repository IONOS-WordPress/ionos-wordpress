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

## APIs

```php
// Options
$value = \get_option('option_name', 'default');
\update_option('option_name', $value, true);

// Transients
$cached = \get_transient('cache_key');
if ($cached === false) {
  $cached = expensive_operation();
  \set_transient('cache_key', $cached, HOUR_IN_SECONDS);
}

// User Meta
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

```php
\add_action('admin_enqueue_scripts', function ($hook) {
  if ($hook !== 'toplevel_page_my-plugin') return;

  $asset_file = require_once __DIR__ . '/build/index.asset.php';

  \wp_enqueue_script(
    handle: 'my-plugin-script',
    src: \plugins_url('build/index.js', PLUGIN_FILE),
    deps: $asset_file['dependencies'],
    ver: $asset_file['version'],
    in_footer: true
  );

  \wp_localize_script('my-plugin-script', 'pluginData', [
    'restUrl' => \rest_url(),
    'nonce'   => \wp_create_nonce('wp_rest'),
  ]);
});
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
