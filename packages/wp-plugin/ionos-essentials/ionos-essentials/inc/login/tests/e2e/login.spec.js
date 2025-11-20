import { test, expect } from '@wordpress/e2e-test-utils-playwright';

test.describe(
  'Login page',
  {
    tag: ['@dashboard', '@login'],
  },
  () => {
    test('showing brand logo', async ({ page }) => {
      const errors = [];
      page.on('console', (msg) => {
        if (msg.type() === 'error') {
          errors.push(msg.text());
        }
      });

      await page.goto('/wp-login.php');

      await expect(page.locator('img[src*="ionos.svg"]')).toBeVisible();

      // .toHaveLength(0) is best practice according to playwright docs
      await expect(errors).toHaveLength(0);
    });
  }
);
