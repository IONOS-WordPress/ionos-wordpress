import { test, expect } from '@wordpress/e2e-test-utils-playwright';
import { execTestCLI } from '../../../../../../../playwright/wp-env.js';

test.describe('options', () => {
  test.beforeAll(async () => {
    execTestCLI(`
      wp option delete IONOS_SECURITY_FEATURE_OPTION
    `);
  });

  test('user can set option', async ({ admin, page }) => {
    await admin.visitAdminPage('?page=ionos#tools');
    const body = page.locator('body');
    const toggle = body.locator('#mailnotify');

    await expect(toggle).not.toBeChecked();
    await toggle.click();
    
    await page.reload();
    await expect(toggle).toBeChecked();
  });
});
