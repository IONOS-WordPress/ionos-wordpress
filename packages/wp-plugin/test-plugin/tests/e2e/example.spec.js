import { test, expect } from '@wordpress/e2e-test-utils-playwright';
import { execSync } from 'child_process';

// @see https://pascalbirchler.com/wordpress-performance-testing/ for more e2e test details
// @see https://github.com/WordPress/gutenberg/tree/trunk/packages/e2e-test-utils-playwright
// @see https://github.com/WordPress/gutenberg/issues/38851
// @see https://medium.com/@tetsuaki.hamano/introducing-e2e-testing-to-wordpress-block-development-43efce96a806
// @see https://aki-hamano.blog/en/2023/11/05/block-e2e

test.describe('test-plugin: wp-admin dashboard', () => {
  // test.beforeEach(async ({ requestUtils, admin }) => {
  //   await admin.createNewPost();
  // });

  test('test-plugin: dashboard should load properly', async ({ requestUtils, admin, page }) => {
    // requestUtils.activateTheme('twentytwentyfive');
    await admin.visitAdminPage('/index.php');
    expect(page.getByRole('heading', { name: 'Welcome to WordPress', level: 2 })).toBeVisible();
  });
});
