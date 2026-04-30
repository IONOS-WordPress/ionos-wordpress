import { test, expect } from '@wordpress/e2e-test-utils-playwright';
import { execTestCLI } from '../../../../../../../../playwright/wp-env';

test.describe(
  'Switch page',
  {
    tag: ['@dashboard', '@switchpage'],
  },
  () => {
    test('showing correct links', async ({ admin, page }) => {
      const errors = [];
      page.on('console', (msg) => {
        if (msg.type() === 'error') {
          errors.push(msg.text());
        }
      });

      await admin.visitAdminPage('/admin.php?page=ionos-onboarding');

      await expect(page.locator('a[href*="extendify-launch"]')).toBeVisible();
      await expect(page.getByRole('link', { name: 'Create manually' })).toBeVisible();
      // .toHaveLength(0) is best practice according to playwright docs
      await expect(errors).toHaveLength(0);
    });

    test.describe('onboarding redirect', () => {
      test.beforeEach(async () => {
        execTestCLI(`wp --quiet option delete IONOS_ESSENTIALS_ONBOARDING`);
      });

      test('wp-admin/ redirects to switch page when onboarding not done', async ({ admin, page }) => {
        await admin.visitAdminPage('/');
        await expect(page).toHaveURL(/page=.*-onboarding/);
      });

      test('IONOS dashboard redirects to switch page when onboarding not done', async ({ admin, page }) => {
        await admin.visitAdminPage('/admin.php?page=ionos');
        await expect(page).toHaveURL(/page=.*-onboarding/);
      });

      test('"Create manually" button includes ionos_onboarding=diy query arg', async ({ admin, page }) => {
        await admin.visitAdminPage('/admin.php?page=ionos-onboarding');
        const link = page.getByRole('link', { name: 'Create manually' });
        await expect(link).toHaveAttribute('href', /ionos_onboarding=diy/);
      });

      test('"Continue with AI" button includes ionos_onboarding=ai query arg', async ({ admin, page }) => {
        await admin.visitAdminPage('/admin.php?page=ionos-onboarding');
        const link = page.locator('a[href*="extendify-launch"]');
        await expect(link).toHaveAttribute('href', /ionos_onboarding=ai/);
      });

      test('clicking "Create manually" persists diy choice and lands on IONOS dashboard', async ({ admin, page }) => {
        await admin.visitAdminPage('/admin.php?page=ionos-onboarding');
        await page.getByRole('link', { name: 'Create manually' }).click();
        await expect(page).toHaveURL(/page=ionos/);
        await expect(page).not.toHaveURL(/page=.*-onboarding/);
      });

      test('after onboarding, wp-admin/ no longer redirects to switch page', async ({ admin, page }) => {
        execTestCLI(`wp --quiet option update IONOS_ESSENTIALS_ONBOARDING diy`);
        await admin.visitAdminPage('/');
        await expect(page).not.toHaveURL(/page=.*-onboarding/);
      });
    });
  }
);
