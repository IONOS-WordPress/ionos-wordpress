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

    test('/dashboard visual regression', async ({ admin, page }) => {
      await admin.visitAdminPage('/');
      const screenshot = await page.screenshot({ fullPage: true });
      expect(screenshot).toMatchSnapshot('dashboard-overview.png');

      await page.getByRole('button', { name: 'Tools' }).click();
      const toolsScreenshot = await page.screenshot({ fullPage: true });
      expect(toolsScreenshot).toMatchSnapshot('dashboard-tools-tab.png');
    });
  }
);
