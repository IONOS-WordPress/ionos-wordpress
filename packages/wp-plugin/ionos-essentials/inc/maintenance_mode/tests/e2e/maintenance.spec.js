import { test, expect } from '@wordpress/e2e-test-utils-playwright';
import { execTestCLI } from '../../../../../../../playwright/wp-env.js';

test.describe('Maintenance', () => {

  test.beforeAll(async () => {
      execTestCLI(
        `wp option delete ionos_essentials_maintenance_mode`
      );
  });

  test.afterAll(async () => {
      execTestCLI(
        `wp option delete ionos_essentials_maintenance_mode`
      );
  });


  test.skip('maintenance mode is enabled', async ({ admin, page }) => {
    await admin.visitAdminPage('/');
    await page.getByRole('button', { name: 'Tools' }).click();
    await page.locator('#ionos_essentials_maintenance_mode').click();

    // Still no redirection to the maintenance page, because we are logged in
    await page.goto('/');
    let body = await page.locator('body');
    await expect(body).toHaveText(/Blog/);


    await page.goto('/wp-login.php?action=logout');
    await page.click('text=log out');

    await page.goto('/');
    body = await page.locator('body');
    await page.waitForTimeout(1000);
    await expect(page).toHaveTitle('Construction');
  });


});
