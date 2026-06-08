import { test, expect } from '@wordpress/e2e-test-utils-playwright';
import { execTestCLI } from '../../../../../../../../playwright/wp-env';

test.describe(
  'MCP',
  {
    tag: ['@mcp'],
  },
  () => {
    test.beforeAll(async () => {
      execTestCLI('wp plugin delete wordpress-mcp');
      execTestCLI('wp --quiet option delete wordpress_mcp_settings');
      execTestCLI('wp user application-password delete 1 --all');
    });

    test('Get MCP snippet', async ({ admin, page }) => {
      const errors = [];
      page.on('console', (msg) => {
        if (msg.type() === 'error') {
          errors.push(msg.text());
        }
      });

      await admin.visitAdminPage('?page=ionos#tools');

      // Check if we are running in a Native WP7.0 environment based on the UI badge
      const isWP7Native = await page.locator('text=Managed via WP7.0').isVisible();

      // Click the activation switch
      await page.locator('#ionos-essentials-mcp').click();

      if (isWP7Native) {
        // ---- WP 7.0+ Native Flow Expectations ----
        // Code snippet block is hidden, so we don't look for WP_API_PASSWORD
        await expect(page.locator('#ionos-essentials-mcp-info')).getAttribute('style', /display:\s*none/);

        // The plugin shouldn't be installed since WP7 handles it natively
        const pluginListOutput = execTestCLI('wp plugin list --format=json');
        const plugins = JSON.parse(pluginListOutput);
        const mcpPlugin = plugins.find((plugin) => plugin.name === 'wordpress-mcp');
        expect(mcpPlugin).toBeFalsy();
      } else {
        // ---- Pre-WP 7.0 Fallback Flow Expectations ----
        // Ensure snippet contains the connection credential strings
        await expect(page.locator('code')).toHaveText(/WP_API_PASSWORD/);

        // Test if plugin was dynamically installed via WP CLI fallback
        const pluginListOutput = execTestCLI('wp plugin list --format=json');
        const plugins = JSON.parse(pluginListOutput);
        const mcpPlugin = plugins.find((plugin) => plugin.name === 'wordpress-mcp');
        expect(mcpPlugin).toBeTruthy();
        expect(mcpPlugin.status).toBe('active');
      }

      // Make sure there are no console errors
      await expect(errors).toEqual([]);
    });
  }
);
