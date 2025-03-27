import { test, expect } from '@wordpress/e2e-test-utils-playwright';
import { execSync } from 'node:child_process';

test.describe('essentials:dashboard next-best-actions block', () => {
  test.beforeAll(async () => {
    await execSync('pnpm -s wp-env run tests-cli wp option delete ionos_nba_status');
    // @FIXME: we need to run the rewrite structure command to avoid 404 error because phpunit will reset rewrite structure for some reason
    await execSync('pnpm -s wp-env run tests-cli wp rewrite structure /%postname% --hard');
  });

  test('test dismissing an option ', async ({ admin, page }) => {
    // show dashboard and click on dismiss button of "create-page" action
    await admin.visitAdminPage('/');

    // get the iframe element
    let iframeLocator = await page.locator('iframe');
    // get the iframe's body element
    let iframeBodyLocator = await iframeLocator.contentFrame().locator('body');
    // get dismiss anchor element
    let dismissAncor = await iframeBodyLocator.locator('css=.dismiss-nba[data-nba-id="create-page"]');
    await expect(dismissAncor).toHaveCount(1);
    await dismissAncor.click();

    // show dashboard and ensure "create-page" action is not more available
    await admin.visitAdminPage('/');
    iframeLocator = await page.locator('iframe');
    iframeBodyLocator = await iframeLocator.contentFrame().locator('body');
    dismissAncor = await iframeBodyLocator.locator('css=.dismiss-nba[data-nba-id="create-page"]');
    await expect(dismissAncor).toHaveCount(0);
  });
});
