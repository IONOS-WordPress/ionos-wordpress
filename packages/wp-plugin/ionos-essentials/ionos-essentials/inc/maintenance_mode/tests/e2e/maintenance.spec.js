import { test, expect } from '@wordpress/e2e-test-utils-playwright';
import { execTestCLI } from '../../../../../../../../playwright/wp-env';

test.describe(
  'essentials:dashboard maintenance',
  {
    tag: ['@dashboard', '@maintenance'],
  },
  () => {
    test.beforeAll(async () => {
      execTestCLI(`
      # set popup after timestamp to a far future date to prevent popups during e2e tests
      wp --quiet user meta update 1 ionos_popup_after_timestamp ${Math.MAX_SAFE_INTEGER}
      # set essentials welcome overlay already clicked away
      wp --quiet user meta update 1 ionos_essentials_welcome true
      # simulate extendify onboarding already done
      wp --quiet option update extendify_attempted_redirect_count 4
      # reset maintenance mode
      wp --quiet option delete ionos_essentials_maintenance_mode
    `);
    });

    test.afterAll(async () => {
      execTestCLI(`wp --quiet option delete ionos_essentials_maintenance_mode`);
    });

    test('maintenance mode is enabled', async ({ admin, page }) => {
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
  }
);

// test('maintenance mode is enabled', async ({ admin, page }) => {
//   // await globalThis.requestUtils.setupRest();

//   await admin.visitAdminPage('/');
//   await page.getByRole('button', { name: 'Tools' }).click();
//   await page.locator('#ionos_essentials_maintenance_mode').click();
// });
