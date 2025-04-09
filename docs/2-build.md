# Folder structure

- Workspace packages are located in folders depending on their _flavor_.

  ```
  ./packages
  ├── docker        # docker packages
  ├── npm           # npm packages
  └── wp-plugin     # WordPress plugins
  ```

  > Organizing packages by _flavor_ makes it easy to decide hwo to build the workspace package.

- a workspace package contains just the code and a `package.json`. The `package.json` is used for

  - workspace dependencies declaration

  - manage semantic versioning

  - (optional) customize individual scripts

- WordPress plugins are located in `./packages/wp-plugin` and have the following structure :

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

# Forcefully rebuilding the whole monorepo

- The build command will build the workspace packages no matter of their flavor.

- Most importantly it will take care of the dependencies between the packages.

  **If a package is dependent on another package, the dependent package will be built first.**

- Caveat : `docker` images are only rebuilt if the package version was changed for performance reasons.

  **To force rebuilding everything you can use the `--force` flage to rebuild everything**

  > After `git pull` or `git checkout` it is always a good idea to rebuild the whole workspace using `pnpm build --force`.

> Most monorepo command support the `--help` commandline flag. Use it to get more information about the command.

# Let's build a new plugin

- create a new directory `foo` in `./packages/wp-plugin` :

  - create a new plugin `foo` :

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

  - create a `package.json` file :

    ```json
    {
      "name": "@ionos-wordpress/foo",
      "version": "0.0.1"
    }
    ```

  - That's it !

    Start the development server by excuting `pnpm start`. This will not only start `wp-env` but also trigger the build process (aka `pnpm build`) for the plugin.

# The build workflow

- syncs the semantic version from `package.json` to the header in the plugin file

- transpiles js/css if a `src` folder was found in the plugin

  The [`wp-scripts`](https://developer.wordpress.org/block-editor/reference-guidespackages/packages-scripts/) tool will also copy all PHP files from the `src` folder.

  The transpilation will generate production assets for minimum asset size and without any debugging information.

  > You can configure the transpilation process to generate debugging friendly assets by setting `NODE_ENV` environment variable to `'development'` in your `.env.local` file.

- generates / updates the localization files in `./languages`

- prepackages the plugin ressources in `./dist/`

- generates a `build-info` file showing statistics about the build artifact like size and contained files.

  This information is very useful to check if everything is at it's place and to track the size of the plugin.

- creates a php 7.4 compatible plugin zip archive using `rector` PHP transpiler

  The generated zip archive is production ready and can be deployed to a WordPress site as is.

# Localization

The managed languages can be customized using environment variable `WP_CLI_I18N_LOCALES` in the `.env` file.

> Try customizing the `WP_CLI_I18N_LOCALES` environment by adding `es_ES` (in `.env` or `.env.local` file) and build the monorepo again using `pnpm build` or - **much faster** - rebuild just the wordpress plugin using `pnpm build --filter '*/foo'`

If the plugin contains a `src` folder with javascript files the build process will also generate the matching `.json` localization files in the `./languages` folder.

> Sneak peek for the `lint/lint-fix` workshop - [get a free DEEPL API key](https://www.deepl.com/en/pro#developer) and add it in your `.env.local` file and execute `pnpm lint-fix`. And voilà, the localization files are all set with the translations from [DEEPL](https://www.deepl.com).

# Plugin features

A plugin feature is a self-contained part of a plugin that can be enabled or disabled.

The feature enablement can be (at it's simplest form) done by

- `require_once` the feature entrypoint PHP file to enable the feature

- or commenting the `require_once` statement out to disable the feature

At it's simplest form a plugin feature is a folder in the `src` folder of the plugin (see [./packages/wp-plugin/test-plugin](./packages/wp-plugin/test-plugin) for an example containing multiple features).

# Shared code

Shared code will be a use case if we have multiple plugins that share the same code.

This is not planned (yet), but we are prepared.

## Javascript

Place shared Javascript/CSS code in the `./shared/` top level folder.

By importing the shared code in the plugin js code, the transplier will automatically take care of encapsulation.

## PHP

Place your shared PHP Code in the `./shared/` top level folder.

- add a `"postbuild"` script to the plugin using the shared PHP code and copy the shared PHP Code to the `build` plugin folder.

  `"postbuild"` scripts will automatically be called by the `build` command.

- Shared PHP Code need to be namespaced to avoid conflicts.

- Shared PHP Code need to ensure that it is not executed multiple times.

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

  - If the shared code contains multiple declarations, you can even optimize the redeclaration safety guard by wrapping the shared feature within it's own namespace.

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
