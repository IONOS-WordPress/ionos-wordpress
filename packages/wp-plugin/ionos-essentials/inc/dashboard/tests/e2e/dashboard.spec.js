import { test, expect } from '@wordpress/e2e-test-utils-playwright';
import { execSync } from 'node:child_process';
import { execTestCLI } from '../../../../../../../playwright/wp-env.js';


test.describe(
  'essentials:dashboard ionos-essentials-dashboard-admin',
  {
    tag: ['@dashboard'],
  },
  () => {
    test.beforeAll(async () => {
      try {
        execTestCLI(
          `wp user meta set 1 ionos_essentials_welcome true`
        );
        execTestCLI(`
          wp eval 'set_transient("ionos_wpscan_issues", [["name"=>"Essentials","slug"=>"ionos-essentials","type"=>"plugin","score"=>8,"update"=>false,"path"=>"ionos-essentials/ionos-essentials.php"]]);' --skip-plugins --skip-themes
        `);
      } catch (error) {}
    });

    test('/dashboard contains My Account block', async ({ admin, page }) => {
      execSync('pnpm wp-env run tests-cli wp option update ionos_group_brand ionos');
      await admin.visitAdminPage('/');

      const body = await page.locator('body');
      await expect(body).toHaveText(/My Account/);
    });

  }
);
