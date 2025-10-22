import { test, expect } from '@wordpress/e2e-test-utils-playwright';
import { execTestCLI } from '../../../../../../../../playwright/wp-env';

test.describe(
  'essentials:dashboard security options',
  {
    tag: ['@dashboard', '@security'],
  },
  () => {
    test.beforeAll(async () => {
      execTestCLI(`
      wp --quiet option delete IONOS_SECURITY_FEATURE_OPTION
    `);
    });

    test('user can set option', async ({ admin, page }) => {
      await admin.visitAdminPage('?page=ionos#tools');
      const body = page.locator('body');
      const toggle = body.locator('#IONOS_SECURITY_FEATURE_OPTION_PEL');

      await expect(toggle).toBeChecked();
      await toggle.click();
      await page.waitForTimeout(3000);
      await page.reload();
      await expect(toggle).not.toBeChecked();
    });
  }
);
