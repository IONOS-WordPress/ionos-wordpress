# About

This image provides the most recent [rector](https://github.com/rectorphp/rector) in a docker image.

## Why ?

I needed a way to provide [rector](https://github.com/rectorphp/rector) on demand and cross platform (Linux/maxOS/Windows).

=> That's exactly what a Docker image can do :-)

# Usage

@TODO: add usage documentation

See [rector](https://github.com/rectorphp/rector) homepage for all options.

# Snippets

- jump into docker image using bash : `docker run -q -it --rm --user "$(id -u $USER):$(id -g $USER)" --entrypoint /bin/sh ionos-wordpress/rector-php`

- show [rector](https://github.com/rectorphp/rector) version : `docker run -q -it --rm --user "$(id -u $USER):$(id -g $USER)" ionos-wordpress/rector-php --version`

## Example usage

Create a file `rector.php` in your project root directory with the following content:

```php
<?php

declare(strict_types=1);

use Rector\Config\RectorConfig;

return RectorConfig::configure()
  ->withSkip([
    __DIR__ . '/dist/vendor',
    __DIR__ . '/dist/languages',
  ])
  ->withParallel()
  // see https://github.com/rectorphp/rector-src/blob/3ed476b9ab65958d85416e48a810b11dbaf4283a/build/config/config-downgrade.php
  //->withPHPStanConfigs([__DIR__ . '/phpstan-for-downgrade.neon'])

  //->withPhpVersion(PhpVersion::PHP_83)

  -> withPreparedSets(
    // deadCode: true,
    // codeQuality: true,
    // codingStyle: true,
    // typeDeclarations: true,
    // privatization: true,
    // naming: true,
    // instanceOf: true,
    // earlyReturn: true,
    // strictBooleans: true,
    // carbon: true,
    // rectorPreset: true,
    // phpunitCodeQuality: true,
    // doctrineCodeQuality: true,
    // symfonyCodeQuality: true,
    // symfonyConfigs: true,
    // // composer based
    // twig: true,
    // phpunit: true,
  )

  // downgrade php code to php 7.4
  ->withDowngradeSets(
    php74: true,
  );
```

and call

```bash
docker run -q -it --rm --user "$(id -u $USER):$(id -g $USER)" -v $(pwd):/project ionos-wordpress/rector-php
```

to get your project gets transformed using the rector configuration.
