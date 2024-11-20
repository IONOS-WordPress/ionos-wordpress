# About

This image provides the most recent [PHP easy-coding-standard](https://github.com/easy-coding-standard/easy-coding-standard) in a docker image.

## Why ?

I needed a way to provide [PHP easy-coding-standard](https://github.com/easy-coding-standard/easy-coding-standard) on demand and cross platform (Linux/maxOS/Windows).

=> That's exactly what a Docker image can do :-)

# Usage

@TODO: add usage documentation

See [PHP easy-coding-standard](https://github.com/easy-coding-standard/easy-coding-standard) homepage for all options.

# Snippets

- execute docker image with current directory mounted as php source project : `docker run -q -it --rm --user "$(id -u $USER):$(id -g $USER)" -v $(pwd):/project ionos-wordpress/ecs-php`

- jump into docker image using bash : `docker run -q -it --rm --user "$(id -u $USER):$(id -g $USER)" --entrypoint /bin/sh ionos-wordpress/ecs-php`

- show [PHP easy-coding-standard](https://github.com/easy-coding-standard/easy-coding-standard) version : `docker run -q -it --rm --user "$(id -u $USER):$(id -g $USER)" ionos-wordpress/ecs-php --version`

## Example usage

Create a file `ecs.php` in your project root directory with the following content:

```php
<?php

declare(strict_types=1);

// use Symplify\CodingStandard\Fixer\LineLength\LineLengthFixer;
use Symplify\EasyCodingStandard\Config\ECSConfig;

return ECSConfig::configure()
  ->withPaths([__DIR__])
  ->withRootFiles()
  ->withSkip(['*/vendor/*', '*/build/*', '*/dist/*', '*/node_modules/*', '*/src/*', '*/tests/*'])
  ->withPreparedSets(
    // symplify: true,
    // psr12: true,
    // arrays: true,
    // common: true, // (arrays | spaces | namespaces | docblocks | controlStructures | phpunit | comments)
    // cleanCode: true,
    // comments: true,
    // docblocks: true,
    // spaces: true,
    // namespaces : true,
    // controlStructures: true,
    // phpunit : true
    // strict: true,
    // docblocks: true,
    symplify: true,
  )
  // use 2 spaces instead of psr12 default (4 spaces)
  ->withSpacing(indentation: '  ');
/*
  ->withConfiguredRule(LineLengthFixer::class, [
    LineLengthFixer::LINE_LENGTH => 80,
    LineLengthFixer::BREAK_LONG_LINES => true,
  ])*/

```

and call

```bash
docker run -q -it --rm --user "$(id -u $USER):$(id -g $USER)" -v $(pwd):/project ionos-wordpress/ecs-php
```

to get your project checked against the configured coding standard.

Add `--fix` to get the fixable changes applied.
