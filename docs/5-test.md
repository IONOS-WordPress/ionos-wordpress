# test

run all tests (e2e, react/storybook, phpunit) : `pnpm test`

- in case `wp-env` is not running, it will be started automatically.

  `pnpm test` will rebuild the project and start wp-env if it is not already running.

  You can skip rebuilding by setting the environment variable `BUILD_UP_TO_DATE=1` : `BUILD_UP_TO_DATE=1 pnpm test`

`pnpm test` has various configuration options to run only specific tests. Execute `pnpm run test --help` to see all options.

## react/storybook

react/storybook test are identified by file name convention (`*.spec.jsx`).

Example: packages/wp-plugin/test-plugin/src/feature-1/blocks/block-1/components/tests/MyButton.spec.jsx

- Develop using Storybook : `pnpm storybook:start`

  Featuring hot-reloading, you can develop your components in isolation.

- Generate static storybook artifact which can be uploaded to a web server like GitHub pages : `pnpm storybook:build`

  This will generate a static storybook that can be deployed to a static site hosting service.

- run react tests written in Playwright : `pnpm test:react`

  react/storybook tests utilize playwright to test the components in storybook.

  **For frontend testing we can use the same framework - playwright 🙌**

  - Run tests continuously when files change : `pnpm watch -- pnpm test:react`

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

Example: `packages/wp-plugin/essentials/inc/dashboard/tests/phpunit/AcceptanceTest.php`

- run phpunit tests : `pnpm test:php`

- run when ever you changed a file : `pnpm watch -- pnpm test:php`

- debug phpunit tests :

  - start `wp-env` launch configuration in vscode

  - start phpunit tests `pnpm test:php`

# e2e tests

> e2e tests are identified by file name convention (`**/tests/e2e/*.spec.js`)

Example: `./packages/wp-plugin/test-plugin/tests/e2e/example.spec.js`

- run e2e tests : `pnpm test:e2e`

- (fastest) run a single e2e test : `pnpm run test:e2e ./packages/wp-plugin/test-plugin/tests/e2e/example.spec.js`

  or even simpler `pnpm run test:e2e example.spec.js` (paths can be skipped ion Playwright)

- run whenever you changed a file : `pnpm watch -- pnpm test:e2e`

  - run a single e2e test without rebuilding and checking wp-env is alive in playwright debug mode : `pnpm exec playwright test-playwright -c ./playwright.config.js --debug ./packages/wp-plugin/test-plugin/tests/e2e/example.spec.js`

- vscode supports running e2e tests by clicking on the play button in the test file.

  - same same for debugging tests.

  > [!TIP]
  > vscode requires a single manual step to enable running/debugging single tests from within the ide:
  > Go to the Testing View, click on the gear icon in the Playwright panel and enable all Plawright configurations.
  > See Video for this steps : https://www.youtube.com/watch?v=cYHyOF5j5K8
  >
  > There is a GitHub feature request to make this step unnecessary: https://github.com/microsoft/playwright/issues/34572 - but until then, we have to do this step manually.

# Linux bare metal testing (without being in devcontainer)

Everything works exactly as in DevContainer, but you need to have the requirements installed globall :

- matching pnpm version (grep for `PNPM_VERSION` to get current version used in project) installed globally

- playwright dependencies installed globally (see `.devcontainer/Dockerfile`) : `sudo pnpx playwright install-deps`

# links

- dozens of useful playwright/wordpress testcases to borrow from :

  https://github.com/WordPress/gutenberg/tree/trunk/test/e2e/specs

  https://github.com/WordPress/wordpress-develop/tree/trunk/tests/e2e/specs

- hundreds of wordpress phpunit tests to borrow from :

  https://github.com/WordPress/wordpress-develop/tree/trunk/tests/phpunit/tests
