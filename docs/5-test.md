# test

Run all tests (e2e, react/storybook, phpunit): `pnpm test`

- `pnpm test` starts its own ephemeral test container (independent of the persistent dev container started by `pnpm start`) and rebuilds the project first.

  You can skip rebuilding by setting the environment variable `BUILD_UP_TO_DATE=1`: `BUILD_UP_TO_DATE=1 pnpm test`

`pnpm test` has various configuration options to run only specific tests. Run `pnpm run test --help` to see all options.

## react/storybook

The file name convention (`*.spec.jsx`) identifies react/storybook tests.

Example: packages/wp-plugin/test-plugin/src/feature-1/blocks/block-1/components/tests/MyButton.spec.jsx

- Develop using Storybook: `pnpm storybook:start`

  With hot-reloading, you can develop your components in isolation.

- Generate a static storybook artifact that you can upload to a web server like GitHub pages: `pnpm storybook:build`

  This command generates a static storybook that you can deploy to a static site hosting service.

- Run react tests written in Playwright: `pnpm test:react`

  react/storybook tests use Playwright to test the components in storybook.

  **For frontend testing, we can use the same framework: Playwright.**
  - Run tests continuously when files change: `pnpm watch -- pnpm test:react`

  - You can run tests in vscode by clicking the play button in the test file.
    - The same is true for debugging tests.

    > [!TIP]
    > vscode requires a single manual step to enable running and debugging single tests from within the ide:
    > Go to the Testing View. Click the gear icon in the Playwright panel. Then enable all Playwright configurations.
    > See this video for these steps: https://www.youtube.com/watch?v=cYHyOF5j5K8
    >
    > There is a GitHub feature request to make this step unnecessary: https://github.com/microsoft/playwright/issues/34572. Until this request is fixed, you must do this step manually.

## phpunit

> The file name convention (`*Test.php`) identifies phpunit tests.

Example: `packages/wp-plugin/ionos-essentials/inc/dashboard/tests/phpunit/AcceptanceTest.php`

- Run phpunit tests: `pnpm test:php`

- Run this whenever you change a file: `pnpm watch -- pnpm test:php`

- Debug phpunit tests:
  - Start `pnpm start` to bring up the dev container (Xdebug is enabled by default)

  - Start phpunit tests: `pnpm test:php`

# e2e tests

> The file name convention (`**/tests/e2e/*.spec.js`) identifies e2e tests.

Example: `./packages/wp-plugin/test-plugin/tests/e2e/example.spec.js`

> wp-dev containers have a custom admin password (set in the `.env` variable `WP_PASSWORD`), because the ionos-essentials security feature checks passwords and requires a "safe" password.

- Run e2e tests: `pnpm test:e2e`

- (fastest) Run a single e2e test: `pnpm run test:e2e ./packages/wp-plugin/test-plugin/tests/e2e/example.spec.js`

  or even simpler: `pnpm run test:e2e example.spec.js` (Playwright lets you skip paths)

- Run this whenever you change a file: `pnpm watch -- pnpm test:e2e`
  - Run a single e2e test without rebuilding, in Playwright debug mode: `pnpm run test:e2e --e2e-opts '--debug' ./packages/wp-plugin/test-plugin/tests/e2e/example.spec.js` (see `pnpm run test --help` for more)

- You can run e2e tests in vscode by clicking the play button in the test file.
  - The same is true for debugging tests.

  > [!TIP]
  > vscode requires a single manual step to enable running and debugging single tests from within the ide:
  > Go to the Testing View. Click the gear icon in the Playwright panel. Then enable all Playwright configurations.
  > See this video for these steps: https://www.youtube.com/watch?v=cYHyOF5j5K8
  >
  > There is a GitHub feature request to make this step unnecessary: https://github.com/microsoft/playwright/issues/34572. Until this request is fixed, you must do this step manually.

## Logging out in e2e tests

If you log out within a test, log back in afterward with `await requestUtils.setupRest();`.
See the _maintenance_ test for a real-life example.

## e2e test isolation

The e2e specs set up global WordPress state in `beforeAll` using wp-cli, and several specs
would otherwise contradict each other: `welcome.spec.js` deletes the `ionos_essentials_welcome`
user meta that `tabs`/`maintenance`/`security-options` set, and `secondary-plugin-dir.spec.js`
deactivates the _ionos-essentials_ plugin for its whole duration. For this reason, playwright's
own `workers` setting is pinned to `1` (see `playwright.config.js`) and the whole suite runs
serially against a single throwaway `ionos-wordpress-test` container (see `scripts/test.sh`).

To keep specs from leaking state into one another, the database is snapshotted once - right
after `playwright/e2e/global-setup.js` finishes logging in and resetting theme/plugins - and
restored **once per spec file**, before that file's first test runs. `playwright/exec-test-cli.js`
exports `dumpTestDb()`/`restoreTestDb()` (plain `mariadb-dump`/`mariadb` calls against the test
container), and `playwright/e2e/fixtures.js` exports `restoreDbOnce()`, a plain function - not a
Playwright fixture, since `test.beforeAll` hooks run in a phase that precedes even auto
fixtures. Every spec file therefore starts with:

```js
import { restoreDbOnce, test, expect } from '<path-to>/playwright/e2e/fixtures';
import { execTestCLI } from '<path-to>/playwright/exec-test-cli';

test.beforeAll(restoreDbOnce);
```

`test.beforeAll(restoreDbOnce)` must be the file's outermost `beforeAll`, declared before any
`test.describe(...)` in the file - Playwright runs parent-suite hooks (the file itself) before
child-suite hooks (a `describe` inside it), so this always restores before that file's own
setup runs.

The restore is per **file**, not per **test**: several specs deliberately build up state across
the tests in one file via their own `beforeAll` - e.g. `secondary-theme-dir.spec.js`'s
`deletable` test sets up what its `installable` test asserts on, and `welcome.spec.js`'s dismiss
test sets up what its "still closed" test asserts on. Restoring before every single test would
wipe that out too and break those specs.

A spec calling `requestUtils.setupRest()` (e.g. after deliberately logging out, see below)
rotates the database session token and rewrites the storage-state cookie file - restoring only
the database would leave the next file's browser context holding a cookie the database no
longer recognizes. `global-setup.js` also snapshots the storage-state file
(`playwright/e2e/storage-state.js` holds the shared path constants), and `restoreDbOnce()`
restores both together.

# Linux bare metal testing (without being in devcontainer)

Everything works exactly as in DevContainer, but you must install the requirements globally:

- matching pnpm version (grep for `PNPM_VERSION` to get the current version used in the project) installed globally

- playwright dependencies installed globally (see `.devcontainer/Dockerfile`): `sudo pnpx playwright install-deps`

# testing production

To test the production build:

- Configure the environment variable `TEST_PRODUCTION=true` before running `pnpm test`.

  You can do this locally by adding the environment variable to your `.env.local` file, or inline: `TEST_PRODUCTION=true pnpm run test`.

  `scripts/test.sh` bind-mounts each package's transpiled `dist/` output (instead of its source) into the ephemeral test container for the run. There is no persistent state to clean up afterward.

- Run the test command (this excludes editor tests, which are not available in the production build): `pnpm run test`

# testing a plugin's self-update mechanism end to end

PHPUnit already covers the S3-first/GitHub-fallback resolver logic in isolation (see
`inc/update/tests/phpunit/UpdateTest.php` in `ionos-essentials` and `ionos-core`). To verify the
whole thing against a real, running WordPress instance instead - detection, download, and (for
`ionos-core`) the actual file swap - use a second, isolated `TEST_PRODUCTION` stack rather than
your regular dev container, since a real update installs into whatever directory the plugin is
mounted from.

## set up an isolated stack

Add a throwaway stack config to `.env.local` (gitignored, never touches your regular stack):

```
CONTAINER_NAME=ionos-wordpress-update-test
HTTP_PORT=8899
SSH_PORT=2299
MNT_HOME=./mnt/update-test
```

Then build and start against the production build:

```
pnpm build
TEST_PRODUCTION=true pnpm start
```

## force an update check and drive it

WordPress caches the `update_plugins` transient for ~12h, so clear it first:

```
pnpm cli transient delete update_plugins
pnpm cli plugin list --update=available
```

- **`wp-plugin` packages** (e.g. `ionos-essentials`) go through WordPress core's own
  `Plugin_Upgrader`: `pnpm cli plugin update <slug>`, or click **Update now** in
  `http://localhost:8899/wp-admin` (admin / the `WP_PASSWORD` from `.env`).

  Prerequisite: in the default stack, `stretch-extra` provisions `ionos-essentials` as a custom
  plugin (see the `plugins` entry in
  `packages/wp-mu-plugin/stretch-extra/stretch-extra/inc/stretch-extra-config.php`), and its
  `upgrader_pre_install` filter
  (`packages/wp-mu-plugin/stretch-extra/stretch-extra/inc/secondary-plugin-dir.php:272-288`)
  rejects any install/update attempt for a provisioned slug before `Plugin_Upgrader` downloads
  anything. Remove (or comment out) that `ionos-essentials` entry and rebuild before running this
  check, otherwise it fails at that filter instead of reaching the limitation below.

  Known limitation: `Plugin_Upgrader`'s final step replaces the plugin's own top-level directory,
  which in `TEST_PRODUCTION` mode is itself a bind-mount point - Docker will not let the container
  remove/replace that, so the update always fails at "Removing the old version of the plugin..."
  with "Could not remove the old plugin". This only proves detection and download from the
  resolved URL work; it cannot prove the full install for a `wp-plugin`.

- **`wp-mu-plugin` packages** (e.g. `ionos-core`) have no admin UI and update via a
  `wp_update_plugins` cron hook instead:

  ```
  pnpm cli cron event run wp_update_plugins
  ```

  Our own `MU_Plugin_Upgrader` copies files into the existing mu-plugins directory rather than
  replacing it wholesale, so this does not hit the same limitation and completes end to end.
  Confirm with:

  ```
  pnpm cli eval 'echo get_file_data(WPMU_PLUGIN_DIR . "/ionos-core.php", ["v" => "Version"])["v"];'
  ```

Check `debug.log` for the resolver's own diagnostics either way (which source answered, and why a
source was skipped):

```
docker exec ${CONTAINER_NAME:-ionos-wordpress-update-test} grep -i <plugin-slug> /htdocs/wp-content/debug.log
```

## cleanup

```
pnpm destroy
```

This only removes the isolated stack's own container, volume, and `${MNT_HOME}` overlay - your
regular dev stack (and its `S3_FOLDER`/`CONTAINER_NAME`/etc.) is untouched throughout, since it
never shares any of `.env.local`'s overridden values.

# links

- dozens of useful playwright/wordpress testcases to borrow from:

  https://github.com/WordPress/gutenberg/tree/trunk/test/e2e/specs

  https://github.com/WordPress/wordpress-develop/tree/trunk/tests/e2e/specs

- hundreds of wordpress phpunit tests to borrow from:

  https://github.com/WordPress/wordpress-develop/tree/trunk/tests/phpunit/tests
