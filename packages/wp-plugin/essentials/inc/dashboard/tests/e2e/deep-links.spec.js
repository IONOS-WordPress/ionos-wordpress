import { test, expect } from '@wordpress/e2e-test-utils-playwright';
import { execSync } from 'node:child_process';

test.describe('Deep Links in Dashboard', () => {
  test('Dashboard contains Deep Links for valid tenant', async ({ requestUtils, admin, page }) => {
    execSync('WP_ENV_HOME=./wp-env-home pnpm exec wp-env run tests-cli wp option update ionos_group_brand_name ionos');
    await page.goto('/?custom_dashboard=ionos');

    const locator = await page.locator('body');
    await expect(locator).toHaveText(/Deep-Links/);
  });

  test('Dashboard does not contain Deep Links for invalid tenant', async ({ requestUtils, admin, page }) => {
    execSync(
      'WP_ENV_HOME=./wp-env-home pnpm exec wp-env run tests-cli wp option update ionos_group_brand_name invalid_tenant'
    );
    await page.goto('/?custom_dashboard=ionos');

    const locator = await page.locator('body');
    await expect(locator).not.toHaveText(/Deep-Links/);
  });
});
