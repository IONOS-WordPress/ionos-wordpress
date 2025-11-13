import { test, expect } from '@wordpress/e2e-test-utils-playwright';

test.describe('Descriptify', () => {
  test('Changing of homeurl is disabled', async ({ admin, page }) => {
    await admin.visitAdminPage('/options-general.php');

    await expect(page.locator('#siteurl')).toBeDisabled();
    await expect(page.locator('body')).toContainText('You can customize and manage your URL');
  });
});
