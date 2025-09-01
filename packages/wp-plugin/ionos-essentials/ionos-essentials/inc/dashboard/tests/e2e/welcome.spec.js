import { test, expect } from '@wordpress/e2e-test-utils-playwright';
import { execTestCLI } from '../../../../../../../../playwright/wp-env';

test.describe('essentials:dashboard next-best-actions block', () => {
  test.beforeAll(async () => {
    try {
      execTestCLI(`wp user meta delete 1 ionos_essentials_welcome`);
      /* eslint-disable-next-line no-empty */
    } catch {}
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
});
