import { restoreDbOnce, test, expect } from '../../../../../../../playwright/e2e/fixtures';
import { execTestCLI } from '../../../../../../../playwright/exec-test-cli';
test.beforeAll(restoreDbOnce);

test.describe(
  'stretch-extra:secondary-plugin-dir functionality',
  {
    tag: ['@stretch-extra', '@secondary-plugin-dir'],
  },
  () => {
    // no afterAll: the theme this file activates, the plugin it deactivates and the options it
    // changes are all database state, which the next spec file's restoreDbOnce brings back.
    test.beforeAll(async () => {
      execTestCLI(`
        # deactivate the *mounted* ionos-essentials. Addressed by plugin file rather than via
        # 'wp plugin deactivate ionos-essentials': both the mounted plugin and the stretch-extra
        # provisioned copy answer to that slug, and wp-cli picks the provisioned one - it reports
        # success while leaving the mounted plugin in active_plugins. Having both active at once
        # is a hard fatal (Cannot redeclare the essentials namespace's _is_plugin_active()), and
        # below activates the provisioned one.
        wp eval 'deactivate_plugins("ionos-essentials/ionos-essentials.php");'
        # prevent auto initialization of stretch-extra provisioned plugins
        wp --quiet option update IONOS_CUSTOM_ACTIVE_PLUGINS_OPTION '[]' --format=json
        # un-hide the provisioned ionos-essentials plugin: the AFTER_START script lists it (and
        # beyond-seo) as deleted, exactly so the dev site never runs the provisioned copy next to
        # the mounted one, but the tests below drive that very plugin's row on plugins.php
        wp --quiet option update IONOS_CUSTOM_DELETED_PLUGINS_OPTION '[]' --format=json
        # allow re-initialization of the extendable theme dir - the snapshot already has this
        # set, since the first wp call (requestUtils.activateTheme('twentytwentyfive') in
        # playwright/e2e/global-setup.js) initializes it. in a real world scenario this would
        # only be needed once.
        wp option delete stretch_extra_extendable_theme_dir_initialized
      `);
    });

    test('current theme is stretch-extra provisioned extendable theme', async ({ admin, page }) => {
      await admin.visitAdminPage('/themes.php');

      // current theme should be extendable
      const eActiveThemeCard = await page.locator('div.theme.active');

      // test if the active theme has [data-slug="extendable"] attribute
      await expect(eActiveThemeCard, 'current theme should be extendable').toHaveAttribute('data-slug', 'extendable');
    });

    test('at least a single stretch-extra provisioned plugin is installed', async ({ admin, page }) => {
      await admin.visitAdminPage('/plugins.php');

      // Find all plugin rows where data-plugin starts with "plugins/" (=> custom plugins)
      await expect
        .poll(async () => await page.locator('tr[data-slug][data-plugin^="plugins/"]').count())
        .toBeGreaterThan(0);
      // .toBe(3);
    });

    test('test activation / deactivation of plugins', async ({ admin, page }) => {
      const performanceLabPluginSlug = 'performance-lab';

      execTestCLI(`
        wp plugin install ${performanceLabPluginSlug} ||:
      `);

      const performanceLabRowSelector = `tr[data-slug="${performanceLabPluginSlug}"]`;
      const customEssentialsRowSelector = 'tr[data-plugin="plugins/ionos-essentials/ionos-essentials.php"]';

      try {
        await admin.visitAdminPage('/plugins.php');

        // test activation for regular plugins
        {
          // Click the activate link for the Performance Lab plugin
          const activateLink = page.locator(`${performanceLabRowSelector} .activate a`);
          await activateLink.click();

          // Wait for activation to complete
          await expect(page.locator(`${performanceLabRowSelector}.active`)).toBeVisible();
        }

        // test activation for custom plugins
        {
          // Click the activate link for the custom IONOS Essentials plugin
          const activateLink = page.locator(`${customEssentialsRowSelector} .activate a`);
          await activateLink.click();

          // Wait for activation to complete
          await expect(page.locator(`${customEssentialsRowSelector}.active`)).toBeVisible();
        }

        // test deactivation for regular plugins
        {
          // Click the deactivate link for the Performance Lab plugin
          const deactivateLink = page.locator(`${performanceLabRowSelector} .deactivate a`);
          await deactivateLink.click();

          // Wait for deactivation to complete
          await expect(page.locator(`${performanceLabRowSelector}:not(.active)`)).toBeVisible();
        }

        // test deactivation for custom plugins
        {
          // Click the deactivate link for the custom IONOS Essentials plugin
          const deactivateLink = page.locator(`${customEssentialsRowSelector} .deactivate a`);
          await deactivateLink.click();

          // Wait for deactivation to complete
          await expect(page.locator(`${customEssentialsRowSelector}:not(.active)`)).toBeVisible();
        }

        // test bulk activation / deactivation works for custom plugins and regular plugins
        {
          // Check the checkbox for the Performance Lab plugin
          const performanceLabCheckbox = page.locator(`${performanceLabRowSelector} input[type="checkbox"]`);
          await performanceLabCheckbox.check();
          await expect(performanceLabCheckbox).toBeChecked();

          // Check the checkbox for the custom IONOS Essentials plugin
          const customEssentialsCheckbox = page.locator(`${customEssentialsRowSelector} input[type="checkbox"]`);
          await customEssentialsCheckbox.check();
          await expect(customEssentialsCheckbox).toBeChecked();

          // Select "Activate" from the bulk actions dropdown
          const bulkActionsSelect = page.locator('select[name="action"]');
          await bulkActionsSelect.selectOption('activate-selected');

          // Click the "Apply" button
          const applyButton = page.locator('input#doaction');
          await applyButton.click();

          // Wait for both plugins to be activated
          await expect(page.locator(`${performanceLabRowSelector}.active`)).toBeVisible();
          await expect(page.locator(`${customEssentialsRowSelector}.active`)).toBeVisible();

          // Now deactivate both plugins using bulk actions

          // Check the checkbox for the Performance Lab plugin
          await performanceLabCheckbox.check();
          await expect(performanceLabCheckbox).toBeChecked();

          // Check the checkbox for the custom IONOS Essentials plugin
          await customEssentialsCheckbox.check();
          await expect(customEssentialsCheckbox).toBeChecked();

          // Select "Deactivate" from the bulk actions dropdown
          await bulkActionsSelect.selectOption('deactivate-selected');

          // Click the "Apply" button
          await applyButton.click();

          // Wait for both plugins to be deactivated
          await expect(page.locator(`${performanceLabRowSelector}:not(.active)`)).toBeVisible();
          await expect(page.locator(`${customEssentialsRowSelector}:not(.active)`)).toBeVisible();
        }
      } finally {
        execTestCLI(`
          wp plugin uninstall ${performanceLabPluginSlug} --deactivate ||:
        `);
      }
    });

    test('test deletion / re installation of plugins', async ({ admin, page }) => {
      const extendifyPluginSlug = 'extendify';

      const customExtendifyPluginRowSelector = `tr[data-plugin="plugins/${extendifyPluginSlug}/${extendifyPluginSlug}.php"]`;

      // try installing extendify plugin using the wp-admin interface
      await admin.visitAdminPage('/plugin-install.php?s=extendify&tab=search&type=term');

      // install button should no be there for the extendify plugin since it's already installed as custom plugin
      const installPluginButton = page.locator(`.plugin-card.plugin-card-extendify a.install-now`);
      await expect(
        installPluginButton,
        'install button should not be present for already installed custom plugin'
      ).toHaveCount(0);

      const activatePluginButton = page.locator(`.plugin-card.plugin-card-extendify a.activate-now`);
      await expect(
        activatePluginButton,
        'activate button should be present for already installed custom plugin'
      ).toHaveCount(1);

      // click the activate button to activate the plugin
      await activatePluginButton.click();

      // wait for the plugin to be activated
      await expect(page.locator(`${customExtendifyPluginRowSelector}.active`)).toBeVisible();

      // now delete the plugin using the wp-admin interface
      await admin.visitAdminPage('/plugins.php');

      // deactivate the plugin first if it's active
      const deactivateLink = page.locator(`${customExtendifyPluginRowSelector} .deactivate a`);
      await deactivateLink.click();

      // wait for deactivation to complete
      await expect(page.locator(`${customExtendifyPluginRowSelector}:not(.active)`)).toBeVisible();

      // append confirmation listener to the deletion in the dialog
      page.on('dialog', async (dialog) => {
        expect(dialog.type()).toBe('confirm');
        await dialog.accept();
      });

      const deleteLink = page.locator(`${customExtendifyPluginRowSelector} .delete a`);
      await deleteLink.click();

      // wait for the plugin row to be removed from the list
      await expect(page.locator(`${customExtendifyPluginRowSelector}.deleted`)).toHaveCount(1);

      // now try reinstalling the plugin using wp admin interface
      await admin.visitAdminPage('/plugin-install.php?s=extendify&tab=search&type=term');

      const reinstallPluginButton = page.locator(`.plugin-card.plugin-card-extendify a.install-now`);
      await expect(reinstallPluginButton, 'reinstall button should be present for deleted custom plugin').toHaveCount(
        1
      );

      // click the reinstall button
      await reinstallPluginButton.click();

      // wait for the plugin to be reinstalled
      await expect(page.locator(`.plugin-card.plugin-card-extendify a.activate-now`)).toBeVisible();

      // check the plugin page to have the plugin listed as installed
      await admin.visitAdminPage('/plugins.php');
      await expect(page.locator(`${customExtendifyPluginRowSelector}.inactive`)).toBeVisible();
    });
  }
);
