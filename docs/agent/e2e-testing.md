# E2E Testing Standards

## Framework

- **Playwright** with `@wordpress/e2e-test-utils-playwright`
- **Config**: `/playwright.config.js`
- **Browser**: Chromium

## Running Tests

```bash
# All tests
pnpm test:e2e

# Specific file
pnpm test:e2e deep-links-block.spec.js

# By tag
pnpm test:e2e --e2e-opts "--grep @smoke"
pnpm test:e2e --e2e-opts "--grep-invert @slow"

# Debug mode
pnpm test:e2e --e2e-opts "--debug"
pnpm test:e2e --e2e-opts "--debug --headed" feature.spec.js

# Advanced
pnpm test:e2e --e2e-opts "--workers 4"
pnpm test:e2e --e2e-opts "--trace on"
pnpm test:e2e --e2e-opts "--headed --slow-mo 1000"
```

## Test Structure

```javascript
/**
 * E2E tests for feature.
 *
 * Run: pnpm test:e2e feature.spec.js
 * Debug: pnpm test:e2e --e2e-opts "--debug" feature.spec.js
 * Tags: pnpm test:e2e --e2e-opts "--grep @feature"
 */
import { test, expect } from '@wordpress/e2e-test-utils-playwright';

test.describe('plugin:feature description', { tag: ['@plugin', '@feature'] }, () => {
  test.beforeEach(async ({ page, admin }) => {
    await admin.visitAdminPage('admin.php?page=plugin');
  });

  test('descriptive test name', async ({ page, admin }) => {
    // Arrange
    const button = page.locator('.action-button');

    // Act
    await button.click();

    // Assert
    await expect(page.locator('.success')).toBeVisible();
  });
});
```

## Common Patterns

**Console error detection:**

```javascript
test('no console errors', async ({ page, admin }) => {
  const errors = [];
  page.on('console', (_) => _.type() === 'error' && !_?.text().includes('favicon.ico') && errors.push(_));

  await admin.visitAdminPage('/');
  await expect(errors).toHaveLength(0);
});
```

**Element visibility:**

```javascript
const element = page.locator('.feature');
await expect(element).toBeVisible();
await expect(element).toContainText('Expected');
```

**Form submission:**

```javascript
await page.fill('#email', 'test@example.com');
await page.click('[type="submit"]');
await expect(page.locator('.success')).toBeVisible();
```

**Wait for API:**

```javascript
const [response] = await Promise.all([
  page.waitForResponse((resp) => resp.url().includes('/wp-json/') && resp.status() === 200),
  page.click('.load-data'),
]);

const data = await response.json();
expect(data).toHaveProperty('success', true);
```

## WP-CLI Setup

```javascript
import { execTestCLI } from '../../../../../../../playwright/wp-env';

test.beforeAll(async () => {
  execTestCLI(`
    wp --quiet option delete test_option
    wp --quiet transient delete test_transient
  `);
});
```

## Assertions

```javascript
// Element
await expect(element).toBeVisible();
await expect(element).toBeHidden();
await expect(element).toBeEnabled();
await expect(element).toHaveCount(5);
await expect(element).toHaveText('Exact');
await expect(element).toContainText('Partial');
await expect(element).toHaveAttribute('href', '/url');

// Page
await expect(page).toHaveTitle('Title');
await expect(page).toHaveURL(/pattern/);

// Response
expect(response.ok()).toBeTruthy();
expect(response.status()).toBe(200);
```

## Selectors

```javascript
// CSS
page.locator('.class-name');
page.locator('#id');
page.locator('[data-test="value"]');
page.locator('.item').nth(0);
page.locator('.item').last();

// Text
page.locator('text="Exact"');
page.locator('text=/partial/i');

// Role (accessible)
page.getByRole('button', { name: 'Submit' });
page.getByRole('link', { name: 'Learn more' });
```

## Best Practices

1. Use WordPress utilities (`@wordpress/e2e-test-utils-playwright`)
2. Tag tests appropriately (`@feature`, `@smoke`, `@critical`)
3. Test user journeys, not just features
4. Use `toHaveCount()` for DOM element counts
5. Clean state with WP-CLI in `beforeAll()`
6. Independent tests - no dependencies
7. Test success and failure paths
8. Use appropriate selectors (data attributes, roles)

---

**See Also**: [JavaScript Standards](javascript-standards.md), [PHPUnit Testing](phpunit-testing.md), [WordPress Integration](wordpress-integration.md)
