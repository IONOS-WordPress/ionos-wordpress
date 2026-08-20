# lint

The project supports two linting modes: `lint` and `lint-fix`.

- `pnpm lint` checks for linting errors

- `pnpm lint-fix` fixes linting errors as far as possible.

`./scripts/lint.sh` implements both linting commands.

# Which linting tools do we use?

- PHP is linted with a combination of [WordPress Coding Standard rules](https://developer.wordpress.org/coding-standards/wordpress-coding-standards/) and [easy-coding-standard](https://github.com/easy-coding-standard/easy-coding-standard)

  [easy-coding-standard](https://github.com/easy-coding-standard/easy-coding-standard) is a linter that can reuse `PHPCS` and `PHPCF` rules. This makes it easier to configure and use. Most importantly, it can fix _almost any formatting error_ automatically, which saves us a lot of time.
  - The `easy-coding-standard` configuration does not yet integrate WordPress specific [WordPress Coding Standard rules](https://developer.wordpress.org/coding-standards/wordpress-coding-standards/). We plan to add this in the future.

  - `./scripts/lint.sh` also lints plugin entry files (like `./packages/wp-plugin/ionos-essentials/ionos-essentials.php`) to check for the required WordPress plugin metadata.

- We lint Javascript and JSX using [eslint](https://eslint.org/). Its configuration is tailored to fit the needs of the WordPress React libraries (`@wordpress\*`, also called Gutenberg).

- We lint CSS and SCSS using [stylelint](https://stylelint.io/).

- We lint PO/POT files using [dennis](https://github.com/mozilla/dennis)

- [pnpm](https://pnpm.io/) lints its own lock files

- We lint all other files (JSON, Markdown, HTML, etc.) using [prettier](https://prettier.io/)

# configuration

- You can use `./.lintignore` to disable linting. `stylelint`, `eslint`, and `prettier` all read this file.

  Disabling linting makes the most sense for files that are under GIT control but are machine generated (like `packages/wp-plugin/ionos-essentials/inc/dashboard/data/ionos/rendered-skeleton.html`)

  > The linters automatically ignore files matched by `.gitignore`. You do not need to add them to `./.lintignore` as well.

- `./packages/docker/ecs-php/ecs-config.php` contains the configuration for PHP linting using `easy-coding-standard`.
  - Right now it is configured to use `PSR12` (the latest official PHP Coding standard), `symplify` (https://github.com/easy-coding-standard/easy-coding-standard/blob/main/config/set/symplify.php), and a few further settings for dead code detection, etc.

  - As of now, we also use `PHPCS` to run WordPress specific `PHPCS` rules. These rules detect misuse of WordPress functions and paradigms. `./packages/docker/ecs-php/ruleset.xml` holds this configuration.

  > The `easy-coding-standard` configuration does not yet integrate the [WordPress Coding Standard rules](https://developer.wordpress.org/coding-standards/wordpress-coding-standards/) for detecting obsolete WordPress functions. We plan to add this in the future.

- `./eslint.config.mjs` configures `eslint` for Javascript and JSX linting.

- `./.stylelintrc.yml` configures `stylelint` for CSS and SCSS linting.

- `./.prettierrc.js` configures `prettier`

# commands

- Start linting: `pnpm lint`

  You can also call the various linting tools separately (for example `pnpm lint:css` or `pnpm lint-fix:css`). To see the whole list of lint targets, call `pnpm run | grep lint`

- Start linting and fixing: `pnpm lint-fix`

  > `pnpm lint-fix` does not fix missing translations in i18n files. To fix these, run `pnpm lint-fix:i18n` directly.

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
              - wp       operate on wordpress plugin/theme entry files
              - js       operate on js/jsx files
              - css      operate on css/scss files
              - pnpm     operate on pnpm lock file
              - i18n     operate on po/pot files

  Example usage : lint all files matching prettier and i18n, skip php files etc.

    pnpm lint --use prettier -use i18n
```

Run `pnpm lint --help` for the authoritative, always up to date option list.

# docker images

Some linters run inside docker images built from `./packages/docker/*`:

| image                         | needed by                                              |
| ----------------------------- | ------------------------------------------------------ |
| `ionos-wordpress/ecs-php`     | `--use php` (and therefore `--use all`)                |
| `ionos-wordpress/dennis-i18n` | `--use i18n` (and therefore `--use all`)               |
| `ionos-wordpress/potrans`     | **only** `pnpm lint-fix:i18n` (deepl auto-translation) |

`./scripts/lint.sh` builds exactly the images that the selected linters need, before
it runs them. As a result, `pnpm lint` never builds `potrans`. In CI, these images are not
built from scratch. Instead, CI pulls them from the registry. See the `lint` job in
`./.github/workflows/integration.yaml`.
