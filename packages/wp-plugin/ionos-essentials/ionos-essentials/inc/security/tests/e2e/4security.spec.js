import { test, expect } from '@wordpress/e2e-test-utils-playwright';
import {createRequestUtils} from '/home/tom/projects/ionos-wordpress/playwright/e2e/helpers'

test('login 1', async ({ admin, page }) => {
  await admin.visitAdminPage('?page=ionos#tools');
  await expect(true).toBeTruthy();
});

test('logout', async ({ page }, testInfo) => {
  await page.goto('/wp-login.php?action=logout');
  await page.click('text=log out');

  await expect(true).toBeTruthy();
});

test('login 2', async ({ admin, page }, testInfo) => {
  const { requestContext, requestUtils } = await createRequestUtils(testInfo.config);
  await requestUtils.login();
if (!requestUtils.restContext) {
    throw new Error('requestUtils.restContext is undefined. login failed.');
  }

  await admin.visitAdminPage('?page=ionos#tools');

  await expect(true).toBeTruthy();
});
