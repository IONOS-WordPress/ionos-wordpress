# tools

## build

By default packages/{npm,wp-plugin} package workspace packages will be build.

packages/{docker} workspace packages will only be build if no matching (name,version) docker image exists locally

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

  - `all` (default) apply all operations
  - `wp-plugin:wp-scripts` do wp-scripts bundling on wordpress plugins
  - `wp-plugin:i18n` do localization operations on wordpress plugins
  - `wp-plugin:rector` execute rector on wordpress plugins
  - `wp-plugin:bundle` bundle wordpress plugins to zip archives

  Example usage :

  do only wp-scripts transpilation and localization on wordpress plugins:
  `pnpm build --use wp-plugin:wp-scripts --use wp-plugin:i18n`

  do only wp-scripts transpilation and localization on wordpress plugin essentials
  `pnpm build --use wp-plugin:wp-scripts --use wp-plugin:i18n --filter '@wordpress-ionos/essentials'`

> Localization can be configured using environment variable `WP_CLI_I18N_LOCALES` (see `.env`).

## changeset

see https://github.com/changesets/changesets

will create a changeset file.

changesets are used to version packages and for maintaining CHANGELOG files.

### create a new changeset

create a new changeset file : `pnpm changeset add`

create a new empty changeset file : `pnpm changeset add --empty`

### create a new version

update package versions and apply changestes to CHANGELOG files: `pnpm changeset version`

# clean

Will cleanup any build artifacts (`dist`/`build` folder for example) and temporary files.

`pnpm clean`

> You can control the cleanup process by configuring the `GIT_CLEAN_OPTS` environment variable (see `.env`).

# distclean

Will cleanup any build artifacts (`dist`/`build` folder for example) and temporary files.

`pnpm distclean` will also remove all `node_modules` folders, docker containers and images created.

> You can control the cleanup process by configuring the `GIT_CLEAN_OPTS` environment variable (see `.env`).

# destroy

Will destroy the started `wp-env` instance : `pnpm destroy`

# gh-cli (advanced)

see https://cli.github.com/manual/

`pnpm gh-cli` allows you to control almost any aspect of the github repository (releases, issues, pull requests, etc.)

# go-waas

`pnpm go-waas` will execute `go-waas` command.

> `go-waas` is not part of the repository. It will be downloaded on demand (=> VPN needs to be enabled in this case)

# lint

`pnpm lint` will lint all packages.

By default every source file will be linted.

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

  Example usage :

  lint all files matching prettier and i18n, skip php files etc.
  `pnpm lint --use prettier -use i18n`

# playground

`pnpm playground` will start a local WordPress playground (https://wordpress.org/playground/).

WordPress playground is a local WordPress environment with a pre-installed WordPress instance and a set of plugins and themes completely running in the browser.

# release (advanced)

`pnpm release` will create a new release.

It will run a bunch of commands like `pnpm changeset version`, `pnpm build`, creates GIT tags, pushes to GIT and creates a new release on GitHub.

> this command can be executed locally but is executed by default whenever code gets pushed to the `main` branch.

# start

`pnpm start` will start `wp-env`.

The command will create the matching configuration file for `wp-env` and `vscode` to allow debugging PHP.

> The command can be individually tuned using environment variable `WP_ENV_START_OPTS` (see .env.local.example).

# stop

`pnpm stop` will stop `wp-env`.

# storybook

see https://storybook.js.org/

`pnpm storybook` will start storybook.

Storybook can be used to create stories and tests for React components. It is also used to document components.

> Storybooks can be easily debugged using `vscode`.

# test

will run tests for all packages.

> This action will start wp-env if it is not already running.

## Options

- `--help` Show this help message and exit

- `--use` Specify which tests to execute (default: all)

  Available options:

  - `php` execute PHPUnit tests
  - `e2e` execute E2E tests
  - `react` execute Storybook/React tests

  This option can be used multiple times to specify multiple tests.

  Example usage :

  Execute only PHPUnit and E2e tests:
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
- updates in workspace packages of the 'docker' flavour

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

# wp-env

This command is a wrapper around the `wp-env` command.

It allows you to call any `wp-env` sub command.

Examples:

- show all commands: `pnpm wp-env run --help`

- open bash in wordpress container: `pnpm wp-env run wordpress bash`

- open bash in wp-cli container: `pnpm wp-env run cli bash`

- execute wp-cli command directly: `pnpm wp-env run cli wp post list`

- enter mysql shell : `pnpm wp-env run mysql mariadb --user=root --password=password wordpress`
