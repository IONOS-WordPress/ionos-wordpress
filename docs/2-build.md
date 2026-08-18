# Folder structure

- Workspace packages are in folders based on their _flavor_.

  ```
  ./packages
  ├── docker        # docker packages
  ├── npm           # npm packages
  └── wp-plugin     # WordPress plugins
  ```

  > Organizing packages by _flavor_ makes it easy to decide how to build the workspace package.

- A workspace package contains just the code and a `package.json`. Use the `package.json` to
  - declare workspace dependencies

  - manage semantic versioning

  - (optional) customize individual scripts

- WordPress plugins are in `./packages/wp-plugin`. Each plugin has the following structure:

  ```
  ./packages/wp-plugin/my-plugin
  ├── inc                             # php code
  │   └── php-only-feature-1          # plugin feature only php
  ├── languages                       # localization ressorces
  ├── src                             # js/css code
  │   ├── feature-1                   # plugin feature consisting of js/css/php
  │   │   └── blocks                  # gutenberg blocks
  │   │       ├── block-1
  │   │       │   └── components      # react components
  │   │       │       ├── stories     # component storybook stories
  │   │       │       └── tests       # component playwright tests
  │   │       └── block-2
  │   └── feature-2
  │       ├── backend
  │       └── frontend
  └── tests                           # integration tests for plugins
      └── phpunit                     # phpunit tests
  ```

  > The directory structure of a WordPress plugin follows the [wp-scripts](https://developer.wordpress.org/block-editor/reference-guides/packages/packages-scripts/) conventions.

  See [`packages/wp-plugin/test-plugin`](packages/wp-plugin/test-plugin) for a complete example.

# Rebuild the whole monorepo by force

- The build command builds the workspace packages no matter what their flavor is.

- Most importantly, it takes care of the dependencies between the packages.

  **If a package depends on another package, the build process builds the dependent package first.**

- Builds are incremental. The build process rebuilds a workspace package only if the package is actually outdated.

  The build process writes a `build-info` file after every successful build (its file modification time marks
  _"last built at"_). The build process considers a workspace package outdated, and therefore rebuilds it, if any of
  the following is true:

  - it has no `build-info` file yet (never built)
  - any file in the package directory is newer than its `build-info` file (source changed).

    This check excludes generated artifacts: `dist/`, `build-info` itself, `node_modules/`,
    `.git/`, and generated localization files (`languages/*.po`, `languages/*.pot`).

  - one of its `workspace:*` dependencies has a newer `build-info` file (the build process rebuilt a dependency)
  - the root `pnpm-lock.yaml` or the package's own `package.json` is newer than its `build-info` file
    (dependencies changed)

  > `docker` packages keep their own, pre-existing skip check. The build process rebuilds them only if the package
  > version changed and no matching image exists locally already.

  **To force a rebuild of everything regardless of the checks above, use the `--force` flag.**

  > After `git pull` or `git checkout`, it is always a good idea to rebuild the whole workspace using `pnpm build --force`.

> Most monorepo commands support the `--help` command-line flag. Use it to get more information about the command.

# Build a new plugin

- Create a new directory `foo` in `./packages/wp-plugin`:
  - Create a new plugin `foo`:

    ```php
    <?php
    /**
    * Plugin Name:       ionos-wordpress/foo
    * Description:       The foo plugin bla bla bla ...
    * Requires at least: 6.6
    * Requires Plugins:
    * Requires PHP:      8.3
    * Version:
    * Update URI:        https://api.github.com/repos/IONOS-WordPress/ionos-wordpress/releases
    * Plugin URI:        https://github.com/IONOS-WordPress/ionos-wordpress/tree/main/packages/wp-plugin/foo
    * License:           GPL-2.0-or-later
    * Author:            IONOS Group
    * Author URI:        https://www.ionos-group.com/brands.html
    * Domain Path:       /languages
    */

    namespace ionos\foo;

    defined('ABSPATH') || exit();

    \add_action( 'init', function (): void {
      $translated_text = \__('Hello World !', 'foo');
      error_log($translated_text);
    });
    ```

  - Create a `package.json` file:

    ```json
    {
      "name": "@ionos-wordpress/foo",
      "version": "0.0.1"
    }
    ```

  - That is all you need.

    Start the development server by running `pnpm start`. This starts the `wordpress-alpine` dev container and also triggers the build process (also called `pnpm build`) for the plugin.

# The build workflow

- syncs the semantic version from `package.json` to the header in the plugin file

- transpiles js/css if the plugin has a `src` folder

  The [`wp-scripts`](https://developer.wordpress.org/block-editor/reference-guidespackages/packages-scripts/) tool also copies all PHP files from the `src` folder.

  The transpilation generates production assets for the smallest asset size, without any debugging information.

  > You can configure the transpilation process to generate assets that are easier to debug. To do this, set the `NODE_ENV` environment variable to `'development'` in your `.env.local` file.

- generates or updates the localization files in `./languages`

- prepackages the plugin resources in `./dist/`

- generates a `build-info` file that shows statistics about the build artifact, such as size and contained files.

  Use this information to check that everything is in its place and to track the size of the plugin.

- creates a PHP 7.4 compatible plugin zip archive, using the `rector` PHP transpiler

  The generated zip archive is production ready. You can deploy it to a WordPress site as is.

# Localization

You can customize the managed languages with the environment variable `WP_CLI_I18N_LOCALES` in the `.env` file.

> Try customizing the `WP_CLI_I18N_LOCALES` environment by adding `es_ES` (in the `.env` or `.env.local` file). Then build the monorepo again using `pnpm build`, or - **much faster** - rebuild just the wordpress plugin using `pnpm build --filter '*/foo'`

If the plugin contains a `src` folder with javascript files, the build process also generates the matching `.json` localization files in the `./languages` folder.

> Sneak peek for the `lint/lint-fix` workshop: [get a free DEEPL API key](https://www.deepl.com/en/pro#developer) and add it to your `.env.local` file. Then run `pnpm lint-fix`, and the localization files are set with translations from [DEEPL](https://www.deepl.com).

# Plugin features

A plugin feature is a self-contained part of a plugin. You can enable or disable it.

In its simplest form, you can enable or disable a feature by

- adding a `require_once` for the feature entry-point PHP file to enable the feature

- or commenting out the `require_once` statement to disable the feature

In its simplest form, a plugin feature is a folder in the `src` folder of the plugin (see [./packages/wp-plugin/test-plugin](./packages/wp-plugin/test-plugin) for an example containing multiple features).

# Shared code

Shared code is useful when multiple plugins share the same code.

We do not plan this yet, but the setup is ready for it.

## Javascript

Place shared Javascript/CSS code in the `./shared/` top-level folder.

When the plugin js code imports the shared code, the transpiler automatically handles encapsulation.

## PHP

Place your shared PHP code in the `./shared/` top-level folder.

- Add a `"postbuild"` script to the plugin that uses the shared PHP code, and copy the shared PHP code to the `build` plugin folder.

  The `build` command automatically calls `"postbuild"` scripts.

- Shared PHP code must be namespaced to avoid conflicts.

- Shared PHP code must make sure it does not run multiple times.

  ```php
  <?PHP

  namespace ionos\shared\nuts;

  // ensure that function gets not redeclared
  if (!function_exists('ionos\shared\nuts\eat')) {
    function eat(): void {
      // smack smack smack ...
    }
  }

  // ensure that function gets not redeclared
  if (!function_exists('ionos\shared\nuts\snack')) {
    function snack(): void {
      // smack smack smack ...
    }
  }
  ```

  - If the shared code contains multiple declarations, you can optimize the redeclaration safety guard further. Wrap the shared feature within its own namespace.

  ```php
  <?PHP

  // ensure that function gets not redeclared
  if (!function_exists('ionos\shared\nuts\peanuts\eat')) {
    namespace ionos\shared\nuts\peanuts;

    function eat(): void {
      // smack smack smack ...
    }

    ...

    function snack(): void {
      // smack smack smack ...
    }
  }
  ```

# Fini

![fini](./fini.jpg)
