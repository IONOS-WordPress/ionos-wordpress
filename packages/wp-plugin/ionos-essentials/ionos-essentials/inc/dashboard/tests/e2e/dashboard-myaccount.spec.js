import { test, expect } from '@wordpress/e2e-test-utils-playwright';
import { execTestCLI } from '../../../../../../../../playwright/wp-env';

test.describe(
  'essentials:dashboard ionos-essentials-dashboard-admin',
  {
    tag: ['@dashboard'],
  },
  () => {
    test('/dashboard contains My Account block', async ({ admin, page }) => {
      execTestCLI('wp --quiet option update ionos_group_brand ionos');
      await admin.visitAdminPage('/');

      const body = await page.locator('body');
      await expect(body).toHaveText(/My Account/);
    });
  }
);
