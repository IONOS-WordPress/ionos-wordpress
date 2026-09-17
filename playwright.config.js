// playwright config for e2e tests against the ephemeral wordpress-alpine test container
/* eslint-disable-next-line import/named */
import { defineConfig, devices } from '@playwright/test';

import baseConfig from '@wordpress/scripts/config/playwright.config';

import { STORAGE_STATE_PATH } from './playwright/e2e/storage-state';

const config = defineConfig({
  ...baseConfig,
  testMatch: ['wp-plugin/**/tests/e2e/*.spec.js', 'wp-mu-plugin/**/tests/e2e/*.spec.js'],
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
    // scripts/test.sh already starts the ephemeral test container before invoking
    // playwright and sets WP_BASE_URL to its published port; reuseExistingServer
    // (inherited from baseConfig) means this command should never actually run - kept
    // as a harmless no-op rather than the inherited wp-env command.
    command: 'true',
  },
  outputDir: './playwright/e2e/.test-results',
  use: {
    ...baseConfig.use,
    // must be the exact file @wordpress/e2e-test-utils-playwright's requestUtils fixture
    // uses, so that the requestUtils.setupRest() calls specs make to restore their login
    // state write where the next test's browser context reads from
    storageState: STORAGE_STATE_PATH,
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
    // under playwright/e2e, not playwright/storybook: the storybook folder belongs to the
    // component-test run (playwright-ct.config.js), which would otherwise overwrite this
    // report - and scripts/_get-workflow-artefacts.sh already collects e2e/.playwright-report.
    ['html', { outputFolder: './playwright/e2e/.playwright-report', open: 'never' }],
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
