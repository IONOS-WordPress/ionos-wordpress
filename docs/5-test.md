# test

run all tests (e2e, react/storybook, phpunit) : `pnpm test`

- `pnpm test` will _by default_ rebuild the project and start wp-env if it is not already running.

  You can skip rebuilding by setting the environment variable `BUILD_UP_TO_DATE=1` : `BUILD_UP_TO_DATE=1 pnpm test`

- `pnpm test` will start `wp-env` for e2e and phpunit tests if not running.

## react/storybook

> react/storybook test are identified by file name convention (`*.spec.jsx`)

Example: packages/wp-plugin/test-plugin/src/feature-1/blocks/block-1/components/tests/MyButton.spec.jsx

- Develop using Storybook : `pnpm storybook:start`

  Featuring hot-reloading, you can develop your components in isolation.

- Generate static storybook artifact which can be uploaded to a web server like GitHub pages : `pnpm storybook:build`

  This will generate a static storybook that can be deployed to a static site hosting service.

- run react tests written in Playwright : `pnpm run test --use react`

  react/storybook tests utilize playwright to test the components in storybook.

  **For frontend testing we can use the same framework - playwright 🙌**

  - Run tests continuously when files change : `pnpm watch -- pnpm run test --use react`

  - vscode supports running tests by clicking on the play button in the test file.

    - same same for debugging tests.

    > [!TIP]
    > vscode requires a single manual step to enable running/debugging single tests from within the ide:
    > Go to the Testing View, click on the gear icon in the Playwright panel and enable all Plawright configurations.
    > See Video for this steps : https://www.youtube.com/watch?v=cYHyOF5j5K8
    >
    > There is a GitHub feature request to make this step unnecessary: https://github.com/microsoft/playwright/issues/34572 - but until then, we have to do this step manually.

## phpunit

> phpunit test are identified by file name convention (`*Test.php`)

Example: packages/wp-plugin/essentials/inc/dashboard/tests/phpunit/AcceptanceTest.php

- run phpunit tests : `pnpm run test --use phpunit`

- (fester) run phpunit tests without rebuilding : `BUILD_UP_TO_DATE=1 pnpm run test --use phpunit`

- (fastest) run phpunit tests without rebuilding and checking wp-env is alive : `pnpm wp-env run tests-wordpress phpunit`

  - run whenever you changed a file : `pnpm watch -- pnpm wp-env run tests-wordpress phpunit`

  - run tests continuously when files change : `pnpm watch -- pnpm run test --use phpunit`

- debug phpunit tests :

  - start wp-env launch configuration in vscode

  - `pnpm wp-env run tests-wordpress phpunit`

- run tests selectively :

  - by test method name : `pnpm wp-env run tests-wordpress phpunit --filter test_dashboard_blocks_registered`

  - by unit test class : `pnpm wp-env run tests-wordpress phpunit --filter AcceptanceTest`

  - by test group : `pnpm wp-env run tests-wordpress phpunit --group essentials`

# e2e tests

> e2e tests are identified by file name convention (`**/tests/e2e/*.spec.js`)

Example: `./packages/wp-plugin/test-plugin/tests/e2e/example.spec.js`

- run e2e tests : `pnpm run test --use e2e`

  - run e2e tests without rebuilding : `BUILD_UP_TO_DATE=1 pnpm run test --use e2e`

  - (fastest) run e2e tests without rebuilding and checking wp-env is alive : `PLAYWRIGHT_SKIP_BROWSER_DOWNLOAD=1 PLAYWRIGHT_CHROME_PATH=$(find ~/.cache/ms-playwright -path "*/chrome-linux/chrome") pnpm exec wp-scripts test-playwright -c ./playwright.config.js`

  - (fastest) run a single e2e test without rebuilding and checking wp-env is alive : `PLAYWRIGHT_SKIP_BROWSER_DOWNLOAD=1 PLAYWRIGHT_CHROME_PATH=$(find ~/.cache/ms-playwright -path "*/chrome-linux/chrome") pnpm exec wp-scripts test-playwright -c ./playwright.config.js ./packages/wp-plugin/test-plugin/tests/e2e/example.spec.js`

- run whenever you changed a file : `pnpm watch -- PLAYWRIGHT_SKIP_BROWSER_DOWNLOAD=1 PLAYWRIGHT_CHROME_PATH=$(find ~/.cache/ms-playwright -path "*/chrome-linux/chrome") pnpm exec wp-scripts test-playwright -c ./playwright.config.js`

- vscode supports running e2e tests by clicking on the play button in the test file.

  - same same for debugging tests.

  > [!TIP]
  > vscode requires a single manual step to enable running/debugging single tests from within the ide:
  > Go to the Testing View, click on the gear icon in the Playwright panel and enable all Plawright configurations.
  > See Video for this steps : https://www.youtube.com/watch?v=cYHyOF5j5K8
  >
  > There is a GitHub feature request to make this step unnecessary: https://github.com/microsoft/playwright/issues/34572 - but until then, we have to do this step manually.

# Linux bare metal testing (without being in devcontainer)

everything works exactly as in devcontainer, but you need to have the requirements installed globally.

## requirements

- matching pnpm version (grep for `PNPM_VERSION` to get current version used in project) installed globally

- playwright dependencies installed globally (see `.devcontainer/Dockerfile`) : `pnpx playwright install --with-deps chromium`

# links

- dozens of useful playwright/wordpress testcases to borrow from :

  https://github.com/WordPress/gutenberg/tree/trunk/test/e2e/specs

  https://github.com/WordPress/wordpress-develop/tree/trunk/tests/e2e/specs

- hundreds of wordpress phpunit tests to borrow from :

  https://github.com/WordPress/wordpress-develop/tree/trunk/tests/phpunit/tests
