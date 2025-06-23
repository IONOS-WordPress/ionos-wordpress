import { test, expect } from '@wordpress/e2e-test-utils-playwright';
import { execTestCLI } from '../../../../../../../playwright/wp-env.js';

test.describe('essentials:dashboard next-best-actions block', () => {
  test.beforeAll(async () => {
    execTestCLI(`
      wp option delete ionos_nba_status
      wp user meta update 1 ionos_essentials_welcome true
      wp option update extendify_attempted_redirect_count 4
    `);
  });

  test('test dismissing an option ', async ({ admin, page }) => {
    await admin.visitAdminPage('/');
    const body = page.locator('body');

    const dismissAnchor = body.locator('.ionos-dismiss-nba[data-nba-id="create-page"]');
    await expect(dismissAnchor).toHaveCount(1);
    await dismissAnchor.click();

    // show dashboard and ensure "create-page" action is not more available
    await admin.visitAdminPage('/');
    body = page.locator('body');
    dismissAnchor = body.locator('.ionos-dismiss-nba[data-nba-id="create-page"]');
    await expect(dismissAnchor).toHaveCount(0);
  });
});
