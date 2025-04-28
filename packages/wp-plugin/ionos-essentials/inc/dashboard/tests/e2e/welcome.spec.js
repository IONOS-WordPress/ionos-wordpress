import { test, expect } from '@wordpress/e2e-test-utils-playwright';
import { execSync } from 'node:child_process';

test.describe(
  'essentials:dashboard next-best-actions block',
  {
    tag: ['@dashboard', '@welcome', '@editor'],
  },
  () => {
    test.beforeAll(async () => {
      try {
        await execSync('pnpm -s wp-env run tests-cli wp rewrite structure /%postname% --hard');
        await execSync('pnpm -s wp-env run tests-cli wp user meta delete 1 ionos_essentials_welcome');
      } catch (error) {}
    });

    test('test welcome banner', async ({ admin, page }) => {
      // show dashboard and click on dismiss button of "create-page" action
      await admin.visitAdminPage('/');
      // get the iframe element
      let iframeLocator = await page.locator('iframe');
      // get the iframe's body element
      let iframeBodyLocator = await iframeLocator.contentFrame().locator('body');

      let dialogBox = await iframeBodyLocator.locator('#essentials-welcome_block');
      await expect(dialogBox).toHaveCount(1);
      await expect(dialogBox).toHaveAttribute('open');

      await dialogBox.locator('button').click();
      await expect(dialogBox).not.toHaveAttribute('open');
    });

    test('test welcome is still closed', async ({ admin, page }) => {
      // show dashboard and click on dismiss button of "create-page" action
      await admin.visitAdminPage('/');

      let iframeLocator = await page.locator('iframe');
      let iframeBodyLocator = await iframeLocator.contentFrame().locator('body');

      let dialogBox = await iframeBodyLocator.locator('#essentials-welcome_block');
      await expect(dialogBox).toHaveCount(0);
    });
  }
);
