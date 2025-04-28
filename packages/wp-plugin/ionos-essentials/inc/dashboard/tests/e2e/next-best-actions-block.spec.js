import { test, expect } from '@wordpress/e2e-test-utils-playwright';
import { execSync } from 'node:child_process';

test.describe('essentials:dashboard next-best-actions block', () => {
  test.beforeAll(async () => {
    await execSync('pnpm -s wp-env run tests-cli wp option delete ionos_nba_status');
    // @FIXME: we need to run the rewrite structure command to avoid 404 error because phpunit will reset rewrite structure for some reason
    await execSync('pnpm -s wp-env run tests-cli wp rewrite structure /%postname% --hard');
    await execSync('pnpm -s wp-env run tests-cli wp user meta update 1 ionos_essentials_welcome true');

    // Install nessesary plugins
    await execSync('pnpm -s wp-env run tests-cli wp plugin install --activate extendify --force');
    await execSync(
      'pnpm -s wp-env run tests-cli wp plugin install --activate https://web-hosting.s3-eu-central-1.ionoscloud.com/extendify/01-ext-ion2hs971.zip --force'
    );

    // Install the extendable theme
    await execSync('pnpm -s wp-env run tests-cli wp theme install --activate extendable --force');
    await execSync('pnpm -s wp-env run tests-cli wp option update extendify_attempted_redirect_count 4');
  });

  test('test dismissing an option ', async ({ admin, page }) => {
    // show dashboard and click on dismiss button of "create-page" action
    await admin.visitAdminPage('/');
    // get the iframe element
    let iframeLocator = await page.locator('iframe');
    // get the iframe's body element
    let iframeBodyLocator = await iframeLocator.contentFrame().locator('body');

    // get dismiss anchor element
    let dismissAncor = await iframeBodyLocator.locator('css=.dismiss-nba[data-nba-id="edit-and-complete"]');
    await expect(dismissAncor).toHaveCount(1);
    await dismissAncor.click();

    // show dashboard and ensure "create-page" action is not more available
    await admin.visitAdminPage('/');
    iframeLocator = await page.locator('iframe');
    iframeBodyLocator = await iframeLocator.contentFrame().locator('body');
    dismissAncor = await iframeBodyLocator.locator('css=.dismiss-nba[data-nba-id="edit-and-complete"]');
    await expect(dismissAncor).toHaveCount(0);
  });

  test('test help center action behavior', async ({ admin, page }) => {
    // redirect to admin page and check if the help center cart exists => expectation is true
    await admin.visitAdminPage('/');
    let iframeLocator = await page.locator('iframe');
    let iframeBodyLocator = await iframeLocator.contentFrame().locator('body');
    let nbaLink = await iframeBodyLocator.locator('css=.nba-link[data-nba-id="help-center"]');
    await expect(nbaLink).toHaveCount(1);

    // click help center cart action and check if the help center flyIn  is open
    let headlessui = await page.locator('#headlessui-portal-root');
    await expect(headlessui).toHaveCount(0);
    await nbaLink.click();
    await expect(headlessui).toHaveCount(1);

    // redirect to admin page and check if the help center cart exists => expectation is false
    await admin.visitAdminPage('/');
    iframeLocator = await page.locator('iframe');
    iframeBodyLocator = await iframeLocator.contentFrame().locator('body');
    nbaLink = await iframeBodyLocator.locator('css=.nba-link[data-nba-id="help-center"]');
    await expect(nbaLink).toHaveCount(0);
  });

  test('test logo upload action behavior', async ({ admin, page }) => {
    // Upload image to the media library
    await execSync(
      'pnpm -s wp-env run tests-cli wp post delete $(pnpm -s run wp-env run tests-cli wp post list --post_type=attachment --format=ids) --force || true'
    );
    await execSync(
      'pnpm -s wp-env run tests-cli wp media import --skip-copy --title="test-logo" wp-content/plugins/ionos-essentials/inc/dashboard/data/tenant-logos/welcome-banner.png '
    );
    // redirect to admin page and check if the help center cart exists => expectation is true
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
