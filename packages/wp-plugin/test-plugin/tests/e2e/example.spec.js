import { test, expect } from '@wordpress/e2e-test-utils-playwright';
import { execSync } from 'child_process';

// @see https://pascalbirchler.com/wordpress-performance-testing/ for more e2e test details
// @see https://github.com/WordPress/gutenberg/tree/trunk/packages/e2e-test-utils-playwright
// @see https://github.com/WordPress/gutenberg/issues/38851
// @see https://medium.com/@tetsuaki.hamano/introducing-e2e-testing-to-wordpress-block-development-43efce96a806
// @see https://aki-hamano.blog/en/2023/11/05/block-e2e

test.describe('wp-admin dashboard', () => {
  // test.beforeEach(async ({ requestUtils, admin }) => {
  //   await admin.createNewPost();
  // });

  test('Should load properly', async ({ requestUtils, admin, page }) => {
    // execSync('WP_ENV_HOME=./wp-env-home pnpm exec wp-env run tests-cli wp option update ionos_group_brand_name strato');

    // requestUtils.activateTheme('twentytwentyfive');
    await admin.visitAdminPage('/');
    expect(page.getByRole('heading', { name: 'Welcome to WordPress', level: 2 })).toBeVisible();
  });
});
