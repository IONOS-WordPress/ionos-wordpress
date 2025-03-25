import { test, expect } from '@wordpress/e2e-test-utils-playwright';
import { execSync } from 'node:child_process';

test.describe('essentials:dashboard ionos-essentials-dashboard-admin', () => {
  test('/admin will contain an iframe referencing our essentials dashboard page', async ({ admin, page }) => {
    await admin.visitAdminPage('/');

    const iframeLocator = await page.locator('iframe');

    await expect(iframeLocator).toHaveCount(1);
    await expect(iframeLocator).toHaveAttribute(
      'src',
      /page=ionos-essentials-dashboard-hidden-admin-page-iframe&noheader=1/
    );
  });

  test('/admin iframe content is rendered using our custom dashboard template', async ({ admin, page }) => {
    // open up /wp-admin/
    await admin.visitAdminPage('/');

    // get the iframe element
    const iframeLocator = await page.locator('iframe');

    // get the iframe's body element
    const iframeBodyLocator = await iframeLocator.contentFrame().locator('body');

    // check if the body element has the class 'custom-dashboard-template'
    // this class name will be derived from our custom dashboard template
    await expect(iframeBodyLocator).toHaveClass(/custom-dashboard-template/);
  });

  test('/admin iframe rendered page contains Deep Links block', async ({ admin, page }) => {
    execSync('pnpm wp-env run tests-cli wp option update ionos_group_brand ionos');
    await admin.visitAdminPage('/');

    // get the iframe element
    const iframeLocator = await page.locator('iframe');

    // get the iframe's body element
    const iframeBodyLocator = await iframeLocator.contentFrame().locator('body');

    // check if the body has the text 'Deep-Links'
    await expect(iframeBodyLocator).toHaveText(/\w+/);
  });
});
