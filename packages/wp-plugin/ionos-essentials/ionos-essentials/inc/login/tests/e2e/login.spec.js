import { test, expect } from '@wordpress/e2e-test-utils-playwright';

test.describe('Login page', () => {
  test('showing brand logo', async ({ page }) => {
    const errors = [];
    page.on('console', (msg) => {
      if (msg.type() === 'error') {
        errors.push(msg.text());
      }
    });

    await page.goto('/wp-login.php');

    await expect(page.locator('img[src*="ionos.svg"]')).toBeVisible();

    // Make sure there are no console errors. This is to catch any issues with loading the logo.
    await expect(errors).toEqual([]);
  });
});
