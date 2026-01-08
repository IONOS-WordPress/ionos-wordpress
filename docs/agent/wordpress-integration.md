# WordPress Integration Standards

## WordPress Version

- **Minimum**: WordPress 6.8+
- Follow WordPress coding standards and best practices except
  - use inlines arrow functions / callbacks when function is used just once


## Hooks and Filters

### Action Hooks

**Use inline functions for single-use callbacks, named functions when reused**:

```php
// Inline function for single-use callback
\add_action('admin_enqueue_scripts', function (string $hook): void {
  if ($hook !== 'toplevel_page_plugin') {
    return;
  }

  \wp_enqueue_script('plugin-script', \plugins_url('build/index.js', PLUGIN_FILE));
});

// Named function when used multiple times
\add_action('save_post', __NAMESPACE__ . '\handle_post_save', 10, 2);
\add_action('edit_post', __NAMESPACE__ . '\handle_post_save', 10, 2);

function handle_post_save(int $post_id, \WP_Post $post): void {
  // Reused handler implementation
}
```

### Filter Hooks

**Prefer arrow functions for single expressions, regular functions for complex logic**:

```php
// Arrow function for single expression
\add_filter('the_content', fn (string $content): string => $content . ' [modified]');

// Arrow function with simple operation
\add_filter('excerpt_length', fn (): int => 50);

// Regular function for multi-line logic
\add_filter('post_content', function (string $content): string {
  $content = strip_tags($content);
  $content = wpautop($content);
  return $content;
});

// Named function when reused
\add_filter('post_content', __NAMESPACE__ . '\process_content');
\add_filter('page_content', __NAMESPACE__ . '\process_content');

function process_content(string $content): string {
  // Complex processing logic
  return $processed_content;
}
```

### Custom Hooks

```php
// Define custom hook points
\do_action('plugin_before_dashboard_render');
\do_action('plugin_after_dashboard_render', $data);

// Filter with default value
$value = \apply_filters('plugin_dashboard_data', $data, $context);
```

## WordPress APIs

### Options API

```php
// Get option
$value = \get_option('option_name', 'default_value');

// Update option
\update_option('option_name', $value, true);  // true = autoload

// Delete option
\delete_option('option_name');

// Add option (fails if exists)
\add_option('option_name', $value);
```

### Transients API

```php
// Get transient
$cached = \get_transient('cache_key');

// Set transient (expires in seconds)
\set_transient('cache_key', $data, HOUR_IN_SECONDS);

// Delete transient
\delete_transient('cache_key');

// Time constants
MINUTE_IN_SECONDS  // 60
HOUR_IN_SECONDS    // 3600
DAY_IN_SECONDS     // 86400
WEEK_IN_SECONDS    // 604800
MONTH_IN_SECONDS   // 2592000
YEAR_IN_SECONDS    // 31536000
```

### User Meta API

```php
// Get user meta
$value = \get_user_meta($user_id, 'meta_key', true);  // true = single value

// Update user meta
\update_user_meta($user_id, 'meta_key', $value);

// Delete user meta
\delete_user_meta($user_id, 'meta_key');

// Current user
$current_user_id = \get_current_user_id();
$value = \get_user_meta(\get_current_user_id(), 'meta_key', true);
```

### Post Meta API

```php
// Get post meta
$value = \get_post_meta($post_id, 'meta_key', true);

// Update post meta
\update_post_meta($post_id, 'meta_key', $value);

// Delete post meta
\delete_post_meta($post_id, 'meta_key');
```

## REST API

### Registering Endpoints

```php
\add_action('rest_api_init', function () {
  \register_rest_route('vendor/plugin/v1', '/endpoint', [
    'methods'             => \WP_REST_Server::READABLE,  // GET
    'callback'            => __NAMESPACE__ . '\handle_request',
    'permission_callback' => fn () => \current_user_can('manage_options'),
    'args'                => [
      'id' => [
        'required'          => true,
        'type'              => 'integer',
        'validate_callback' => fn ($value) => is_numeric($value) && $value > 0,
        'sanitize_callback' => 'absint',
      ],
    ],
  ]);
});

function handle_request(\WP_REST_Request $request): \WP_REST_Response {
  $id = $request->get_param('id');

  return new \WP_REST_Response([
    'success' => true,
    'data'    => process_request($id),
  ], 200);
}
```

### HTTP Methods

```php
\WP_REST_Server::READABLE   // GET
\WP_REST_Server::CREATABLE  // POST
\WP_REST_Server::EDITABLE   // PUT/PATCH
\WP_REST_Server::DELETABLE  // DELETE
\WP_REST_Server::ALLMETHODS // All methods
```

### Making REST Requests (JavaScript)

```javascript
import apiFetch from '@wordpress/api-fetch';

// GET request
const data = await apiFetch({
  path: '/vendor/plugin/v1/endpoint?id=123',
});

// POST request
const result = await apiFetch({
  path: '/vendor/plugin/v1/endpoint',
  method: 'POST',
  data: {
    id: 123,
    action: 'update',
  },
});
```

## Admin Pages

### Adding Menu Pages

```php
\add_action('admin_menu', function () {
  // Top-level menu
  \add_menu_page(
    page_title: 'Page Title',
    menu_title: 'Menu Title',
    capability: 'manage_options',
    menu_slug: 'my-plugin',
    callback: __NAMESPACE__ . '\render_page',
    icon_url: 'dashicons-admin-generic',
    position: 30
  );

  // Submenu page
  \add_submenu_page(
    parent_slug: 'my-plugin',
    page_title: 'Settings',
    menu_title: 'Settings',
    capability: 'manage_options',
    menu_slug: 'my-plugin-settings',
    callback: __NAMESPACE__ . '\render_settings'
  );
});
```

### Admin Bar

```php
\add_action('admin_bar_menu', function (\WP_Admin_Bar $admin_bar) {
  $admin_bar->add_node([
    'id'    => 'my-menu-item',
    'title' => 'My Menu Item',
    'href'  => \admin_url('admin.php?page=my-plugin'),
  ]);
}, 100);
```

## Asset Enqueuing

### Scripts

```php
\add_action('admin_enqueue_scripts', function ($hook) {
  // Only load on specific page
  if ($hook !== 'toplevel_page_my-plugin') {
    return;
  }

  // Load asset manifest
  $asset_file = require_once __DIR__ . '/build/index.asset.php';

  // Enqueue script
  \wp_enqueue_script(
    handle: 'my-plugin-script',
    src: \plugins_url('build/index.js', PLUGIN_FILE),
    deps: $asset_file['dependencies'],
    ver: $asset_file['version'],
    in_footer: true
  );

  // Localize script data
  \wp_localize_script('my-plugin-script', 'myPluginData', [
    'restUrl' => \rest_url(),
    'nonce'   => \wp_create_nonce('wp_rest'),
    'ajaxUrl' => \admin_url('admin-ajax.php'),
  ]);
});
```

### Styles

```php
\add_action('admin_enqueue_scripts', function ($hook) {
  \wp_enqueue_style(
    handle: 'my-plugin-style',
    src: \plugins_url('build/style.css', PLUGIN_FILE),
    ver: VERSION
    // deps defaults to [], so we skip it
  );
});
```

## Translation

### PHP Internationalization

```php
// Simple translation
\__('Text to translate', 'text-domain');

// Translation with echo
\_e('Text to translate', 'text-domain');

// Escaped translations
\esc_html__('Text', 'text-domain');
\esc_html_e('Text', 'text-domain');
\esc_attr__('Text', 'text-domain');

// Plural forms
\_n('One item', '%d items', $count, 'text-domain');

// Translation with context
\_x('Post', 'noun', 'text-domain');
\_x('Post', 'verb', 'text-domain');

// Formatted strings
sprintf(\__('Hello %s', 'text-domain'), $name);
```

### JavaScript Internationalization

```javascript
import { __ } from '@wordpress/i18n';

// Simple translation
const text = __('Text to translate', 'text-domain');

// Formatted strings
const message = sprintf(__('Hello %s', 'text-domain'), name);
```

### Loading Text Domain

```php
\add_action('init', function () {
  \load_plugin_textdomain(
    'text-domain',
    false,
    dirname(plugin_basename(PLUGIN_FILE)) . '/languages'
  );
});
```

## Database Access

### Using $wpdb

```php
global $wpdb;

// Get single value
$count = $wpdb->get_var($wpdb->prepare(
  "SELECT COUNT(*) FROM {$wpdb->posts} WHERE post_type = %s",
  'post'
));

// Get single row
$post = $wpdb->get_row($wpdb->prepare(
  "SELECT * FROM {$wpdb->posts} WHERE ID = %d",
  $post_id
));

// Get multiple rows
$posts = $wpdb->get_results($wpdb->prepare(
  "SELECT * FROM {$wpdb->posts} WHERE post_status = %s LIMIT %d",
  'publish',
  10
));

// Insert
$wpdb->insert(
  $wpdb->prefix . 'custom_table',
  [
    'column1' => 'value1',
    'column2' => 'value2',
  ],
  ['%s', '%s']  // format
);

// Update
$wpdb->update(
  $wpdb->prefix . 'custom_table',
  ['column1' => 'new_value'],  // data
  ['id' => 123],                // where
  ['%s'],                       // data format
  ['%d']                        // where format
);

// Delete
$wpdb->delete(
  $wpdb->prefix . 'custom_table',
  ['id' => 123],
  ['%d']
);
```

## Plugin Lifecycle

### Activation Hook

```php
\register_activation_hook(PLUGIN_FILE, function () {
  // Create database tables
  // Set default options
  // Schedule cron events
});
```

### Deactivation Hook

```php
\register_deactivation_hook(PLUGIN_FILE, function () {
  // Clear scheduled events
  // Clean up temporary data
  // DO NOT delete user data
});
```

### Uninstall Hook

```php
// uninstall.php
if (!defined('WP_UNINSTALL_PLUGIN')) {
  exit;
}

// Delete options
\delete_option('plugin_option');

// Delete user meta
\delete_metadata('user', 0, 'plugin_user_meta', '', true);

// Drop custom tables
global $wpdb;
$wpdb->query("DROP TABLE IF EXISTS {$wpdb->prefix}custom_table");
```

## Cron Events

### Scheduling Events

```php
// Schedule on activation
\register_activation_hook(PLUGIN_FILE, function () {
  if (!\wp_next_scheduled('my_daily_event')) {
    \wp_schedule_event(time(), 'daily', 'my_daily_event');
  }
});

// Hook handler
\add_action('my_daily_event', function () {
  // Perform scheduled task
});

// Clear on deactivation
\register_deactivation_hook(PLUGIN_FILE, function () {
  \wp_clear_scheduled_hook('my_daily_event');
});
```

### Custom Schedules

```php
\add_filter('cron_schedules', function ($schedules) {
  $schedules['every_5_minutes'] = [
    'interval' => 300,
    'display'  => \__('Every 5 Minutes', 'text-domain'),
  ];
  return $schedules;
});
```

## WordPress Query

### WP_Query

```php
$query = new \WP_Query([
  'post_type'      => 'post',
  'posts_per_page' => 10,
  'post_status'    => 'publish',
  'orderby'        => 'date',
  'order'          => 'DESC',
  'meta_query'     => [
    [
      'key'     => 'custom_field',
      'value'   => 'value',
      'compare' => '=',
    ],
  ],
]);

if ($query->have_posts()) {
  while ($query->have_posts()) {
    $query->the_post();
    // Use template tags
    \the_title();
    \the_content();
  }
  \wp_reset_postdata();
}
```

### get_posts()

```php
$posts = \get_posts([
  'numberposts' => 5,
  'post_type'   => 'post',
  'post_status' => 'publish',
]);

foreach ($posts as $post) {
  echo \esc_html($post->post_title);
}
```

## Admin Notices

```php
\add_action('admin_notices', function () {
  ?>
  <div class="notice notice-success is-dismissible">
    <p><?php \_e('Settings saved.', 'text-domain'); ?></p>
  </div>
  <?php
});

// Notice types
// notice-success
// notice-error
// notice-warning
// notice-info
```

## AJAX

### Registering Handler

```php
// For logged-in users
\add_action('wp_ajax_my_action', function () {
  check_ajax_referer('my-nonce');

  if (!\current_user_can('manage_options')) {
    \wp_send_json_error('Unauthorized', 403);
  }

  $data = \sanitize_text_field($_POST['data'] ?? '');
  $result = process_data($data);

  \wp_send_json_success($result);
});

// For logged-out users
\add_action('wp_ajax_nopriv_my_action', function () {
  // Handler for non-logged-in users
});
```

## Common WordPress Functions

### URL Functions

```php
\home_url('/path');           // Site URL
\admin_url('admin.php');      // Admin URL
\plugins_url('file', __FILE__); // Plugin URL
\content_url('/uploads');     // Content URL
\includes_url('file.php');    // Includes URL
```

### User Functions

```php
\is_user_logged_in();
\current_user_can('capability');
\get_current_user_id();
\wp_get_current_user();
\get_userdata($user_id);
```

### Post Functions

```php
\get_post($post_id);
\get_the_title($post_id);
\get_the_content($post_id);
\get_permalink($post_id);
\wp_update_post($post_data);
\wp_insert_post($post_data);
\wp_delete_post($post_id);
```

### Conditional Tags

```php
\is_admin();
\is_single();
\is_page();
\is_front_page();
\is_home();
\is_archive();
\is_search();
\is_404();
```

---

**See Also**:
- [PHP Standards](php-standards.md)
- [Security Standards](security.md)
- [PHPUnit Testing](phpunit-testing.md)
