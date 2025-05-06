import { test, expect } from '@wordpress/e2e-test-utils-playwright';
import { execTestCLI } from '../../../../../../../playwright/wp-env.js';

test.describe('essentials:dashboard next-best-actions block', () => {
  test.beforeAll(async () => {
    // @FIXME: we need to run the rewrite structure command to avoid 404 error because phpunit will reset rewrite structure for some reason
    execTestCLI(`
      wp option delete ionos_nba_status
      wp rewrite structure /%postname% --hard
      wp user meta update 1 ionos_essentials_welcome true
      # install the extendify plugin and site assistant
      wp plugin install --activate extendify --force
      wp plugin install --activate https://web-hosting.s3-eu-central-1.ionoscloud.com/extendify/01-ext-ion2hs971.zip --force
      # install the extendify theme
      wp theme install --activate extendable --force
      # avoid extendify redirect
      wp option update extendify_attempted_redirect_count 4
    `);
  });

  test('test dismissing an option ', async ({ admin, page }) => {
    // show dashboard and click on dismiss button of "create-page" action
    await admin.visitAdminPage('/');
    // get the iframe element
    let iframeLocator = page.locator('iframe');
    // get the iframe's body element
    let iframeBodyLocator = iframeLocator.contentFrame().locator('body');

    // get dismiss anchor element
    let dismissAncor = iframeBodyLocator.locator('css=.dismiss-nba[data-nba-id="create-page"]');
    await expect(dismissAncor).toHaveCount(1);
    await dismissAncor.click();

    // show dashboard and ensure "create-page" action is not more available
    await admin.visitAdminPage('/');
    iframeLocator = page.locator('iframe');
    iframeBodyLocator = iframeLocator.contentFrame().locator('body');
    dismissAncor = iframeBodyLocator.locator('css=.dismiss-nba[data-nba-id="create-page"]');
    await expect(dismissAncor).toHaveCount(0);
  });

  test('test help center action behavior', async ({ admin, page }) => {
    await admin.visitAdminPage('/');
    let iframeLocator = page.locator('iframe');
    let iframeBodyLocator = iframeLocator.contentFrame().locator('body');
    let nbaLink = iframeBodyLocator.locator('css=.nba-link[data-nba-id="help-center"]');
    await expect(nbaLink).toHaveCount(1);

    // click help center cart action and check if the help center flyIn  is open
    let headlessui = page.locator('#headlessui-portal-root');
    await expect(headlessui).toHaveCount(0);
    await nbaLink.click();
    await expect(headlessui).toHaveCount(1);

    // redirect to admin page and check if the help center card exists => expectation is false
    await admin.visitAdminPage('/');
    iframeLocator = page.locator('iframe');
    iframeBodyLocator = iframeLocator.contentFrame().locator('body');
    nbaLink = iframeBodyLocator.locator('css=.nba-link[data-nba-id="help-center"]');
    await expect(nbaLink).toHaveCount(0);
  });

  test('test logo upload action behavior', async ({ admin, page }) => {
    // Upload image to the media library
    // prevent thumbnail generation
    execTestCLI(`
wp option update thumbnail_size_w 0
wp option update thumbnail_size_h 0
wp option update medium_size_w 0
wp option update medium_size_h 0
wp option update medium_large_size_w 0
wp option update medium_large_size_h 0
wp option update large_size_w 0
wp option update large_size_h 0
wp post delete $(wp post list --post_type=attachment --format=ids) --force || true
wp media import --skip-copy --title="test-logo" wp-content/plugins/ionos-essentials/inc/dashboard/data/tenant-logos/welcome-banner.png
    `);

    await admin.visitAdminPage('/');
    let iframeLocator = await page.locator('iframe');
    let iframeBodyLocator = await iframeLocator.contentFrame().locator('body');
    let nbaLink = await iframeBodyLocator.locator('css=.nba-link[data-nba-id="upload-logo"]');
    await expect(nbaLink).toHaveCount(1);

    // click logo upload action and expect redirect to the upload logo page with opened upload overlay
    await nbaLink.click();
    await expect(page).toHaveTitle(/Editor/);
    let uploadLogoOverlay = await page.locator('.media-modal');
    await expect(uploadLogoOverlay).toHaveCount(1);
  });
});
