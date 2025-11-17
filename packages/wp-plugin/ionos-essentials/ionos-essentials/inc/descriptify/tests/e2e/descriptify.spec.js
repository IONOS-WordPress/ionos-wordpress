import { test, expect } from '@wordpress/e2e-test-utils-playwright';
import { execTestCLI } from '../../../../../../../../playwright/wp-env';

test.describe('Descriptify', () => {
  test('Changing of homeurl is disabled', async ({ admin, page }) => {
    execTestCLI(`wp option update ionos_market de`);
    await admin.visitAdminPage('/options-general.php');

    await expect(page.locator('#siteurl')).toBeDisabled();
    await expect(page.locator('body')).toContainText('You can customize and manage your URL');
  });
});
