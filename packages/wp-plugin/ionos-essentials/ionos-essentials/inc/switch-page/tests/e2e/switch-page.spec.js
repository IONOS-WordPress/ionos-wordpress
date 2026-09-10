import { restoreDbOnce, test, expect } from '../../../../../../../../playwright/e2e/fixtures';
test.beforeAll(restoreDbOnce);

test.describe(
  'Switch page',
  {
    tag: ['@dashboard', '@switchpage'],
  },
  () => {
    test('showing correct links', async ({ admin, page }) => {
      const errors = [];
      page.on('console', (msg) => {
        if (msg.type() === 'error') {
          errors.push(msg.text());
        }
      });

      await admin.visitAdminPage('/admin.php?page=ionos-onboarding');

      await expect(page.locator('a[href*="extendify-launch"]')).toBeVisible();
      await expect(page.getByRole('link', { name: 'Create manually' })).toBeVisible();
      // .toHaveLength(0) is best practice according to playwright docs
      await expect(errors).toHaveLength(0);
    });
  }
);
