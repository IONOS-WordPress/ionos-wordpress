import { test, expect, canvas } from '@wordpress/e2e-test-utils-playwright';
import { exec, execSync } from 'node:child_process';
import { writeFileSync, unlinkSync, existsSync, renameSync } from 'node:fs';

test.describe('Dashboard Global Styles', () => {
  test('Initially load global styles from file', async ({ admin, editor }) => {
    // preparation: remove any global styles posts from database
    execSync(
      'pnpm run wp-env tests-cli wp post delete $(pnpm run wp-env tests-cli post list --format=ids --post_type=wp_global_styles) --force'
    );

    const theme = 'twentytwentyfive';
    const globalStylePath = `packages/wp-plugin/essentials/inc/dashboard/data/${theme}-global-styles.json`;
    const background_rgb = 'rgb(6, 6, 6)';
    // backup the global styles file
    if (existsSync(globalStylePath)) {
      renameSync(globalStylePath, `${globalStylePath}.bak`);
    }
    // write tmp global styles
    writeFileSync(globalStylePath, JSON.stringify({ styles: { color: { background: background_rgb } } }));

    // when entering the site-editor a new global styles post should be created
    // and the global styles should be loaded from the tmp file
    await admin.visitAdminPage('site-editor.php');

    const body = editor.canvas.locator('body');
    await expect(body).toHaveCSS('background-color', background_rgb);

    // cleanup: overwrite or delete tmp file
    if (existsSync(`${globalStylePath}.bak`)) {
      renameSync(`${globalStylePath}.bak`, globalStylePath);
    } else {
      unlinkSync(globalStylePath);
    }
  });
});
