import { test, expect } from '@wordpress/e2e-test-utils-playwright';

test.describe('Tabs', () => {

  test('user can switch between tabs', async ({ admin, page }) => {
    await admin.visitAdminPage('/');

    const body = await page.locator('body');
    const tab1 = body.locator('#overview');
    const tab2 = body.locator('#tools');

    await expect(tab1).toBeVisible();
    await expect(tab2).not.toBeVisible();

    await page.getByRole('button', { name: 'Werkzeuge' }).click();

    await expect(tab1).not.toBeVisible();
    await expect(tab2).toBeVisible();
  });


});
