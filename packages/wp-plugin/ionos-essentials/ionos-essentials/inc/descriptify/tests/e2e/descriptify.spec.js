import { restoreDbOnce, test, expect } from '../../../../../../../../playwright/e2e/fixtures';
test.beforeAll(restoreDbOnce);

test.describe('Descriptify', () => {
  // descriptify only takes over the site url field on the 'de' market, which the AFTER_START
  // script sets before the snapshot is taken.
  test('Changing of homeurl is disabled', async ({ admin, page }) => {
    await admin.visitAdminPage('/options-general.php');

    await expect(page.locator('#siteurl')).toBeDisabled();
    await expect(page.locator('body')).toContainText('You can customize and manage your URL');
  });
});
