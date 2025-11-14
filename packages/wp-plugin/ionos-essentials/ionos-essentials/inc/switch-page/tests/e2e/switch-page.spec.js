import { test, expect } from '@wordpress/e2e-test-utils-playwright';

test.describe('Switch page', () => {
  test('showing correct links', async ({ admin, page }) => {
    const errors = [];
    page.on('console', (msg) => {
      if (msg.type() === 'error') {
        errors.push(msg.text());
      }
    });

    await admin.visitAdminPage('/admin.php?page=ionos-onboarding');

    await expect(page.locator('a[href*="extendify-launch"]')).toBeVisible();
    await expect(page.locator('a[href$="admin.php?page=ionos"].link-btn')).toBeVisible();

    await expect(errors).toEqual([]);
  });
});
