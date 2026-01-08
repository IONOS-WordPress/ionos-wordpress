# PHP Coding Standards

## Requirements

- **PHP 8.3+** - Use modern features freely
- **Prefer functions over classes** when possible

## Naming

- **Namespaces**: `vendor\plugin\feature_name`
- **Functions**: `render_callback()`, `get_user_data()`
- **Internal functions**: `_is_plugin_active()`
- **Classes**: `Tenant`, `WPScan` (PascalCase)
- **Constants**: `PLUGIN_FILE`, `WP_OPTION_NAME`

## Modern Syntax

```php
// Constructor promotion
private function __construct(
  public readonly string $id,
  public readonly string $title,
  private readonly bool $completed,
) {}

// Match expressions
$label = match ($slug) {
  'option1' => 'Value 1',
  'homepl'  => 'home.pl',
  default   => 'Default',
};

// Arrow functions (single expression)
$buttons = array_map(fn (array $button): string => sprintf(
  BUTTON_TEMPLATE,
  \esc_url($button['link'] ?? '#'),
  $button['css-class'] ?? 'button--secondary'
), $button_list);

// Anonymous functions (multi-line)
\add_action('admin_enqueue_scripts', function (string $hook): void {
  if ($hook !== 'toplevel_page_plugin') return;
  \wp_enqueue_script('plugin-script', \plugins_url('build/index.js', PLUGIN_FILE));
});

// Short array syntax
$items = ['item1', 'item2'];
$config = ['key' => 'value'];

// Combined isset
if (isset($data['key1'], $data['key2'], $data['key3'])) {
  process_data($data['key1'], $data['key2'], $data['key3']);
}

// Modern array functions (PHP 8.4+)
$user = array_find($users, fn ($u) => $u['id'] === $target_id);
$has_error = array_any($validations, fn ($v) => $v['error']);
$all_valid = array_all($items, fn ($item) => $item['valid']);
```

## Named Arguments

**Use for non-standard PHP functions with 3+ parameters:**

```php
\add_menu_page(
  page_title: 'Page Title',
  menu_title: 'Menu Title',
  capability: 'manage_options',
  menu_slug: 'page-slug',
  callback: __NAMESPACE__ . '\render_page'
);

// Skip parameters with default values
\wp_enqueue_script(
  handle: 'my-script',
  src: \plugins_url('script.js', PLUGIN_FILE),
  deps: ['jquery'],
  ver: '1.0.0'
  // in_footer defaults to false - skip it
);
```

## File Structure

```php
<?php

namespace vendor\plugin\feature;

defined('ABSPATH') || exit();

use function vendor\plugin\_helper;
use const vendor\plugin\PLUGIN_FILE;

const FEATURE_OPTION = 'option_name';

// Functions follow
```

## Templating: Heredoc-printf Pattern

**Always use heredoc for HTML output:**

```php
function render_block(array $data): void {
  // Escape ALL variables first (late escaping)
  $title = \esc_html($data['title'] ?? '');
  $link = \esc_url($data['link'] ?? '#');
  $css_class = \esc_attr($data['class'] ?? 'default');

  // Then output with clean heredoc
  printf( <<<EOF
<div class="block {$css_class}">
  <h2>{$title}</h2>
  <a href="{$link}">Link</a>
</div>
EOF
  );
}
```

**Why heredoc:**
- Context preservation
- Forces data preparation before output
- Better static analysis
- Cleaner code

## WordPress Functions

**Always prefix with `\` except nonce functions:**

```php
\add_action('hook', 'callback');
\get_option('option_name');
\home_url('/path');

// NO backslash for these:
check_admin_referer('action_name', '_wpnonce');
check_ajax_referer('ajax-nonce', '_wpnonce');
```

## Hook Patterns

```php
// Inline for single use
\add_action('admin_enqueue_scripts', function (string $hook): void {
  if ($hook !== 'toplevel_page_plugin') return;
  \wp_enqueue_script('script', \plugins_url('build/index.js', PLUGIN_FILE));
});

// Arrow function for single expression filters
\add_filter('excerpt_length', fn (): int => 50);

// Named when reused
\add_action('save_post', __NAMESPACE__ . '\handle_save');
\add_action('edit_post', __NAMESPACE__ . '\handle_save');

function handle_save(int $post_id): void {
  // Reused logic
}
```

## Code Formatting

- **Indentation**: 2 spaces
- **Line Length**: 120 characters max
- **No Yoda conditions**: `$var === 'value'` not `'value' === $var`
- **Strict equality**: Always use `===` and `!==`

## Common Patterns

```php
// Option management
$value = \get_option('option_name', 'default');
\update_option('option_name', $value, true);

// Transient caching
$cached = \get_transient('cache_key');
if ($cached === false) {
  $cached = expensive_operation();
  \set_transient('cache_key', $cached, HOUR_IN_SECONDS);
}

// Migration
const MIGRATION_VERSION = '1.0.0';
const MIGRATION_OPTION = 'plugin_migration_version';

\add_action('admin_init', function () {
  $current = MIGRATION_VERSION;
  $installed = \get_option(MIGRATION_OPTION, '0.0.0');

  if (version_compare($installed, $current, '>=')) return;

  perform_migration();
  \update_option(MIGRATION_OPTION, $current, true);
});
```

---

**See Also**: [Security Standards](security.md), [WordPress Integration](wordpress-integration.md), [PHPUnit Testing](phpunit-testing.md)
