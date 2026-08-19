# tools

## build

By default, the build process builds packages/{npm,wp-plugin} workspace packages.

The build process only builds packages/{docker} workspace packages if no matching (name,version) docker image exists locally.

### Options:

- `--help` Show this help message and exit
- `--force` will also rebuild all packages/{docker} workspace packages

  even if a matching (name,version) docker image exists locally

- `--verbose` Show verbose output
- `--filter` Filter packages to build by package name.

  Wildcards allowed

  May occur multiple times

  Examples:

  `pnpm build --filter '@ionos-wordpress/ionos-essentials'`
  `pnpm build --filter '_/test_' --filter '\*/essentials'`

- `--use` Specify which operations to use (default: all)

  Currently supported operations:
  - `all` (default) applies all operations
  - `wp-plugin:wp-scripts` bundles wordpress plugins with wp-scripts
  - `wp-plugin:i18n` localizes wordpress plugins
  - `wp-plugin:rector` runs rector on wordpress plugins
  - `wp-plugin:bundle` bundles wordpress plugins into zip archives

  Example usage:

  Run only wp-scripts transpilation and localization on wordpress plugins:
  `pnpm build --use wp-plugin:wp-scripts --use wp-plugin:i18n`

  Run only wp-scripts transpilation and localization on the wordpress plugin essentials:
  `pnpm build --use wp-plugin:wp-scripts --use wp-plugin:i18n --filter '@wordpress-ionos/essentials'`

> You can configure localization using the environment variable `WP_CLI_I18N_LOCALES` (see `.env`).

## changeset

see https://github.com/changesets/changesets

`pnpm changeset` creates a changeset file.

We use changesets to version packages and maintain CHANGELOG files.

### create a new changeset

Create a new changeset file: `pnpm changeset add`

Create a new empty changeset file: `pnpm changeset add --empty`

### create a new version

Update package versions and apply changes to CHANGELOG files: `pnpm changeset version`

# clean

`pnpm clean` cleans up any build artifacts (for example, the `dist`/`build` folder) and temporary files.

`pnpm clean`

> You can control the cleanup process by configuring the `GIT_CLEAN_OPTS` environment variable (see `.env`).

# distclean

`pnpm distclean` cleans up any build artifacts (for example, the `dist`/`build` folder) and temporary files.

`pnpm distclean` also removes all `node_modules` folders, and all docker containers and images it created.

> You can control the cleanup process by configuring the `GIT_CLEAN_OPTS` environment variable (see `.env`).

# purge-registry

Deletes the container packages this repository publishes to `ghcr.io` — the dev container image plus one image per `packages/docker/*` workspace package — including every version they hold : `pnpm purge-registry`

The tool deletes whole packages rather than individual versions. This is the only way to clear the legacy dev container packages that carried their timestamp in the package _name_ instead of the tag (one package per change). It never touches packages published by other repositories in the organization. It lists these as `skip`.

> [!CAUTION]
> This is irreversible — GitHub cannot restore a deleted package version. `pnpm purge-registry` therefore only _reports_ what it would delete. Add `--yes` to actually delete. The next CI run rebuilds and republishes whatever it needs, so the cost is build time — but never run it while a release is in flight.

`pnpm purge-registry` requires `GH_TOKEN` in `.secrets` (see `.secrets.example`) — a classic personal access token carrying the `read:packages` and `delete:packages` scopes. Fine-grained tokens cannot delete container packages.

# destroy

`pnpm destroy` removes the persistent `wordpress-alpine` dev container and its per-stack overlay data (the shared, version-keyed WordPress core cache survives).

> A container's env vars and bind mounts are fixed when it is created, so changes to them only take effect after a `pnpm destroy`. `WORDPRESS_VERSION` is the exception : `pnpm start` compares it against the existing container and recreates the container itself when they differ.

# cli

`pnpm cli` runs a `wp-cli` command inside the dev container, as the `php` user.

Example usage : `pnpm cli plugin list`

# enter

`pnpm enter` opens an interactive shell in the dev container, as the `php` user.

# logs

`pnpm logs` tails the dev container's logs (Apache/MariaDB/debug.log, all forwarded to stdout).

# gh-cli (advanced)

see https://cli.github.com/manual/

`pnpm gh-cli` allows you to control almost any aspect of the github repository (releases, issues, pull requests, etc.)

# lint

`pnpm lint` will lint all packages.

By default, `pnpm lint` lints every source file.

## options:

- `--help` Show this help message and exit

- `--fix` Apply lint fixes where possible

- `--use` Specify which linters to use (default: all)

  Available options:
  - `all` operate on all files
  - `php` operate on php files
  - `prettier` operate html/yml/md/etc. files
  - `js` operate on js/jsx files
  - `css` operate on css/scss files
  - `pnpm` operate on pnpm lock file
  - `i18n` operate on po/pot files

  Example usage:

  Lint all files matching prettier and i18n. Skip php files, etc.:
  `pnpm lint --use prettier -use i18n`

# playground

`pnpm playground` will start a local WordPress playground (https://wordpress.org/playground/).

WordPress playground is a local WordPress environment with a pre-installed WordPress instance. It also includes a set of plugins and themes, and it runs completely in the browser.

# release (advanced)

`pnpm release` will create a new release.

It runs a series of commands, such as `pnpm changeset version` and `pnpm build`. It also creates GIT tags, pushes them to GIT, and creates a new release on GitHub.

> You can run this command locally. By default, it also runs whenever someone pushes code to the `main` branch.

# start

`pnpm start` starts the persistent `wordpress-alpine` dev container. It builds the Docker image first if needed.

Xdebug is part of the `wordpress-alpine` image and is enabled by default. It attaches on _every_ request (`xdebug.start_with_request=yes`) and connects back to the IDE on `host.docker.internal:9003`. As a result, you only need to start the generated `ionos-wordpress` launch configuration in vscode to hit breakpoints.

# stop

`pnpm stop` will stop the `wordpress-alpine` dev container.

# storybook

see https://storybook.js.org/

`pnpm storybook` will start storybook.

You can use Storybook to create stories and tests for React components. You can also use it to document components.

> You can debug Storybooks easily using `vscode`.

# test

`pnpm test` runs tests for all packages.

> This action starts its own ephemeral test container, independent of the persistent dev container started by `pnpm start`.

## Options

- `--help` Show this help message and exit

- `--use` Specify which tests to execute (default: all)

  Available options:
  - `php` execute PHPUnit tests
  - `e2e` execute E2E tests
  - `react` execute Storybook/React tests

  You can use this option multiple times to specify multiple tests.

  Example usage:

  Execute only PHPUnit and E2E tests:
  `pnpm run test --use e2e --use php`

  Execute PHPUnit tests and provide additional args to PHPUnit :
  `pnpm test --use php -- --filter test_my_test_method`

  `pnpm test --use php -- --filter MyTestClass`

  `pnpm run test --use php -- --group foo`

# update-dependencies

`pnpm update-dependencies`

Check for updates of

- package dependencies
- nodejs version
- pnpm version
- docker version
- updates in workspace packages of the 'docker' flavor

## Options

- `--help` Show this help message and exit

- `--pnpm-opts <pnpm-opts>` Pass additional options to pnpm command

  Example usage :

  update package dependencies
  `pnpm update-dependencies`

  update package dependencies to latest version
  `pnpm update-dependencies --pnpm-opts '--latest'`
  will apply all

# watch

`pnpm watch` will watch for changes in the workspace and execute a command whenever a change is detected.

Example usage:

`pnpm watch -- pnpm build --use wp-plugin:wp-scripts --filter 'essentials'`
