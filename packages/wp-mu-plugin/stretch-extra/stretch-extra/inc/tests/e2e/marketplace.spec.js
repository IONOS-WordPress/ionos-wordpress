import { test, expect } from '@wordpress/e2e-test-utils-playwright';
import { execTestCLI } from '../../../../../../../playwright/wp-env';

test.describe(
  'stretch-extra:marketplace',
  {
    tag: ['@stretch-extra', '@marketplace'],
  },
  () => {
    test.beforeAll(async () => {
      execTestCLI(`
        wp option set stretch_extra_extendable_theme_dir_initialized 1
      `);
    });
    test.skip('ionos tab is present', async ({ admin, page }) => {
      await admin.visitAdminPage('/plugin-install.php');
      await expect(page.locator('.plugin-install-ionos')).toHaveCount(1);
      await expect(page.locator('.plugin-card-01-ext-ion8dhas7')).toHaveCount(1);
    });
  }
);
