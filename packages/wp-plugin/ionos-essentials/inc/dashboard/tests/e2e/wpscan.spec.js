import { test, expect } from '@wordpress/e2e-test-utils-playwright';
import { execTestCLI } from '../../../../../../../playwright/wp-env.js';

test.describe('options', () => {
  test.beforeAll(async () => {
    execTestCLI(`
      wp eval 'set_transient("ionos_wpscan_issues", [["name"=>"Essentials","slug"=>"ionos-essentials","type"=>"plugin","score"=>8,"update"=>false,"path"=>"ionos-essentials/ionos-essentials.php"]]);' --skip-plugins --skip-themes
    `);
  });

  test('expect issues to be displayed', async ({ admin, page }) => {
    await admin.visitAdminPage('?page=ionos#tools');
    const body = page.locator('body');

    await expect(body).toContainText('1 critical issue');
    await expect(page.getByRole('button', { name: 'Delete' })).toBeVisible();
  });

  test('is there an admin notice', async ({ admin, page }) => {
    await admin.visitAdminPage('edit.php');
    const body = page.locator('body');

    await expect(body).toContainText('Vulnerability scan');
  });

  test('is there a notice in plugin overview', async ({ admin, page }) => {
    await admin.visitAdminPage('plugins.php');
    const body = page.locator('body');

    await expect(body).toContainText('The vulnerability scan has found issues for Essentials.');
  });
});
