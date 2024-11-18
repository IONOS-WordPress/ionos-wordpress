# About

This image provides the most recent [rector](https://github.com/rectorphp/rector) in a docker image.

## Why ?

I needed a way to provide [rector](https://github.com/rectorphp/rector) on demand and cross platform (Linux/maxOS/Windows).

=> That's exactly what a Docker image can do :-)

# Usage

@TODO: add usage documentation

See [rector](https://github.com/rectorphp/rector) homepage for all options.

# Snippets

- jump into docker image using bash : `docker run -q -it --rm --user "$(id -u $USER):$(id -g $USER)" --entrypoint /bin/bash ionos-wordpress/rector-php`

- show [rector](https://github.com/rectorphp/rector) version : `docker run -q -it --rm --user "$(id -u $USER):$(id -g $USER)" ionos-wordpress/rector-php --version`
