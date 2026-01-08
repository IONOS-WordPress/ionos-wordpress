# PHPUnit Testing Standards

## Environment

- **Framework**: PHPUnit with WordPress Test Library
- **Base Class**: `\WP_UnitTestCase`
- **Config**: `/phpunit/phpunit.xml`

## Running Tests

```bash
# All tests
pnpm test:php

# Filter by name
pnpm test:php --php-opts "--filter LoginTest"
pnpm test:php --php-opts "--filter test_login_admin"

# By group
pnpm test:php --php-opts "--group feature"
pnpm test:php --php-opts "--exclude-group slow"

# Coverage
pnpm test:php --php-opts "--coverage-html coverage"

# Debug
pnpm test:php --php-opts "--stop-on-failure --verbose"
```

## Test Structure

```php
<?php

use const vendor\plugin\PLUGIN_FILE;
use function vendor\plugin\feature\_function_to_test;

/**
 * Tests for feature.
 *
 * Run: pnpm test:php --php-opts "--filter FeatureTest"
 * Group: pnpm test:php --php-opts "--group feature"
 *
 * @group plugin
 * @group feature
 */
class FeatureTest extends \WP_UnitTestCase {
  public function setUp(): void {
    parent::setUp();
    \activate_plugin('plugin/plugin.php');
  }

  public function tearDown(): void {
    \delete_option('test_option');
    parent::tearDown();
  }

  public function test_feature_works_correctly(): void {
    // Arrange
    $expected = 'expected_value';

    // Act
    $actual = _function_to_test();

    // Assert
    $this->assertEquals($expected, $actual, 'Should return expected value');
  }
}
```

## Assertions

```php
// Equality
$this->assertEquals($expected, $actual);
$this->assertSame($expected, $actual);  // Strict ===

// Boolean
$this->assertTrue($condition);
$this->assertFalse($condition);

// Type
$this->assertIsString($value);
$this->assertIsInt($value);
$this->assertIsArray($value);
$this->assertNull($value);

// Array
$this->assertArrayHasKey('key', $array);
$this->assertCount(5, $array);
$this->assertContains('value', $array);

// String
$this->assertStringContainsString('substring', $string);

// Numeric
$this->assertGreaterThan(5, $value);
$this->assertLessThan(10, $value);
```

## Testing WordPress

```php
// Options
public function test_option_storage(): void {
  \update_option('test_option', 'value');
  $this->assertEquals('value', \get_option('test_option'));
}

// Factories
public function test_with_factories(): void {
  $user_id = $this->factory->user->create(['role' => 'editor']);
  $post_id = $this->factory->post->create(['post_author' => $user_id]);

  $this->assertIsInt($user_id);
  $this->assertGreaterThan(0, $post_id);
}

// Hooks
public function test_action_executes(): void {
  $flag = false;
  \add_action('test_action', function () use (&$flag) {
    $flag = true;
  });

  \do_action('test_action');
  $this->assertTrue($flag);
}
```

## Best Practices

1. One assertion per test (when possible)
2. Use descriptive test names
3. Follow AAA pattern (Arrange, Act, Assert)
4. Test edge cases
5. Use factories for test data
6. Clean up in tearDown()
7. Test behavior, not implementation
8. Keep tests simple
9. Make tests independent

---

**See Also**: [PHP Standards](php-standards.md), [Security Standards](security.md), [E2E Testing](e2e-testing.md)
