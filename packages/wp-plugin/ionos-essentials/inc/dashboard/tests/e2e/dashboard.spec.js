import { test, expect } from '@wordpress/e2e-test-utils-playwright';
import { execSync } from 'node:child_process';

test.describe(
  'essentials:dashboard ionos-essentials-dashboard-admin',
  {
    tag: ['@dashboard'],
  },
  () => {
    test('/dashboard contains My Account block', async ({ admin, page }) => {
      execSync('pnpm wp-env run tests-cli wp option update ionos_group_brand ionos');
      await admin.visitAdminPage('/');

      const body = await page.locator('body');
      await expect(body).toHaveText(/My Account/);
    });

  }
);
