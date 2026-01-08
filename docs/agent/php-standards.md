# PHP Coding Standards

## PHP Version

- **Minimum**: PHP 8.3+
- Use modern PHP 8.3+ features freely

## Naming Conventions

- **Namespaces**: Use lowercase with underscores: `vendor\plugin\feature_name`
- **Functions**: Use snake_case: `render_callback()`, `get_user_data()`
- **Internal functions**: Prefix with underscore: `_is_plugin_active()`, `_install()`
- **Classes**: Use PascalCase: `Tenant`, `WPScan`, `NBA`
- **Constants**: Use UPPER_SNAKE_CASE: `PLUGIN_FILE`, `WP_OPTION_LAST_INSTALL_DATA`

## Modern PHP Syntax (PHP 8.3+)

### Architectural Preference

- **Use functions in favor of classes when possible**
- Prefer function-based approach over class-based for simple logic
- Use classes only for stateful objects or complex behavior

### Constructor Property Promotion

```php
private function __construct(
  public readonly string $id,
  public readonly string $title,
  private readonly bool $completed,
  public readonly array $categories,
) {
  // Constructor body
}
```

### Match Expressions

```php
$label = match ($slug) {
  'option1'  => 'Value 1',
  'fasthosts' => 'Fasthosts',
  'homepl'    => 'home.pl',
  default     => 'Default',
};
```

### Arrow Functions

**Use arrow functions for simple, single-expression callbacks**:

```php
$buttons = array_map(fn (array $button): string => sprintf(
  BUTTON_TEMPLATE,
  \esc_url($button['link'] ?? '#'),
  $button['css-class'] ?? 'button--secondary'
), $button_list);

// Filter with arrow function
$active = array_filter($items, fn ($item) => $item['active']);

// Sort with arrow function
usort($items, fn ($a, $b) => $a['order'] <=> $b['order']);
```

### Anonymous Functions

**Use anonymous/inlined functions if only used once, instead of named functions**:

**Prefer arrow functions for single expressions, regular functions for multi-line logic**:

```php
// ✅ Best - Arrow function for single expression
\add_filter('the_content', fn (string $content): string => $content . ' [Read more]');

// ✅ Good - Arrow function with simple logic
\add_filter('excerpt_length', fn (): int => 50);

// ❌ Avoid - Anonymous function when arrow function works
\add_filter('the_content', function (string $content): string {
  return $content . ' [Read more]';
});

// ✅ Good - Regular anonymous function for multi-line logic
\add_action('admin_enqueue_scripts', function (string $hook): void {
  if ($hook !== 'toplevel_page_plugin') {
    return;
  }

  \wp_enqueue_script('plugin-script', \plugins_url('build/index.js', PLUGIN_FILE));
});

// ✅ Good - Named function when used multiple times
\add_action('save_post', __NAMESPACE__ . '\handle_save');
\add_action('edit_post', __NAMESPACE__ . '\handle_save');

function handle_save(int $post_id): void {
  // Shared logic
}

// ❌ Avoid - Named function used only once
\add_action('init', __NAMESPACE__ . '\one_time_setup');

function one_time_setup(): void {
  // Logic used nowhere else
}
```

**Decision Guide**:
- **Single expression** → Arrow function `fn() =>`
- **Multi-line logic, used once** → Anonymous function `function() {}`
- **Used multiple times** → Named function

### Null Coalescing

```php
$value = $button['title'] ?? '';
$count = $options['count'] ?? 0;
```

### Isset with Multiple Variables

**Combine multiple `isset()` checks into a single call for better readability**:

```php
// ✅ Good - Combined isset() call
if (isset($data['key1'], $data['key2'], $data['key3'])) {
  // All three keys exist
  process_data($data['key1'], $data['key2'], $data['key3']);
}

// ✅ Good - Check multiple variables
if (isset($user, $post, $comment)) {
  // All three variables are set
}

// ❌ Avoid - Multiple isset() calls with &&
if (isset($data['key1']) && isset($data['key2']) && isset($data['key3'])) {
  process_data($data['key1'], $data['key2'], $data['key3']);
}

// Note: isset() with multiple parameters returns true only if ALL are set
// Use null coalescing for individual defaults
$key1 = $data['key1'] ?? 'default1';
$key2 = $data['key2'] ?? 'default2';
```

### Short Array Syntax

**Always use short array syntax `[]` instead of `array()`**:

```php
// ✅ Good - Short array syntax
$items = ['item1', 'item2', 'item3'];

$config = [
  'key1' => 'value1',
  'key2' => 'value2',
  'nested' => [
    'subkey' => 'subvalue',
  ],
];

$empty = [];

// ❌ Avoid - Old array syntax
$items = array('item1', 'item2', 'item3');
$config = array('key' => 'value');
```

### Array Functions

**Use modern array functions for cleaner, more expressive code**:

```php
// ✅ Good - array_find() (PHP 8.4+)
$user = array_find($users, fn ($user) => $user['id'] === $target_id);
$active = array_find($items, fn ($item) => $item['active'] === true);

// ✅ Good - array_any() and array_all() (PHP 8.4+)
$has_error = array_any($validations, fn ($v) => $v['error']);
$all_valid = array_all($items, fn ($item) => $item['valid']);

// ❌ Avoid - Verbose loop-based approach
$user = null;
foreach ($users as $u) {
  if ($u['id'] === $target_id) {
    $user = $u;
    break;
  }
}

// ✅ Good - array_filter for subsets
$active_items = array_filter($items, fn ($item) => $item['active']);

// ✅ Good - array_map for transformations
$ids = array_map(fn ($item) => $item['id'], $items);

// ✅ Good - array_reduce for accumulation
$total = array_reduce($items, fn ($sum, $item) => $sum + $item['price'], 0);
```

### Named Arguments

**Use named arguments for non-standard PHP functions with more than 3 parameters**:

```php
// ✅ Good - Named arguments for WordPress function with 3+ parameters
\add_menu_page(
  page_title: 'Page Title',
  menu_title: 'Menu Title',
  capability: 'manage_options',
  menu_slug: 'page-slug',
  callback: __NAMESPACE__ . '\render_page',
  icon_url: 'dashicons-admin-generic',
  position: 30
);

// ✅ Good - Named arguments for submenu page
\add_submenu_page(
  parent_slug: 'my-plugin',
  page_title: 'Settings',
  menu_title: 'Settings',
  capability: 'manage_options',
  menu_slug: 'my-plugin-settings',
  callback: __NAMESPACE__ . '\render_settings'
);

// ✅ Good - Named arguments for custom function
custom_register_feature(
  feature_name: 'dashboard',
  enabled: true,
  priority: 10,
  dependencies: ['core', 'api']
);

// ✅ Good - 3 or fewer parameters can use positional
\add_action('init', __NAMESPACE__ . '\setup', 10);

// ❌ Avoid - Positional arguments with 4+ parameters (hard to read)
\add_menu_page(
  'Page Title',
  'Menu Title',
  'manage_options',
  'page-slug',
  __NAMESPACE__ . '\render_page',
  'dashicons-admin-generic',
  30
);
```

**When to use named arguments**:
- **Always**: Non-standard PHP functions with 4+ parameters
- **Optional but recommended**: Functions with 3 parameters when clarity is needed
- **Not needed**: Standard PHP functions (array_map, sprintf, etc.)
- **Not needed**: Functions with 1-2 parameters

**Skip parameters with default values**:

```php
// ✅ Good - Skip optional parameters that use defaults
\wp_enqueue_script(
  handle: 'my-script',
  src: \plugins_url('script.js', PLUGIN_FILE),
  deps: ['jquery'],
  ver: '1.0.0'
  // in_footer defaults to false, so we skip it
);

// ✅ Good - Only include parameters that differ from defaults
\add_menu_page(
  page_title: 'My Plugin',
  menu_title: 'My Plugin',
  capability: 'manage_options',
  menu_slug: 'my-plugin',
  callback: __NAMESPACE__ . '\render_page'
  // icon_url defaults to '', position defaults to null - skip them
);

// ❌ Avoid - Including parameters just to set them to defaults
\wp_enqueue_script(
  handle: 'my-script',
  src: \plugins_url('script.js', PLUGIN_FILE),
  deps: [],
  ver: false,
  in_footer: false  // Unnecessary - false is the default
);
```

## File Structure

### Standard PHP File Header

```php
<?php

namespace vendor\plugin\feature\subfeature;

defined('ABSPATH') || exit();

use function vendor\plugin\_helper_function;
use const vendor\plugin\PLUGIN_FILE;
use vendor\plugin\ClassName;

const FEATURE_CONSTANT = 'value';
```

**Key Points**:
- **ALWAYS** include `defined('ABSPATH') || exit();` after namespace
- Use explicit `use` statements for functions, classes, and constants
- Group imports by type (functions, constants, classes)
- Define constants at namespace level

## PHP Output & Templating Standards

### Architectural Preference: Heredoc over Tag Switching

To maintain high code quality, security, and readability, **follow the Heredoc-printf pattern** for HTML generation rather than frequent PHP tag switching.

#### Why Heredoc?

1. **Context Preservation**: Keeping HTML in a single string helps maintain correct DOM structure
2. **Variable Scope**: Forces preparation of all data variables before output, reducing "spaghetti logic"
3. **Static Analysis**: More compatible with PHP static analysis tools (PHPStan, Psalm)
4. **Linting**: Easier to lint and maintain compared to fragmented code
5. **IDE Support**: Better syntax highlighting and formatting

### Comparison

#### ❌ Anti-Pattern (Tag Switching)

```php
// Fragmented, hard to read, and prone to indentation errors
?>
<div class="<?php echo \esc_attr(get_foo()); ?>">
    <?php echo \__('label', 'my-plugin'); ?>
</div>
<?php
```

#### ✅ Preferred Pattern (Heredoc)

```php
// Clean, secure, and easy for IDEs to highlight
$foo   = \esc_attr(get_foo());
$label = \__('label', 'my-plugin');

printf( <<<EOF
<div class="{$foo}">
    {$label}
</div>
EOF
);
```

### Mandatory Late Escaping

**Security is paramount**. All dynamic data must be escaped at the point of output.

**Rules**:
1. Process all variables (escaping and localization) **immediately before** the printf/heredoc block
2. Use the global namespace prefix `\` for functions to optimize execution (e.g., `\esc_attr`)
3. Never interpolate unescaped data into heredoc strings

**Example**:

```php
function render_block(array $data): void {
  // Prepare and escape ALL variables first
  $title       = \esc_html($data['title'] ?? '');
  $description = \esc_html($data['description'] ?? '');
  $link        = \esc_url($data['link'] ?? '#');
  $link_text   = \esc_html($data['link_text'] ?? 'Read more');
  $css_class   = \esc_attr($data['class'] ?? 'default-class');

  // Then output with clean heredoc
  printf( <<<EOF
<div class="block {$css_class}">
  <h2>{$title}</h2>
  <p>{$description}</p>
  <a href="{$link}">{$link_text}</a>
</div>
EOF
  );
}
```

### Complex Template Pattern

For more complex templates with loops:

```php
function render_list(array $items): void {
  // Prepare items HTML
  $items_html = '';
  foreach ($items as $item) {
    $title = \esc_html($item['title']);
    $link  = \esc_url($item['link']);

    $items_html .= <<<EOF
    <li>
      <a href="{$link}">{$title}</a>
    </li>
EOF;
  }

  // Output complete template
  printf( <<<EOF
<ul class="item-list">
  {$items_html}
</ul>
EOF
  );
}
```

## WordPress Function Prefixing

**ALWAYS** prefix WordPress core functions with backslash for namespace resolution:

```php
\add_action('hook', 'callback');
\add_filter('filter', 'callback');
\get_option('option_name');
\update_option('option_name', $value);
\home_url('/path');
\admin_url('admin.php?page=page');
\plugins_url('path', PLUGIN_FILE);
```

### Exceptions

**DO NOT** prefix these functions with backslash:
- `check_admin_referer()` - WordPress nonce verification
- `check_ajax_referer()` - AJAX nonce verification

```php
// Correct
check_admin_referer('action_name', '_wpnonce');
check_ajax_referer('ajax-nonce', '_wpnonce');
```

## Hook Registration Patterns

### Action Hooks

**Use inline functions for single-use callbacks, named functions when reused**:

```php
// ✅ Good - Inline function for single-use callback
\add_action('admin_enqueue_scripts', function (string $hook): void {
  if ($hook !== 'toplevel_page_plugin') {
    return;
  }

  \wp_enqueue_script('plugin-script', \plugins_url('build/index.js', PLUGIN_FILE));
});

// ✅ Good - Named function when used multiple times
\add_action('save_post', __NAMESPACE__ . '\handle_post_save');
\add_action('edit_post', __NAMESPACE__ . '\handle_post_save');

function handle_post_save(int $post_id): void {
  // Reused logic
}
```

### Filter Hooks

**Prefer arrow functions for single expressions, regular functions for complex logic**:

```php
// ✅ Best - Arrow function for single expression
\add_filter('the_content', fn (string $content): string => $content . ' [Read more]');

// ✅ Best - Arrow function with simple operation
\add_filter('excerpt_length', fn (): int => 50);

// ✅ Good - Arrow function with single-line expression
\add_filter('option_active_plugins', fn (array $plugins): array =>
  array_unique(array_merge($plugins, $custom_plugins))
);

// ✅ Good - Regular function for multi-line logic
\add_filter('post_content', function (string $content): string {
  // Complex transformation
  $content = strip_tags($content);
  $content = wpautop($content);
  return $content;
});

// ✅ Good - Named function when complex or reused
\add_filter('post_content', __NAMESPACE__ . '\process_content');
\add_filter('page_content', __NAMESPACE__ . '\process_content');

function process_content(string $content): string {
  // Complex logic used in multiple places
  return $processed_content;
}
```

### Hook Naming Convention

**Use namespace prefix for named callback functions**:

```php
namespace vendor\plugin\feature;

// Named function reference
\add_action('init', __NAMESPACE__ . '\initialize');

function initialize(): void {
  // Implementation
}
```

## Code Formatting

### General Rules

- **Indentation**: 2 spaces (no tabs)
- **Line Length**: Maximum 120 characters
- **Line Endings**: Unix-style (LF)
- **Trailing Whitespace**: Remove all trailing whitespace
- **Final Newline**: Always include final newline in files

### Array Formatting

```php
$config = [
  'key1' => 'value1',
  'key2' => 'value2',
  'nested' => [
    'subkey' => 'subvalue',
  ],
];
```

### Comparison Operators

**Do not use Yoda conditions - keep literals on the right side**:

```php
// ✅ Good - Natural reading order (literal on right)
if ($status === 'active') {
  // Code
}

if ($count > 0) {
  // Code
}

if ($user !== null) {
  // Code
}

if ($hook === 'toplevel_page_plugin') {
  // Code
}

// ❌ Avoid - Yoda conditions (literal on left)
if ('active' === $status) {
  // Code
}

if (0 < $count) {
  // Code
}

if (null !== $user) {
  // Code
}
```

**Why avoid Yoda conditions**:
- Modern PHP (8.3+) with strict typing makes accidental assignment in conditions less likely
- Natural reading order improves code readability
- IDE warnings catch assignment in conditions
- Type hints prevent many common mistakes

**Comparison operators**:
```php
// Strict equality (always prefer)
$a === $b  // Equal value and type
$a !== $b  // Not equal value or type

// Loose equality (avoid when possible)
$a == $b   // Equal value (type coercion)
$a != $b   // Not equal value

// Numeric comparison
$a > $b    // Greater than
$a >= $b   // Greater than or equal
$a < $b    // Less than
$a <= $b   // Less than or equal
$a <=> $b  // Spaceship operator (returns -1, 0, or 1)
```

### Control Structures

```php
if ($condition) {
  // Code
}

foreach ($items as $item) {
  // Code
}

for ($i = 0; $i < $count; $i++) {
  // Code
}

while ($condition) {
  // Code
}
```

## Error Handling

### Exception Handling

```php
try {
  // Risky operation
} catch (\Exception $e) {
  error_log('Error message: ' . $e->getMessage());
  // Handle error appropriately
}
```

### Error Logging

```php
error_log('Plugin Name: Error message - ' . $error_details);
```

**Do not use backslash prefix for `error_log`** - it's a PHP native function, not WordPress.

## Type Declarations

Use type hints and return types:

```php
function process_data(string $input, int $count = 10): array {
  // Implementation
  return $results;
}

function render_template(array $data): void {
  // No return value
  echo $template;
}
```

## Documentation

### Inline Comments

```php
// Single-line comment for brief explanations

/*
 * Multi-line comment for more detailed explanations
 * that span multiple lines
 */
```

### When to Comment

- Document "why" not "what"
- Explain complex algorithms
- Document business logic
- Add TODO comments: `// TODO(username): Description`
- **DO NOT** add file-level docblocks unless meaningful

## Common Patterns

### Option Management

```php
$value = \get_option('option_name', 'default_value');
\update_option('option_name', $value, true);
\delete_option('option_name');
```

### Transient Caching

```php
$cached = \get_transient('cache_key');
if ($cached === false) {
  $cached = expensive_operation();
  \set_transient('cache_key', $cached, HOUR_IN_SECONDS);
}
return $cached;
```

### User Meta

```php
$meta = \get_user_meta(\get_current_user_id(), 'meta_key', true);
\update_user_meta($user_id, 'meta_key', $value);
\delete_user_meta($user_id, 'meta_key');
```

## Plugin-Specific Patterns

### Must-Use Plugin Dual-File Loading

```php
// mu-plugins/plugin-loader.php
<?php
/**
 * Plugin Name: Plugin Name
 */
require_once __DIR__ . '/plugin-name/index.php';
```

```php
// mu-plugins/plugin-name/index.php
<?php

namespace vendor\plugin_name;

defined('ABSPATH') || exit();

// Plugin implementation
```

### Feature Module Structure

```php
// inc/feature-name/index.php
<?php

namespace vendor\plugin\feature_name;

defined('ABSPATH') || exit();

const FEATURE_OPTION = 'option_name';

\add_action('admin_init', __NAMESPACE__ . '\setup');

function setup(): void {
  // Setup logic
}
```

## Migration Patterns

```php
namespace vendor\plugin\migration;

const MIGRATION_VERSION = '1.0.0';
const MIGRATION_OPTION = 'plugin_migration_version';

\add_action('admin_init', function () {
  $current = MIGRATION_VERSION;
  $installed = \get_option(MIGRATION_OPTION, '0.0.0');

  if (version_compare($installed, $current, '>=')) {
    return;
  }

  perform_migration();
  \update_option(MIGRATION_OPTION, $current, true);
});

function perform_migration(): void {
  // Migration logic
}
```

## Performance Best Practices

1. **Cache expensive operations** using transients
2. **Limit database queries** - use batch operations
3. **Conditional loading** - only load code when needed
4. **Static caching** - cache file existence checks
5. **Early returns** - exit early when conditions not met

```php
function expensive_operation(): array {
  static $cache = null;

  if ($cache !== null) {
    return $cache;
  }

  $cache = perform_expensive_work();
  return $cache;
}
```

---

**See Also**:
- [Security Standards](security.md)
- [WordPress Integration](wordpress-integration.md)
- [PHPUnit Testing](phpunit-testing.md)
