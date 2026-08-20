import { restoreDbOnce, test, expect } from '../../../../../../../../playwright/e2e/fixtures';
test.beforeAll(restoreDbOnce);

test.describe(
  'essentials:dashboard ionos-essentials-dashboard-admin',
  {
    tag: ['@dashboard'],
  },
  () => {
    // the My Account block only renders for the ionos group brand, which the AFTER_START script
    // sets before the snapshot is taken.
    test('/dashboard contains My Account block', async ({ admin, page }) => {
      await admin.visitAdminPage('/');

      const body = await page.locator('body');
      await expect(body).toHaveText(/My Account/);
    });
  }
);
