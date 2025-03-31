import { test, expect } from '@wordpress/e2e-test-utils-playwright';

test.describe('essentials:editor get initialized with dashboard content type', () => {
  test('contains output for valid tenant', async ({ admin, page }) => {
    await admin.visitAdminPage('/post-new.php?post_type=ionos_dashboard');

    const body = await page.locator('body');

    // editor get openend for our custom post type with body element having css class "post-type-ionos_dashboard" applied
    await expect(body).toHaveClass(/post-type-ionos_dashboard/);
  });
});
