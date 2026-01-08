# Security Standards

## Security Principles

1. **Never trust user input** - Always validate and sanitize
2. **Escape on output** - Use appropriate escaping functions
3. **Use nonces** - Verify intent for state-changing operations
4. **Check capabilities** - Ensure users have required permissions
5. **Prepared statements** - Always use `$wpdb->prepare()` for SQL
6. **Validate file operations** - Check file paths and types
7. **HTTPS only** - Assume secure connections
8. **Principle of least privilege** - Grant minimum necessary permissions

## Input Validation and Sanitization

### PHP Input Sanitization

Always sanitize input before processing:

```php
// Text fields
$text = \sanitize_text_field(\wp_unslash($_POST['text'] ?? ''));

// Email
$email = \sanitize_email($_POST['email'] ?? '');

// URL
$url = \sanitize_url($_POST['url'] ?? '');

// File name
$filename = \sanitize_file_name($_POST['filename'] ?? '');

// Key/slug
$key = \sanitize_key(\wp_unslash($_POST['key'] ?? ''));

// HTML content (allows safe HTML)
$html = \wp_kses_post(\wp_unslash($_POST['content'] ?? ''));

// Integer
$id = absint($_POST['id'] ?? 0);

// Float
$price = floatval($_POST['price'] ?? 0);

// Boolean
$active = !empty($_POST['active']);
```

### WordPress Sanitization Functions

| Function | Use Case |
|----------|----------|
| `\sanitize_text_field()` | Single-line text input |
| `\sanitize_textarea_field()` | Multi-line text (no HTML) |
| `\sanitize_email()` | Email addresses |
| `\sanitize_url()` | URLs |
| `\sanitize_file_name()` | File names |
| `\sanitize_key()` | Alphanumeric keys/slugs |
| `\sanitize_hex_color()` | Hex color values |
| `\sanitize_html_class()` | CSS class names |
| `\wp_kses_post()` | HTML with post-allowed tags |
| `\wp_kses()` | HTML with custom allowed tags |
| `absint()` | Positive integers |

### Array Data Sanitization

```php
// Sanitize array of text values
$values = array_map('sanitize_text_field', $_POST['values'] ?? []);

// Sanitize array of integers
$ids = array_map('absint', $_POST['ids'] ?? []);

// Recursive sanitization
function sanitize_array_recursive(array $data): array {
  return array_map(function ($value) {
    if (is_array($value)) {
      return sanitize_array_recursive($value);
    }
    return \sanitize_text_field($value);
  }, $data);
}
```

## Output Escaping

### PHP Output Escaping

Always escape output based on context:

```php
// HTML content
echo \esc_html($text);

// HTML attributes
<div class="<?php echo \esc_attr($class); ?>">

// URLs
<a href="<?php echo \esc_url($url); ?>">

// JavaScript strings
<script>
var message = '<?php echo \esc_js($message); ?>';
</script>

// SQL queries (with wpdb->prepare)
$results = $wpdb->get_results($wpdb->prepare(
  "SELECT * FROM {$wpdb->posts} WHERE post_type = %s",
  $post_type
));

// Allow specific HTML tags
echo \wp_kses($html, [
  'a' => ['href' => [], 'title' => []],
  'strong' => [],
  'em' => [],
]);

// Allow post content HTML
echo \wp_kses_post($content);
```

### Escaping Functions

| Function | Context |
|----------|---------|
| `\esc_html()` | HTML body content |
| `\esc_attr()` | HTML attribute values |
| `\esc_url()` | URLs in href, src attributes |
| `\esc_js()` | JavaScript strings |
| `\esc_textarea()` | Textarea element content |
| `\wp_kses()` | HTML with allowed tags |
| `\wp_kses_post()` | Post content HTML |

### Late Escaping Rule

**Always escape at the point of output, not before**:

```php
// ✅ Good - Late escaping
$foo   = \esc_attr(get_foo());
$label = \__('label', 'my-plugin');

printf( <<<EOF
<div class="{$foo}">
    {$label}
</div>
EOF
);

// ❌ Bad - Early escaping
$text = \esc_html($user_input); // Too early!
store_in_database($text);        // Escaped data in DB
```

## Nonce Verification

### What are Nonces?

WordPress nonces (Number Used Once) verify user intent and prevent CSRF attacks.

### Creating Nonces

```php
// Generate nonce field
\wp_nonce_field('action_name', '_wpnonce');

// Generate nonce URL
$url = \wp_nonce_url('admin.php?page=settings', 'action_name', '_wpnonce');

// Create nonce value
$nonce = \wp_create_nonce('action_name');
```

### Verifying Nonces

**Form Submissions**:
```php
// DO NOT use backslash prefix
check_admin_referer('action_name', '_wpnonce');

if (!\current_user_can('manage_options')) {
  \wp_die('Insufficient permissions');
}

// Process form
```

**AJAX Requests**:
```php
\add_action('wp_ajax_my_action', function () {
  // DO NOT use backslash prefix
  check_ajax_referer('ajax-nonce', '_wpnonce');

  if (!\current_user_can('manage_options')) {
    \wp_send_json_error('Insufficient permissions');
  }

  // Process AJAX
  \wp_send_json_success(['message' => 'Success']);
});
```

**Manual Verification**:
```php
if (!\wp_verify_nonce($_POST['_wpnonce'] ?? '', 'action_name')) {
  \wp_die('Invalid nonce');
}
```

### REST API Nonce

```php
// PHP: Localize nonce
\wp_localize_script('script-handle', 'wpData', [
  'nonce' => \wp_create_nonce('wp_rest'),
]);

// JavaScript: Include in headers
fetch(wpData.restUrl + 'endpoint', {
  headers: {
    'X-WP-Nonce': wpData.nonce,
  },
});
```

## Capability Checks

### Checking User Permissions

Always verify user capabilities before sensitive operations:

```php
// Check capability
if (!\current_user_can('manage_options')) {
  \wp_die('You do not have sufficient permissions');
}

// Check multiple capabilities
if (!\current_user_can('edit_posts') || !\current_user_can('publish_posts')) {
  \wp_die('Insufficient permissions');
}

// Check for specific post
if (!\current_user_can('edit_post', $post_id)) {
  \wp_die('You cannot edit this post');
}
```

### Common Capabilities

| Capability | Level |
|-----------|-------|
| `read` | Subscriber |
| `edit_posts` | Contributor |
| `publish_posts` | Author |
| `edit_pages` | Editor |
| `manage_options` | Administrator |
| `manage_categories` | Administrator |
| `manage_links` | Administrator |
| `upload_files` | Author |

### REST API Permission Callbacks

```php
\register_rest_route('namespace/v1', '/endpoint', [
  'methods'             => 'POST',
  'callback'            => __NAMESPACE__ . '\handle_request',
  'permission_callback' => function () {
    return \current_user_can('manage_options');
  },
]);
```

## Database Security

### SQL Injection Prevention

**ALWAYS use `$wpdb->prepare()`**:

```php
global $wpdb;

// ✅ Good - Prepared statement
$results = $wpdb->get_results($wpdb->prepare(
  "SELECT * FROM {$wpdb->posts} WHERE post_type = %s AND post_status = %s",
  $post_type,
  $status
));

// ✅ Good - Multiple parameters
$wpdb->query($wpdb->prepare(
  "UPDATE {$wpdb->options} SET option_value = %s WHERE option_name = %s",
  $value,
  $option_name
));

// ❌ NEVER do this - SQL injection vulnerability
$results = $wpdb->get_results(
  "SELECT * FROM {$wpdb->posts} WHERE post_type = '{$post_type}'"
);
```

### Prepared Statement Placeholders

| Placeholder | Type |
|------------|------|
| `%s` | String |
| `%d` | Integer |
| `%f` | Float |

### Safe Table Names

```php
global $wpdb;

// Use WordPress table properties
$wpdb->posts
$wpdb->users
$wpdb->options
$wpdb->postmeta
$wpdb->usermeta

// Custom table with prefix
$table_name = $wpdb->prefix . 'custom_table';
```

## File Security

### File Upload Validation

```php
function validate_uploaded_file(array $file): bool {
  // Check for upload errors
  if ($file['error'] !== UPLOAD_ERR_OK) {
    return false;
  }

  // Validate file type
  $allowed_types = ['image/jpeg', 'image/png', 'image/gif'];
  $finfo = finfo_open(FILEINFO_MIME_TYPE);
  $mime_type = finfo_file($finfo, $file['tmp_name']);
  finfo_close($finfo);

  if (!in_array($mime_type, $allowed_types, true)) {
    return false;
  }

  // Validate file size (5MB max)
  if ($file['size'] > 5 * 1024 * 1024) {
    return false;
  }

  // Sanitize filename
  $filename = \sanitize_file_name($file['name']);

  return true;
}
```

### File Path Validation

```php
function validate_file_path(string $path): bool {
  // Get real path
  $real_path = realpath($path);

  if ($real_path === false) {
    return false;
  }

  // Check if within allowed directory
  $allowed_dir = realpath(WP_CONTENT_DIR . '/uploads');

  if (strpos($real_path, $allowed_dir) !== 0) {
    return false;
  }

  return true;
}
```

### Preventing Directory Traversal

```php
// ❌ Vulnerable
$file = $_GET['file'];
include $file . '.php';

// ✅ Safe - Whitelist approach
$allowed_files = ['page1', 'page2', 'page3'];
$file = \sanitize_key($_GET['file'] ?? '');

if (in_array($file, $allowed_files, true)) {
  include __DIR__ . '/pages/' . $file . '.php';
}
```

## API Security

### REST API Security

```php
\register_rest_route('vendor/v1', '/endpoint', [
  'methods'             => \WP_REST_Server::CREATABLE,
  'callback'            => __NAMESPACE__ . '\handle_request',
  'permission_callback' => fn () => \current_user_can('manage_options'),
  'args' => [
    'id' => [
      'required'          => true,
      'type'              => 'integer',
      'validate_callback' => fn ($value) => is_numeric($value) && $value > 0,
      'sanitize_callback' => 'absint',
    ],
    'name' => [
      'required'          => true,
      'type'              => 'string',
      'sanitize_callback' => 'sanitize_text_field',
    ],
  ],
]);

function handle_request(\WP_REST_Request $request): \WP_REST_Response {
  // Parameters are already validated and sanitized
  $id = $request->get_param('id');
  $name = $request->get_param('name');

  // Additional capability check if needed
  if (!\current_user_can('edit_post', $id)) {
    return new \WP_REST_Response(
      ['error' => 'Unauthorized'],
      403
    );
  }

  // Process request
  return new \WP_REST_Response(['success' => true], 200);
}
```

### AJAX Security

```php
// Register AJAX handler
\add_action('wp_ajax_my_action', function () {
  // Verify nonce (no backslash)
  check_ajax_referer('my-nonce', '_wpnonce');

  // Check capability
  if (!\current_user_can('manage_options')) {
    \wp_send_json_error('Insufficient permissions', 403);
  }

  // Sanitize input
  $data = \sanitize_text_field(\wp_unslash($_POST['data'] ?? ''));

  // Process
  $result = process_data($data);

  // Return JSON
  \wp_send_json_success($result);
});

// Enqueue with nonce
\wp_localize_script('script-handle', 'ajaxData', [
  'ajaxUrl' => \admin_url('admin-ajax.php'),
  'nonce'   => \wp_create_nonce('my-nonce'),
]);
```

## JavaScript Security

### XSS Prevention

```javascript
// ✅ Good - Use textContent
element.textContent = userInput;

// ✅ Good - Escape for attributes
element.setAttribute('title', userInput);

// ⚠️ Dangerous - innerHTML with user input
element.innerHTML = userInput; // Can execute scripts!

// ✅ Safe - Sanitize first if HTML needed
element.innerHTML = DOMPurify.sanitize(userInput);
```

### API Calls

```javascript
// Always include nonce
const response = await fetch(wpData.restUrl + 'endpoint', {
  method: 'POST',
  headers: {
    'Content-Type': 'application/json',
    'X-WP-Nonce': wpData.nonce,
  },
  credentials: 'include',
  body: JSON.stringify(data),
});

// Validate response
if (!response.ok) {
  throw new Error(`HTTP ${response.status}`);
}
```

## Common Vulnerabilities

### SQL Injection

**Vulnerability**:
```php
// ❌ NEVER
$results = $wpdb->get_results(
  "SELECT * FROM {$wpdb->posts} WHERE ID = {$_GET['id']}"
);
```

**Fix**:
```php
// ✅ Always use prepare
$results = $wpdb->get_results($wpdb->prepare(
  "SELECT * FROM {$wpdb->posts} WHERE ID = %d",
  absint($_GET['id'] ?? 0)
));
```

### Cross-Site Scripting (XSS)

**Vulnerability**:
```php
// ❌ NEVER
echo "<h1>" . $_POST['title'] . "</h1>";
```

**Fix**:
```php
// ✅ Escape output
echo "<h1>" . \esc_html($_POST['title'] ?? '') . "</h1>";
```

### Cross-Site Request Forgery (CSRF)

**Vulnerability**:
```php
// ❌ No verification
if (isset($_POST['delete'])) {
  delete_post($_POST['id']);
}
```

**Fix**:
```php
// ✅ Verify nonce and capability
check_admin_referer('delete_post', '_wpnonce');

if (!\current_user_can('delete_posts')) {
  \wp_die('Unauthorized');
}

delete_post(absint($_POST['id'] ?? 0));
```

### Directory Traversal

**Vulnerability**:
```php
// ❌ NEVER
$file = $_GET['file'];
include $file;
```

**Fix**:
```php
// ✅ Whitelist and validate
$allowed = ['page1', 'page2'];
$file = \sanitize_key($_GET['file'] ?? '');

if (in_array($file, $allowed, true)) {
  include __DIR__ . '/pages/' . $file . '.php';
}
```

## Security Checklist

### Before Deployment

- [ ] All user input is sanitized
- [ ] All output is escaped appropriately
- [ ] Nonces are used for state-changing operations
- [ ] Capability checks are in place
- [ ] Database queries use prepared statements
- [ ] File uploads are validated
- [ ] API endpoints have permission callbacks
- [ ] Error messages don't reveal sensitive information
- [ ] Debug mode is disabled in production
- [ ] HTTPS is enforced
- [ ] File permissions are correct (644 for files, 755 for directories)

### Code Review Questions

1. Can this accept user input? → **Sanitize**
2. Does this display data? → **Escape**
3. Does this change state? → **Verify nonce**
4. Does this need permissions? → **Check capability**
5. Does this query the database? → **Use prepare()**
6. Does this handle files? → **Validate paths and types**

---

**See Also**:
- [PHP Standards](php-standards.md)
- [WordPress Integration](wordpress-integration.md)
- [PHPUnit Testing](phpunit-testing.md)
