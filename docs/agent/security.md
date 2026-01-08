# Security Standards

## Core Principles

1. **Never trust user input** - Always validate and sanitize
2. **Escape on output** - Use appropriate escaping functions
3. **Use nonces** - Verify intent for state-changing operations
4. **Check capabilities** - Ensure users have required permissions
5. **Prepared statements** - Always use `$wpdb->prepare()` for SQL

## Input Sanitization

```php
// Text
$text = \sanitize_text_field(\wp_unslash($_POST['text'] ?? ''));

// Email
$email = \sanitize_email($_POST['email'] ?? '');

// URL
$url = \sanitize_url($_POST['url'] ?? '');

// Integer
$id = absint($_POST['id'] ?? 0);

// Boolean
$active = !empty($_POST['active']);

// Array
$values = array_map('sanitize_text_field', $_POST['values'] ?? []);

// HTML (safe tags only)
$html = \wp_kses_post(\wp_unslash($_POST['content'] ?? ''));
```

## Output Escaping

```php
// HTML content
echo \esc_html($text);

// HTML attributes
<div class="<?php echo \esc_attr($class); ?>">

// URLs
<a href="<?php echo \esc_url($url); ?>">

// JavaScript
<script>var msg = '<?php echo \esc_js($message); ?>';</script>

// Late escaping (escape just before output)
$title = \esc_html($data['title']);
$link = \esc_url($data['link']);

printf( <<<EOF
<h1>{$title}</h1>
<a href="{$link}">Link</a>
EOF
);
```

## Nonce Verification

```php
// Create nonce
\wp_nonce_field('action_name', '_wpnonce');
$nonce = \wp_create_nonce('action_name');

// Verify form (NO backslash)
check_admin_referer('action_name', '_wpnonce');

// Verify AJAX (NO backslash)
\add_action('wp_ajax_my_action', function () {
  check_ajax_referer('ajax-nonce', '_wpnonce');

  if (!\current_user_can('manage_options')) {
    \wp_send_json_error('Unauthorized', 403);
  }

  $data = \sanitize_text_field($_POST['data'] ?? '');
  \wp_send_json_success(process_data($data));
});

// REST API
\wp_localize_script('script', 'wpData', [
  'nonce' => \wp_create_nonce('wp_rest'),
]);
```

## Capability Checks

```php
// Check capability
if (!\current_user_can('manage_options')) {
  \wp_die('Insufficient permissions');
}

// REST API
\register_rest_route('vendor/v1', '/endpoint', [
  'methods' => 'POST',
  'callback' => __NAMESPACE__ . '\handle_request',
  'permission_callback' => fn () => \current_user_can('manage_options'),
]);
```

## Database Security

**ALWAYS use prepared statements:**

```php
global $wpdb;

// ✅ Correct
$results = $wpdb->get_results($wpdb->prepare(
  "SELECT * FROM {$wpdb->posts} WHERE post_type = %s AND post_status = %s",
  $post_type,
  $status
));

// ❌ NEVER do this - SQL injection vulnerability
$results = $wpdb->get_results(
  "SELECT * FROM {$wpdb->posts} WHERE post_type = '{$post_type}'"
);

// Placeholders: %s (string), %d (integer), %f (float)
```

## Common Vulnerabilities

### SQL Injection
```php
// ❌ Vulnerable
$wpdb->get_results("SELECT * FROM table WHERE id = {$_GET['id']}");

// ✅ Fix
$wpdb->get_results($wpdb->prepare(
  "SELECT * FROM table WHERE ID = %d",
  absint($_GET['id'] ?? 0)
));
```

### XSS
```php
// ❌ Vulnerable
echo "<h1>" . $_POST['title'] . "</h1>";

// ✅ Fix
echo "<h1>" . \esc_html($_POST['title'] ?? '') . "</h1>";
```

### CSRF
```php
// ❌ Vulnerable
if (isset($_POST['delete'])) {
  delete_post($_POST['id']);
}

// ✅ Fix
check_admin_referer('delete_post', '_wpnonce');
if (!\current_user_can('delete_posts')) {
  \wp_die('Unauthorized');
}
delete_post(absint($_POST['id'] ?? 0));
```

## Security Checklist

Before deployment:
- [ ] All user input is sanitized
- [ ] All output is escaped appropriately
- [ ] Nonces are used for state-changing operations
- [ ] Capability checks are in place
- [ ] Database queries use prepared statements
- [ ] File uploads are validated
- [ ] API endpoints have permission callbacks
- [ ] HTTPS is enforced
- [ ] Debug mode is disabled in production

## Code Review Questions

1. Can this accept user input? → **Sanitize**
2. Does this display data? → **Escape**
3. Does this change state? → **Verify nonce**
4. Does this need permissions? → **Check capability**
5. Does this query the database? → **Use prepare()**

---

**See Also**: [PHP Standards](php-standards.md), [WordPress Integration](wordpress-integration.md)
