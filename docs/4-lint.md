# lint

the project supports 2 linting modes : `lint` and `lint-fix`.

- `pnpm lint` will check for linting errors

- `pnpm lint-fix` will fix them as far as possible.

Both linting commands are implemented in `./scripts/lint.sh`.

# linting tools ?

- PHP is linted with a combination of [WordPress Coding Standard rules](https://developer.wordpress.org/coding-standards/wordpress-coding-standards/) and [easy-coding-standard](https://github.com/easy-coding-standard/easy-coding-standard)

  [easy-coding-standard](https://github.com/easy-coding-standard/easy-coding-standard) is a linter capable if reusing `PHPCS` and `PHPCF` rules making it easier to configure and use. Most importantly, it allows to fix _almost any formatting errors_ automatically saving us a lot of time.

  - WordPress specific [WordPress Coding Standard rules](https://developer.wordpress.org/coding-standards/wordpress-coding-standards/) rules are not yet integrated in the `easy-coding-standard` configuration but it's planned for the future. That's why the `./scripts/lint.sh` script also runs `phpcs` directly.

  - Plugin entry files (like `./packages/wp-plugin/ionos-essentials/ionos-essentials.php`) are also linted to contain the required WordPress plugin metadata using `./scripts/lint.sh`.

- Javascript and JSX is linted using [eslint](https://eslint.org/). It's configuration is tailored to fit especially the needs of the WordPress React libraries (`@wordpress\*` aka Gutenberg).

- CSS and SCSS is linted using [stylelint](https://stylelint.io/).

- PO/POT files are linted using [dennis](https://github.com/mozilla/dennis)

- pnpm files are linted using [pnpm](https://pnpm.io/) itself

- all other files (JSON, Markdown, HTML, etc.) are linted using [prettier](https://prettier.io/)

# configuration

- `./.lintignore` can be used to disable linting. It will be consumed by `stylelint`, `eslint` and `prettier`.

  Disabling linting for certain files makes especially sense for files that are under GIT control but machine generated (like `packages/wp-plugin/essentials/inc/dashboard/data/ionos/rendered-skeleton.html`)

  > Files matched by `.gitignore` will be automatically ignored by the linters. They don't need to be additionally added to `./.lintignore`

- `./ecs-config.php` contains the configuration for PHP linting using `easy-coding-standard`.

  - Right now it's configured to use the `PSR12` (this is the latest official PHP Coding standard), `symplify` (https://github.com/easy-coding-standard/easy-coding-standard/blob/main/config/set/symplify.php) and a few further settings for dead code detection etc.

  - `PHPCS` is - as of now - also used for executing WordPress specific `PHPCS` rules detecting misuse of WordPress functions and paradigms. The configuration is done in `./packages/docker/ecs-php/ruleset.xml`.

  > The [WordPress Coding Standard rules](https://developer.wordpress.org/coding-standards/wordpress-coding-standards/) for detecting obsolete WordPRess functions are not yet integrated in the `easy-coding-standard` configuration but it's planned for the future.

- `./eslint.config.mjs` configures `eslint` for Javascript and JSX linting.

- `./.stylelintrc.yml` configures `stylelint` for CSS and SCSS linting.

- `prettier` is configured using `./.prettierrc.js`

# commands

## lint

- start linting : `pnpm lint`

- start linting + fixing : `pnpm lint --fix`

- `./scripts/lint.sh`

```
By default every source file will be linted.

Options:

  --help    Show this help message and exit

  --fix     Apply lint fixes where possible

  --use     Specify which linters to use (default: all)

            Available options:
              - all      operate on all files
              - php      operate on php files
              - prettier operate html/yml/md/etc. files
              - js       operate on js/jsx files
              - css      operate on css/scss files
              - pnpm     operate on pnpm lock file
              - i18n     operate on po/pot files

  Example usage : lint all files matching prettier and i18n, skip php files etc.

    pnpm lint --use prettier -use i18n
```
