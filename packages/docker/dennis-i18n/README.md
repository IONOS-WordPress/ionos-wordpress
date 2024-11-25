# About

This package provides the most recent [dennis](https://github.com/mozilla/dennis) pot/po linter in a docker image.

## Why ?

I needed a way to provide [dennis](https://github.com/mozilla/dennis) on demand and cross platform (Linux/maxOS/Windows).

=> That's exactly what a Docker image can do :-)

# Usage

@TODO: add usage documentation

See [dennis](https://github.com/mozilla/dennis) homepage for all options.

# Snippets

- show pot/po status for a specific folder : `docker run -it --rm -v $(pwd):/project ionos-wordpress/dennis-i18n status packages/wp-plugin/test-plugin/languages/ packages/wp-plugin/essentials/languages/`

- show pot/po status for a specific file : `docker run -it --rm -v $(pwd):/project ionos-wordpress/dennis-i18n status packages/wp-plugin/test-plugin/languages/ packages/wp-plugin/essentials/languages/wp-plugin.pot` (last argument can also be a po file)

- lint a folder containaing pot/po files : `docker run -it --rm -v $(pwd):/project ionos-wordpress/dennis-i18n lint packages/wp-plugin/test-plugin/languages/ packages/wp-plugin/essentials/languages/` (last argument can also be a po file)

- show [dennis](https://github.com/mozilla/dennis) version : `docker run -q -it --rm --user "$(id -u $USER):$(id -g $USER)" ionos-wordpress/rector-php --version`
