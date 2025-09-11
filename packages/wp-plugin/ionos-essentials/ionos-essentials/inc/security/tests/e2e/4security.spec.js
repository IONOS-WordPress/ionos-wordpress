import { test, expect } from '@wordpress/e2e-test-utils-playwright';
import {createRequestUtils} from '../../../../../../../../../ionos-wordpress/playwright/e2e/helpers.js'

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
  await requestContext.dispose();

  await admin.visitAdminPage('?page=ionos#tools');

  await expect(true).toBeTruthy();
});
