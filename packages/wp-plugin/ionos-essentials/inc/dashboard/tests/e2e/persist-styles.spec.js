import { test, expect } from '@wordpress/e2e-test-utils-playwright';
import { execSync } from 'node:child_process';
import { writeFileSync, unlinkSync, existsSync, renameSync } from 'node:fs';

test.describe('Dashboard Global Styles', () => {
  const THEME = 'twentytwentyfive';
  const GLOBAL_STYLES_FILE = `packages/wp-plugin/ionos-essentials/inc/dashboard/data/${THEME}-global-styles.json`;

  test.beforeEach(async () => {
    // backup the global styles file
    if (existsSync(GLOBAL_STYLES_FILE)) {
      renameSync(GLOBAL_STYLES_FILE, `${GLOBAL_STYLES_FILE}.bak`);
    }
  });

  test.afterEach(async () => {
    // cleanup: overwrite or delete tmp file
    if (existsSync(`${GLOBAL_STYLES_FILE}.bak`)) {
      renameSync(`${GLOBAL_STYLES_FILE}.bak`, GLOBAL_STYLES_FILE);
    } else {
      unlinkSync(GLOBAL_STYLES_FILE);
    }
  });

  test('Initially load global styles from file', async ({ admin, editor }) => {
    // preparation: remove any global styles posts from database
    execSync(
      'pnpm -s run wp-env run tests-cli wp post delete $(pnpm -s run wp-env run tests-cli wp post list --format=ids --post_type=wp_global_styles) --force ||:'
    );

    const EXPECTED_BACKGROUND_RGB = 'rgb(6, 6, 6)';

    // write tmp global styles
    writeFileSync(GLOBAL_STYLES_FILE, JSON.stringify({ styles: { color: { background: EXPECTED_BACKGROUND_RGB } } }));

    // when entering the site-editor a new global styles post should be created
    // and the global styles should be loaded from the tmp file
    await admin.visitAdminPage('post-new.php?post_type=ionos_dashboard');

    const body = await editor.canvas.locator('body');

    await expect(body).toHaveCSS('background-color', EXPECTED_BACKGROUND_RGB);
  });
});
