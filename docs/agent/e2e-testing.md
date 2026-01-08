# End-to-End (E2E) Testing Standards

## Test Environment

- **Framework**: Playwright with `@playwright/test`
- **WordPress Integration**: `@wordpress/e2e-test-utils-playwright`
- **Configuration**: `/playwright.config.js`
- **Browser**: Chromium via `@playwright/browser-chromium`
- **Global Setup**: `/playwright/e2e/global-setup.js`

## Running Tests

### Basic Test Execution

```bash
# Run all E2E tests
pnpm test:e2e

# Alternative syntax using unified test command
pnpm test --use e2e
```

### Running Specific Test Files

Execute a single test file by providing its path or just the filename:

```bash
# Run specific test file with full path
pnpm test:e2e packages/wp-plugin/ionos-essentials/inc/dashboard/tests/e2e/deep-links-block.spec.js

# Run by filename only (Playwright auto-discovers)
pnpm test:e2e deep-links-block.spec.js

# Alternative with unified command
pnpm test --use e2e packages/wp-plugin/ionos-essentials/inc/dashboard/tests/e2e/deep-links-block.spec.js
```

### Filtering Tests by Tags

Use Playwright's [tag system](https://playwright.dev/docs/test-annotations#tag-tests) to filter tests:

```bash
# Run only tests tagged with @smoke
pnpm test:e2e --e2e-opts "--grep @smoke"

# Run only tests tagged with @dashboard
pnpm test:e2e --e2e-opts "--grep @dashboard"

# Run tests matching multiple tags (AND)
pnpm test:e2e --e2e-opts "--grep \"@dashboard.*@critical\""

# Exclude tests with specific tag
pnpm test:e2e --e2e-opts "--grep-invert @slow"

# Run all tests except those tagged with @editor
pnpm test:e2e --e2e-opts "--grep-invert @editor"
```

### Debug Mode

Run tests with Playwright's interactive debugger:

```bash
# Debug all tests
pnpm test:e2e --e2e-opts "--debug"

# Debug specific test file with full path
pnpm test:e2e --e2e-opts "--debug" packages/wp-plugin/ionos-essentials/inc/dashboard/tests/e2e/deep-links-block.spec.js

# Debug specific test file by name
pnpm test:e2e --e2e-opts "--debug" deep-links-block.spec.js

# Debug with specific tag
pnpm test:e2e --e2e-opts "--debug --grep @dashboard"
```

### Browser Options

```bash
# Run in headed mode (visible browser)
pnpm test:e2e --e2e-opts "--headed"

# Run in a specific browser
pnpm test:e2e --e2e-opts "--project chromium"
pnpm test:e2e --e2e-opts "--project firefox"
pnpm test:e2e --e2e-opts "--project webkit"

# Slow down execution for observation
pnpm test:e2e --e2e-opts "--headed --slow-mo 1000"
```

### Advanced Options

```bash
# Run tests in parallel with specific number of workers
pnpm test:e2e --e2e-opts "--workers 4"

# Run tests sequentially (one at a time)
pnpm test:e2e --e2e-opts "--workers 1"

# Generate trace for debugging
pnpm test:e2e --e2e-opts "--trace on"

# Update snapshots
pnpm test:e2e --e2e-opts "--update-snapshots"

# Retry failed tests
pnpm test:e2e --e2e-opts "--retries 2"

# Show browser console output
pnpm test:e2e --e2e-opts "--headed" deep-links-block.spec.js
```

### Combining Options

You can combine multiple options for powerful test execution:

```bash
# Debug specific tagged test in headed mode
pnpm test:e2e --e2e-opts "--debug --headed --grep @critical"

# Run specific file with trace and retries
pnpm test:e2e --e2e-opts "--trace on --retries 2" deep-links-block.spec.js

# Run tests excluding tags with visible browser
pnpm test:e2e --e2e-opts "--headed --grep-invert @slow"
```

## Test File Structure

### File Location

Tests should be located alongside the feature they test:

```
inc/feature/
├── index.php
├── functions.php
└── tests/
    └── e2e/
        ├── feature.spec.js
        └── feature-advanced.spec.js
```

### Test File Template

```javascript
/**
 * E2E tests for feature functionality.
 *
 * Run this test file:
 *   pnpm test:e2e feature.spec.js
 *
 * Run with debug:
 *   pnpm test:e2e --e2e-opts "--debug" feature.spec.js
 *
 * Run by tag:
 *   pnpm test:e2e --e2e-opts "--grep @feature"
 */
import { test, expect } from '@wordpress/e2e-test-utils-playwright';

test.describe(
  'feature:subfeature description',
  {
    tag: ['@feature', '@subfeature'],
  },
  () => {
    test.beforeAll(async () => {
      // Global setup for all tests in this describe block
    });

    test.beforeEach(async ({ page, admin }) => {
      // Setup before each test
    });

    test('descriptive test name', async ({ page, admin }) => {
      // Test implementation
    });

    test.afterEach(async () => {
      // Cleanup after each test
    });
  }
);
```

## Test Naming

Use descriptive names that explain the behavior being tested:

```javascript
test.describe('plugin:dashboard navigation', { tag: ['@dashboard'] }, () => {
  test('displays main dashboard page without errors', async ({ page, admin }) => {
    // Test
  });

  test('switches between tabs correctly', async ({ page, admin }) => {
    // Test
  });

  test('shows error message when API fails', async ({ page, admin }) => {
    // Test
  });
});
```

## WordPress Test Utilities

### Admin Navigation

```javascript
// Visit admin page
await admin.visitAdminPage('/');
await admin.visitAdminPage('admin.php?page=plugin');
await admin.visitAdminPage('plugins.php');

// Create post
await admin.createNewPost();
await admin.createNewPost({ postType: 'page' });
```

### Authentication

```javascript
// Tests run as admin by default via global setup
// To test as different user:
test('logged out user behavior', async ({ page }) => {
  // Logout handled by test
  await page.goto('/wp-login.php?action=logout');
  await page.goto('/');
  // Test logged-out behavior
});
```

### Request Utilities

```javascript
test('test with request utils', async ({ requestUtils }) => {
  // Make REST API requests
  const posts = await requestUtils.rest({
    path: '/wp/v2/posts',
  });

  expect(posts).toBeInstanceOf(Array);
});
```

## Common Test Patterns

### Console Error Detection

```javascript
test('ensure no console errors', async ({ page, admin }) => {
  const errors = [];
  page.on('console', (_) =>
    _.type() === 'error' &&
    // Ignore specific errors if needed
    !_?.text().includes('favicon.ico') &&
    errors.push(_)
  );

  await admin.visitAdminPage('/');

  // Should have no errors
  await expect(errors).toHaveLength(0);
});
```

### Element Visibility

```javascript
test('element is visible', async ({ page, admin }) => {
  await admin.visitAdminPage('admin.php?page=plugin');

  // Wait for and check visibility
  const element = page.locator('.feature-element');
  await expect(element).toBeVisible();
  await expect(element).toContainText('Expected text');
});
```

### Click and Navigation

```javascript
test('button click navigates correctly', async ({ page, admin }) => {
  await admin.visitAdminPage('/');

  // Click button
  await page.click('.navigation-button');

  // Wait for navigation
  await page.waitForLoadState('networkidle');

  // Verify new page
  await expect(page).toHaveURL(/admin\.php\?page=settings/);
});
```

### Form Submission

```javascript
test('form submission works', async ({ page, admin }) => {
  await admin.visitAdminPage('admin.php?page=settings');

  // Fill form
  await page.fill('#user_login', 'testuser');
  await page.fill('#user_email', 'test@example.com');

  // Submit
  await page.click('[name="wp-submit"]');

  // Verify success
  await expect(page.locator('.notice-success')).toBeVisible();
});
```

### Waiting for Network

```javascript
test('wait for API call', async ({ page, admin }) => {
  await admin.visitAdminPage('/');

  // Wait for specific request
  const [response] = await Promise.all([
    page.waitForResponse(resp =>
      resp.url().includes('/wp-json/vendor/') && resp.status() === 200
    ),
    page.click('.load-data-button'),
  ]);

  const data = await response.json();
  expect(data).toHaveProperty('success', true);
});
```

### Testing with WP-CLI

Use `execTestCLI` for WordPress setup:

```javascript
import { execTestCLI } from '../../../../../../../playwright/wp-env';

test.describe('feature tests', { tag: ['@feature'] }, () => {
  test.beforeAll(async () => {
    // Reset state using WP-CLI
    execTestCLI(`
      wp --quiet option delete test_option
      wp --quiet user meta delete admin test_meta_key
      wp --quiet transient delete test_transient
    `);
  });

  test('feature works after reset', async ({ page, admin }) => {
    // Test
  });
});
```

## Playwright Assertions

### Element Assertions

```javascript
await expect(element).toBeVisible();
await expect(element).toBeHidden();
await expect(element).toBeEnabled();
await expect(element).toBeDisabled();
await expect(element).toBeChecked();
await expect(element).toBeFocused();
await expect(element).toHaveCount(5);
await expect(element).toHaveText('Exact text');
await expect(element).toContainText('Partial text');
await expect(element).toHaveValue('value');
await expect(element).toHaveAttribute('href', '/url');
await expect(element).toHaveClass(/active/);
await expect(element).toHaveCSS('color', 'rgb(255, 0, 0)');
```

### Page Assertions

```javascript
await expect(page).toHaveTitle('Page Title');
await expect(page).toHaveURL('https://example.com/path');
await expect(page).toHaveURL(/pattern/);
```

### Response Assertions

```javascript
expect(response.ok()).toBeTruthy();
expect(response.status()).toBe(200);
await expect(response).toBeOK();

const body = await response.text();
expect(body).toContain('expected content');

const json = await response.json();
expect(json).toHaveProperty('success', true);
```

### Count Assertions

```javascript
// Prefer toHaveCount over toHaveLength for DOM queries
await expect(page.locator('.item')).toHaveCount(5);

// For arrays
expect(array).toHaveLength(5);
```

## Selectors

### CSS Selectors

```javascript
// Class selector
page.locator('.class-name')

// ID selector
page.locator('#element-id')

// Attribute selector
page.locator('[data-test="value"]')

// Compound selector
page.locator('.parent .child')

// nth-child
page.locator('.item').nth(0)  // First item
page.locator('.item').last()  // Last item
```

### Text Selectors

```javascript
// Exact text match
page.locator('text="Exact text"')

// Partial text match
page.locator('text=/partial text/i')  // Case-insensitive

// WordPress-style
page.locator('.plugin-title:has-text("Plugin Name")')
```

### Role Selectors

```javascript
// Accessible role selectors
page.getByRole('button', { name: 'Submit' })
page.getByRole('link', { name: 'Learn more' })
page.getByRole('textbox', { name: 'Email' })
page.getByRole('heading', { level: 1 })
```

## Handling Dynamic Content

### Wait for Element

```javascript
// Wait for element to appear
await page.waitForSelector('.element', { state: 'visible' });

// Wait for element to disappear
await page.waitForSelector('.loading', { state: 'hidden' });

// With timeout
await page.waitForSelector('.element', { timeout: 5000 });
```

### Wait for Function

```javascript
// Wait for custom condition
await page.waitForFunction(() => {
  return document.querySelector('.data')?.textContent !== 'Loading...';
});
```

### Auto-waiting

Playwright auto-waits for elements:

```javascript
// These automatically wait for element
await page.click('.button');          // Waits for visible, enabled
await page.fill('#input', 'text');    // Waits for visible, enabled
await expect(element).toBeVisible();   // Waits for condition
```

## API Testing

### Testing REST Endpoints

```javascript
test('REST API endpoint returns correct data', async ({ request }) => {
  const response = await request.post('/wp-json/vendor/v1/endpoint', {
    data: {
      id: 123,
      action: 'update',
    },
  });

  expect(response.ok()).toBeTruthy();

  const data = await response.json();
  expect(data).toHaveProperty('success', true);
  expect(data.data).toHaveProperty('id', 123);
});
```

### Testing with Authentication

```javascript
test('authenticated API request', async ({ request }) => {
  // Request context has auth from global setup
  const response = await request.get('/wp-json/vendor/v1/private-endpoint');

  expect(response.status()).toBe(200);
});
```

## Screenshots and Videos

### Taking Screenshots

```javascript
test('visual test', async ({ page, admin }) => {
  await admin.visitAdminPage('/');

  // Take screenshot
  await page.screenshot({ path: 'screenshots/dashboard.png' });

  // Full page screenshot
  await page.screenshot({
    path: 'screenshots/full-page.png',
    fullPage: true,
  });
});
```

### Videos

Configured in `playwright.config.js`:

```javascript
use: {
  video: 'retain-on-failure',  // Only save video on test failure
}
```

## Test Organization

### Using Tags

Organize tests with tags for selective execution:

```javascript
test.describe('plugin:dashboard', { tag: ['@dashboard', '@smoke'] }, () => {
  test('critical dashboard test', { tag: ['@critical'] }, async ({ page }) => {
    // Test
  });
});
```

See [Filtering Tests by Tags](#filtering-tests-by-tags) for execution examples.

### Test Hooks

```javascript
test.describe('feature tests', () => {
  test.beforeAll(async () => {
    // Runs once before all tests
  });

  test.beforeEach(async ({ page }) => {
    // Runs before each test
    await page.goto('/');
  });

  test.afterEach(async ({ page }) => {
    // Runs after each test
    await page.close();
  });

  test.afterAll(async () => {
    // Runs once after all tests
  });
});
```

## Debugging Tests

### Debug Mode

See [Debug Mode](#debug-mode) in the Running Tests section for all debug options.

Within tests, use `page.pause()` to pause execution:

```javascript
test('debug test', async ({ page }) => {
  await page.goto('/');

  // Pause execution and open Playwright Inspector
  await page.pause();

  // Continue testing after inspecting
  await page.click('.button');
});
```

### Console Logging

```javascript
test('debug test', async ({ page }) => {
  // Log page title
  console.log(await page.title());

  // Log element text
  const text = await page.locator('.element').textContent();
  console.log(text);

  // Log all console messages
  page.on('console', msg => console.log('Browser:', msg.text()));
});
```

### Trace Viewing

```bash
# Generate trace
pnpm test:e2e --trace on

# View trace
npx playwright show-trace trace.zip
```

## Best Practices

1. **Use WordPress utilities** - Prefer `@wordpress/e2e-test-utils-playwright`
2. **Tag tests appropriately** - Organize with `@feature`, `@smoke`, `@critical`
3. **Test user journeys** - Not just individual features
4. **Wait for network idle** - Use `waitForLoadState('networkidle')`
5. **Use `toHaveCount()`** - For DOM element counts
6. **Clean state** - Reset with WP-CLI in `beforeAll()`
7. **Descriptive test names** - Explain what is being tested
8. **Independent tests** - Tests shouldn't depend on each other
9. **Test both success and failure** - Happy path and error states
10. **Use appropriate selectors** - Prefer data attributes and roles

## Common Patterns

### Tab Switching

```javascript
test('tab switching works', async ({ page, admin }) => {
  await admin.visitAdminPage('admin.php?page=plugin');

  // Click tab
  await page.click('[data-tab="tools"]');

  // Verify active tab
  await expect(page.locator('[data-tab="tools"]')).toHaveClass(/active/);
  await expect(page.locator('#tools')).toBeVisible();
});
```

### Modal/Dialog Interaction

```javascript
test('modal opens and closes', async ({ page, admin }) => {
  await admin.visitAdminPage('/');

  // Open modal
  await page.click('#open-modal');
  await expect(page.locator('.modal')).toBeVisible();

  // Close modal
  await page.click('.modal-close');
  await expect(page.locator('.modal')).toBeHidden();
});
```

### Form Validation

```javascript
test('form shows validation errors', async ({ page, admin }) => {
  await admin.visitAdminPage('admin.php?page=settings');

  // Submit empty form
  await page.click('[type="submit"]');

  // Verify error messages
  await expect(page.locator('.error-message')).toBeVisible();
  await expect(page.locator('.error-message')).toContainText('required');
});
```

---

**See Also**:
- [JavaScript Standards](javascript-standards.md)
- [PHPUnit Testing](phpunit-testing.md)
- [WordPress Integration](wordpress-integration.md)
