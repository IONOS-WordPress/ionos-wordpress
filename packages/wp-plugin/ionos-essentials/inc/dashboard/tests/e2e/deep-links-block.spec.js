import { test, expect } from '@wordpress/e2e-test-utils-playwright';
import { execSync } from 'node:child_process';

test.describe('essentials:dashboard deep-links block', () => {
  test.beforeAll(async () =>
    execSync(
      'pnpm -s run wp-env run tests-cli wp post delete $(pnpm -s run wp-env run tests-cli wp post list --format=ids --post_status=auto-draft,publish --post_type=ionos_dashboard,wp_global_styles) --force ||:'
    )
  );

  test('contains output for valid tenant "ionos"', async ({ admin, editor }) => {
    execSync('pnpm wp-env run tests-cli wp option update ionos_group_brand ionos');
    await admin.visitAdminPage('/post-new.php?post_type=ionos_dashboard');

    await editor.insertBlock({ name: 'ionos-dashboard-page/deep-links' });

    const deepLinksBlock = await editor.canvas.getByRole('document', {
      name: 'Block: Deep Links',
    });

    await expect(deepLinksBlock).toHaveCount(1);
    await expect(deepLinksBlock.locator('css=h3')).toHaveText(/\w+/);
    await expect(deepLinksBlock.locator('css=.deep-links .wp-block-group .element')).not.toHaveCount(0);
  });

  test('contains no output for invalid tenant', async ({ admin, editor }) => {
    execSync('pnpm wp-env run tests-cli wp option update ionos_group_brand invalid_tenant');
    await admin.visitAdminPage('/post-new.php?post_type=ionos_dashboard');

    await editor.insertBlock({ name: 'ionos-dashboard-page/deep-links' });

    const deepLinksBlock = await editor.canvas.getByRole('document', {
      name: 'Block: Deep Links',
    });

    await expect(deepLinksBlock).toHaveCount(1);
    await expect(deepLinksBlock).toHaveText('Block rendered as empty.');
  });
});
