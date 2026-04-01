import { test, expect } from '@wordpress/e2e-test-utils-playwright';
import { execTestCLI } from '../../../../../../../../playwright/wp-env';

test.describe('Tabs', () => {
  test.beforeAll(async () => {
    execTestCLI(`
          # set popup after timestamp to a far future date to prevent popups during e2e tests
          wp --quiet user meta update 1 ionos_popup_after_timestamp ${Math.MAX_SAFE_INTEGER}
          # set essentials welcome overlay already clicked away
          wp --quiet user meta update 1 ionos_essentials_welcome true
          # simulate extendify onboarding already done
          wp --quiet option update extendify_attempted_redirect_count 4
        `);
  });

  test('user can switch between tabs', async ({ admin, page }) => {
    await admin.visitAdminPage('/');

    const body = await page.locator('body');
    const tab1 = body.locator('#overview');
    const tab2 = body.locator('#tools');

    await expect(tab1).toBeVisible();
    await expect(tab2).not.toBeVisible();

    await page.getByRole('button', { name: 'Tools' }).click();

    await expect(tab1).not.toBeVisible();
    await expect(tab2).toBeVisible();
  });
});
