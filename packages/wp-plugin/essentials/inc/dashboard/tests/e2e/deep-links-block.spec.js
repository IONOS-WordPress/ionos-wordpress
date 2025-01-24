import { test, expect } from '@wordpress/e2e-test-utils-playwright';
import { execSync } from 'node:child_process';

test.describe('essentials:dashboard deep-links block ', () => {
  test('contains output for valid tenant', async ({ admin, editor }) => {
    // execSync('pnpm wp-env run tests-cli wp option update ionos_group_brand_name ionos');
    // await admin.visitAdminPage('/post-new.php?post_type=custom_dashboard');
    // await editor.insertBlock({ name: 'ionos-dashboard-page/deep-links' });
    // // const deepLinksBlock = editor.canvas.getByRole('document', {
    // //   name: 'Block: Deep-Links',
    // // });
    // // expect(deepLinksBlock).toHaveCount(1);
    // await editor.insertBlock({ name: 'core/cover' });
    // const coverBlock = editor.canvas.getByRole('document', {
    //   name: 'Block: Cover',
    // });
    // expect(coverBlock).toHaveCount(1);
    // const deepLinksBlock = await editor.canvas.toHaveText(/Deep-Links/);
    // //.locator('css=.wp-block-ionos-dashboard-page-deep-links');
    // expect(deepLinksBlock).toHaveCount(1);
    // div.wp-block-ionos-dashboard-page-deep-links
    //const block_locator = await editor.canvas.locator('.wp-block-ionos-dashboard-page-deep-links');
    // await admin.visitAdminPage('admin.php?page=ionos-essentials-dashboard-hidden-admin-page-iframe&noheader=1');
    // const locator = await page.locator('body');
    // await expect(locator).toHaveText(/Deep-Links/);
  });

  // test('Dashboard contains Deep Links for valid tenant', async ({ requestUtils, admin, page }) => {
  //   execSync('pnpm wp-env run tests-cli wp option update ionos_group_brand_name ionos');
  //   //await page.goto('/?custom_dashboard=ionos');
  //   await admin.visitAdminPage('admin.php?page=ionos-essentials-dashboard-hidden-admin-page-iframe&noheader=1');

  //   const locator = await page.locator('body');
  //   await expect(locator).toHaveText(/Deep-Links/);
  // });

  // test('Dashboard does not contain Deep Links for invalid tenant', async ({ requestUtils, admin, page }) => {
  //   execSync('pnpm wp-env run tests-cli wp option update ionos_group_brand_name invalid_tenant');
  //   // await page.goto('/?custom_dashboard=ionos');
  //   await admin.visitAdminPage('admin.php?page=ionos-essentials-dashboard-hidden-admin-page-iframe&noheader=1');

  //   const locator = await page.locator('body');
  //   await expect(locator).not.toHaveText(/Deep-Links/);
  // });
});
