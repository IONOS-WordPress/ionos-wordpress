import { test, expect } from '@wordpress/e2e-test-utils-playwright';
import { execTestCLI } from '../../../../../../../../playwright/wp-env';

test.describe(
  'essentials:dashboard welcome banner functionality',
  {
    tag: ['@dashboard', '@welcome'],
  },
  () => {
    test.beforeAll(async () => {
      execTestCLI(`wp --quiet user meta delete 1 ionos_essentials_welcome`);
    });

    test('test welcome banner has tenant title', async ({ admin, page }) => {
      // test for default tenant (ionos)
      {
        await admin.visitAdminPage('/');

        const body = await page.locator('body');

        const title = await body.locator('#essentials-welcome_block h2.headline');
        await expect(title).toHaveText(/ionos/i);
      }
      // test for different tenant (strato)
      {
        execTestCLI(`wp --quiet option update ionos_group_brand strato`);

        await admin.visitAdminPage('/');

        const body = await page.locator('body');

        const title = await body.locator('#essentials-welcome_block h2.headline');
        await expect(title).toHaveText(/strato/i);
        execTestCLI(`wp --quiet option update ionos_group_brand ionos`);
      }
    });

    test('test welcome banner', async ({ admin, page }) => {
      await admin.visitAdminPage('/');

      const body = await page.locator('body');

      const dialogBox = await body.locator('#essentials-welcome_block');
      await expect(dialogBox).toHaveCount(1);
      await expect(dialogBox).toHaveAttribute('open');

      await dialogBox.locator('button').click();
      await expect(dialogBox).not.toHaveAttribute('open');
    });

    test('test welcome is still closed', async ({ admin, page }) => {
      await admin.visitAdminPage('/');

      const body = await page.locator('body');

      const dialogBox = await body.locator('#essentials-welcome_block');
      await expect(dialogBox).toHaveCount(0);
    });
  }
);
