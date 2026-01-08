# PHPUnit Testing Standards

## Test Environment

- **Framework**: PHPUnit with WordPress Test Library
- **Configuration**: `/phpunit/phpunit.xml`
- **Bootstrap**: `/phpunit/bootstrap.php`
- **Test Discovery**: Files matching `*Test.php` pattern
- **Base Class**: `\WP_UnitTestCase`

## Running Tests

### Basic Test Execution

```bash
# Run all PHPUnit tests
pnpm test:php

# Alternative syntax using unified test command
pnpm test --use php
```

### Filtering Tests

#### By Test Method Name

Filter tests by method name (matches test methods containing the specified string):

```bash
# Using test:php script
pnpm test:php --php-opts "--filter test_login_admin"

# Using unified test command
pnpm test --use php --php-opts "--filter test_login_admin"
```

#### By Test Class Name

Filter tests by class name (matches test classes containing the specified string):

```bash
# Using test:php script
pnpm test:php --php-opts "--filter LoginTest"

# Using unified test command
pnpm test --use php --php-opts "--filter LoginTest"

# Alternative unified syntax
pnpm test -- --use php --php-opts "--filter LoginTest"
```

### Running Test Groups

Execute tests that belong to specific `@group` tags:

```bash
# Using test:php script
pnpm test:php --php-opts "--group test-plugin"

# Using unified test command
pnpm test --use php --php-opts "--group test-plugin"

# Exclude specific groups
pnpm test:php --php-opts "--exclude-group slow"
```

### Coverage Reports

```bash
# Generate HTML coverage report
pnpm test:php --php-opts "--coverage-html coverage"

# Generate text coverage report
pnpm test:php --php-opts "--coverage-text"

# Generate Clover XML coverage (for CI)
pnpm test:php --php-opts "--coverage-clover coverage.xml"
```

### Advanced Options

```bash
# Stop on first failure
pnpm test:php --php-opts "--stop-on-failure"

# Verbose output
pnpm test:php --php-opts "--verbose"

# Debug output
pnpm test:php --php-opts "--debug"

# Combine multiple options
pnpm test:php --php-opts "--filter LoginTest --stop-on-failure --verbose"
```

## Test File Structure

### File Location

Tests should be located alongside the code they test:

```
inc/feature/
├── index.php
├── functions.php
└── tests/
    └── phpunit/
        ├── FeatureTest.php
        └── FunctionsTest.php
```

### Test Class Template

```php
<?php

use const vendor\plugin\PLUGIN_FILE;
use const vendor\plugin\feature\FEATURE_CONSTANT;
use function vendor\plugin\feature\_function_to_test;

/**
 * Tests for feature functionality.
 *
 * Run this test class:
 *   pnpm test:php --php-opts "--filter FeatureTest"
 *
 * Run by group:
 *   pnpm test:php --php-opts "--group feature"
 *
 * @group plugin
 * @group feature
 */
class FeatureTest extends \WP_UnitTestCase {
  public function setUp(): void {
    if (!defined('WP_RUN_CORE_TESTS')) {
      define('WP_RUN_CORE_TESTS', true);
    }

    parent::setUp();
    \activate_plugin('text-domain/text-domain.php');
  }

  public function tearDown(): void {
    // Clean up test state
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

## Test Method Naming

Use descriptive names that explain what is being tested:

```php
public function test_function_returns_correct_value(): void { }
public function test_function_throws_exception_on_invalid_input(): void { }
public function test_function_handles_empty_array(): void { }
public function test_option_is_stored_in_database(): void { }
```

## Arrange-Act-Assert Pattern

Follow the AAA pattern for test structure:

```php
public function test_user_creation(): void {
  // Arrange - Set up test conditions
  $username = 'testuser';
  $email = 'test@example.com';

  // Act - Execute the functionality
  $user_id = \wp_create_user($username, 'password', $email);

  // Assert - Verify the results
  $this->assertIsInt($user_id, 'Should return user ID');
  $this->assertGreaterThan(0, $user_id, 'User ID should be positive');

  $user = \get_user_by('id', $user_id);
  $this->assertEquals($email, $user->user_email, 'Email should match');
}
```

## Common Assertions

### Equality Assertions

```php
$this->assertEquals($expected, $actual, 'Message');
$this->assertNotEquals($expected, $actual);
$this->assertSame($expected, $actual); // Strict comparison (===)
$this->assertNotSame($expected, $actual);
```

### Boolean Assertions

```php
$this->assertTrue($condition, 'Message');
$this->assertFalse($condition, 'Message');
```

### Type Assertions

```php
$this->assertIsString($value);
$this->assertIsInt($value);
$this->assertIsFloat($value);
$this->assertIsBool($value);
$this->assertIsArray($value);
$this->assertIsObject($value);
$this->assertNull($value);
$this->assertNotNull($value);
```

### Array Assertions

```php
$this->assertIsArray($value, 'Should be array');
$this->assertArrayHasKey('key', $array, 'Array should have key');
$this->assertArrayNotHasKey('key', $array);
$this->assertCount(5, $array, 'Array should have 5 elements');
$this->assertEmpty($array);
$this->assertNotEmpty($array);
$this->assertContains('value', $array);
```

### String Assertions

```php
$this->assertStringContainsString('substring', $string);
$this->assertStringStartsWith('prefix', $string);
$this->assertStringEndsWith('suffix', $string);
$this->assertMatchesRegularExpression('/pattern/', $string);
```

### Numeric Assertions

```php
$this->assertGreaterThan(5, $value);
$this->assertGreaterThanOrEqual(5, $value);
$this->assertLessThan(10, $value);
$this->assertLessThanOrEqual(10, $value);
```

### WordPress-Specific Assertions

```php
$this->assertInstanceOf(\WP_Error::class, $result);
$this->assertNotWPError($result);
$this->assertWPError($result);
```

## Testing WordPress Functions

### Testing Options

```php
public function test_option_storage(): void {
  $option_name = 'test_option';
  $value = 'test_value';

  // Test option doesn't exist initially
  $this->assertFalse(\get_option($option_name));

  // Test option storage
  \update_option($option_name, $value);
  $this->assertEquals($value, \get_option($option_name));

  // Test option deletion
  \delete_option($option_name);
  $this->assertFalse(\get_option($option_name));
}
```

### Testing User Meta

```php
public function test_user_meta(): void {
  $user_id = $this->factory->user->create();
  $meta_key = 'test_meta';
  $meta_value = 'test_value';

  // Test meta storage
  \update_user_meta($user_id, $meta_key, $meta_value);
  $this->assertEquals($meta_value, \get_user_meta($user_id, $meta_key, true));

  // Clean up
  \delete_user_meta($user_id, $meta_key);
}
```

### Testing Post Creation

```php
public function test_post_creation(): void {
  $post_id = $this->factory->post->create([
    'post_title'   => 'Test Post',
    'post_content' => 'Test content',
    'post_status'  => 'publish',
  ]);

  $this->assertIsInt($post_id);
  $this->assertGreaterThan(0, $post_id);

  $post = \get_post($post_id);
  $this->assertEquals('Test Post', $post->post_title);
  $this->assertEquals('publish', $post->post_status);
}
```

## Using Factories

WordPress test factories create test data:

```php
public function test_with_factories(): void {
  // Create test user
  $user_id = $this->factory->user->create([
    'role' => 'editor',
  ]);

  // Create test post
  $post_id = $this->factory->post->create([
    'post_author' => $user_id,
    'post_type'   => 'post',
  ]);

  // Create test comment
  $comment_id = $this->factory->comment->create([
    'comment_post_ID' => $post_id,
  ]);

  $this->assertIsInt($user_id);
  $this->assertIsInt($post_id);
  $this->assertIsInt($comment_id);
}
```

## Testing Hooks

### Testing Actions

```php
public function test_action_is_added(): void {
  $this->assertEquals(10, has_action('init', 'my_init_function'));
}

public function test_action_executes(): void {
  $flag = false;

  \add_action('test_action', function () use (&$flag) {
    $flag = true;
  });

  \do_action('test_action');

  $this->assertTrue($flag, 'Action should have executed');
}
```

### Testing Filters

```php
public function test_filter_modifies_value(): void {
  \add_filter('test_filter', function ($value) {
    return $value . '_modified';
  });

  $result = \apply_filters('test_filter', 'original');

  $this->assertEquals('original_modified', $result);
}
```

## Mocking and Stubs

### Using Mocks

```php
public function test_with_mock(): void {
  $mock = $this->createMock(MyClass::class);

  $mock->expects($this->once())
       ->method('myMethod')
       ->with($this->equalTo('argument'))
       ->willReturn('mocked_value');

  $result = $mock->myMethod('argument');

  $this->assertEquals('mocked_value', $result);
}
```

### Mocking WordPress Functions

```php
// Use WordPress function mocking when needed
public function test_with_wp_function_mock(): void {
  // Mock wp_remote_get
  $this->mock_wp_remote_get([
    'body' => '{"data": "value"}',
    'response' => ['code' => 200],
  ]);

  $result = my_function_that_uses_wp_remote_get();

  $this->assertIsArray($result);
}
```

## Testing Exceptions

```php
public function test_throws_exception_on_invalid_input(): void {
  $this->expectException(\InvalidArgumentException::class);
  $this->expectExceptionMessage('Invalid input provided');

  throw_exception_function();
}
```

## Testing Private Methods

Avoid testing private methods directly. Test public interface instead:

```php
// ❌ Don't test private methods
public function test_private_method(): void {
  $reflection = new \ReflectionClass(MyClass::class);
  $method = $reflection->getMethod('privateMethod');
  $method->setAccessible(true);
  // ...
}

// ✅ Test public interface
public function test_public_method_that_uses_private(): void {
  $instance = new MyClass();
  $result = $instance->publicMethod();
  $this->assertEquals('expected', $result);
}
```

## Test Data Cleanup

Always clean up in `tearDown()`:

```php
public function tearDown(): void {
  // Delete test options
  \delete_option('test_option');

  // Delete test transients
  \delete_transient('test_transient');

  // Reset global state
  unset($GLOBALS['test_var']);

  parent::tearDown();
}
```

## Test Organization

### Using @group Tags

Organize tests into groups for selective execution:

```php
/**
 * @group plugin
 * @group security
 * @group slow
 */
class SecurityTest extends \WP_UnitTestCase {
  // Tests
}
```

See [Running Test Groups](#running-test-groups) for execution examples.

### Using Data Providers

```php
/**
 * @dataProvider provideTestData
 */
public function test_with_multiple_inputs($input, $expected): void {
  $result = process_input($input);
  $this->assertEquals($expected, $result);
}

public function provideTestData(): array {
  return [
    'case 1' => ['input1', 'expected1'],
    'case 2' => ['input2', 'expected2'],
    'case 3' => ['input3', 'expected3'],
  ];
}
```

## Code Coverage

### Running with Coverage

```bash
# Generate HTML coverage report
pnpm test:php --php-opts "--coverage-html coverage"

# Generate text coverage
pnpm test:php --php-opts "--coverage-text"
```

### Coverage Annotations

```php
/**
 * @covers \vendor\plugin\feature\function_name
 */
public function test_function_name(): void {
  // Test
}
```

## Best Practices

1. **One assertion per test** (when possible) - Makes failures clear
2. **Use descriptive test names** - Explain what is being tested
3. **Follow AAA pattern** - Arrange, Act, Assert
4. **Test edge cases** - Empty arrays, null values, boundaries
5. **Use factories** - For creating test data
6. **Clean up state** - In tearDown()
7. **Test behavior, not implementation** - Test public interface
8. **Keep tests simple** - Each test should be easy to understand
9. **Test one thing** - Each test should verify one behavior
10. **Make tests independent** - Tests should not depend on each other

## Common Patterns

### Testing REST API Endpoints

```php
public function test_rest_endpoint_returns_data(): void {
  // Create user with permissions
  $user_id = $this->factory->user->create(['role' => 'administrator']);
  \wp_set_current_user($user_id);

  // Create request
  $request = new \WP_REST_Request('GET', '/vendor/v1/endpoint');
  $response = \rest_do_request($request);

  $this->assertEquals(200, $response->get_status());
  $this->assertIsArray($response->get_data());
}
```

### Testing AJAX Handlers

```php
public function test_ajax_handler(): void {
  // Set up user
  $user_id = $this->factory->user->create(['role' => 'administrator']);
  \wp_set_current_user($user_id);

  // Set up POST data
  $_POST['action'] = 'my_action';
  $_POST['_wpnonce'] = \wp_create_nonce('my-nonce');
  $_POST['data'] = 'test_value';

  // Catch output
  try {
    $this->_handleAjax('my_action');
  } catch (\WPAjaxDieContinueException $e) {
    // Expected for successful AJAX
  }

  $response = json_decode($this->_last_response, true);
  $this->assertTrue($response['success']);
}
```

---

**See Also**:
- [PHP Standards](php-standards.md)
- [Security Standards](security.md)
- [E2E Testing](e2e-testing.md)
