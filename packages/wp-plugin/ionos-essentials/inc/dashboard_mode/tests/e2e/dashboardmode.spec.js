import { test, expect } from '@wordpress/e2e-test-utils-playwright';
import { execTestCLI } from '../../../../../../../playwright/wp-env.js';

test.describe('Dashboard', () => {

  test.beforeAll(async () => {
      execTestCLI(
        `wp option delete ionos_essentials_dashboard_mode`
      );
  });

  test.afterAll(async () => {
      execTestCLI(
        `wp option delete ionos_essentials_dashboard_mode`
      );
  });


  test('Default mode: dashboard is essentials', async ({ admin, page }) => {
    await admin.visitAdminPage('/');
    const body = await page.locator('body');
    await expect(body).toHaveText(/Unlock Your Website\'s Potential/);
  });

  test('Wordpress dashboard: dashboard is wordpress', async ({ admin, page }) => {
    await admin.visitAdminPage('/');
    const body = await page.locator('body');
    await expect(body).toHaveText(/Unlock Your Website\'s Potential/);

    await admin.visitAdminPage('/admin.php?page=ionos#tools');
    await page.locator('#ionos_essentials_dashboard_mode').click();
    await page.waitForTimeout(1000);


    await page.goto('/wp-login.php?action=logout');
    await page.click('text=log out');

    await login(page);
    await expect(body).toHaveText(/Dashboard/);
  });
});


async function login(page) {
  // Normally we use the wp-env standard user, but we need to create a new user for testing security
  // as the wp-env user has a leaked password
  await page.goto('/wp-admin');
  await page.fill('#user_login', 'admin');
  await page.fill('#user_pass', 'g0lasch0815!');
  await page.click('[name="wp-submit"]');

  // Click the Confirm-email button, if it exists
  try {
    await page.waitForSelector('button:has-text("Log In")', { timeout: 5000 });
    await page.click('button:has-text("Log In")');
  } catch (error) {
    // Silence is golden.
  }
}
