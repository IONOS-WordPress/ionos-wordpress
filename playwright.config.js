// playwright config for wp-env based e2e tests
import { defineConfig, devices } from '@playwright/test';

import baseConfig from '@wordpress/scripts/config/playwright.config.js';

// take password from our .env file variable WP_ENV_TEST_ADMIN_PASSWORD
process.env.WP_PASSWORD = process.env.WP_ENV_TEST_ADMIN_PASSWORD;

const config = defineConfig({
  ...baseConfig,
  testMatch: 'wp-plugin/**/tests/e2e/*.spec.js',
  testDir: './packages',
  /* The base directory, relative to the config file, for snapshot files created with toMatchSnapshot and toHaveScreenshot. */
  snapshotDir: './playwright/storybook/__snapshots__',
  /* Maximum time one test can run for. */
  timeout: 30 * 1000,
  /* Run tests in files in parallel */
  fullyParallel: false,
  /* Fail the build on CI if you accidentally left test.only in the source code. */
  forbidOnly: !!process.env.CI,
  /* Retry on CI only */
  retries: process.env.CI ? 2 : 1,
  /* Opt out of parallel tests */
  workers: 1,
  webServer: {
    ...baseConfig.webServer,
    command: 'pnpm start',
  },
  outputDir: './playwright/e2e/.test-results',
  use: {
    ...baseConfig.use,
    storageState: './playwright/e2e/.storage-states/admin.json',
    // @TODO: as of now wp-scripts uses a different version of playwright
    // causing not to use the already downloaded chrome browser of storybook
    // thats why we inject it here manually
    launchOptions: {
      executablePath: process.env.PLAYWRIGHT_CHROME_PATH,
    },
  },
  /* Reporter to use. See https://playwright.dev/docs/test-reporters */
  reporter: [
    process.env.CI ? ['dot'] : ['list', { printSteps: true }],
    ['html', { outputFolder: './playwright/storybook/.playwright-report', open: 'never' }],
    ['line'],
  ],
  globalSetup: './playwright/e2e/global-setup.js',
  projects: [
    {
      name: 'chromium',
      use: {
        ...devices['Desktop Chrome'],
        channel: 'chromium',
      },
    },
  ],
});

export default config;
