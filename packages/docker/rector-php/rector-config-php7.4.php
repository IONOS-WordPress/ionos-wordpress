<?php

declare(strict_types=1);

use Rector\Config\RectorConfig;
use Rector\ValueObject\PhpVersion;

/*
  @TODO: integrate PHPCS and PHP-CS-Fixer rulesets with EasyCodingStandard instead of calling them separately in the ./scripts/lint.sh script
  - https://hugo.alliau.me/blog/posts/2023-07-19-how-to-use-php-cs-fixer-ruleset-with-easy-coding-standard
  - https://masteringlaravel.io/daily/2023-11-22-how-to-reference-a-php-codesniffer-ruleset-in-easycodingstandard
 */

// rector runs either from the ionos-wordpress/rector-php docker image
// (COMPOSER_HOME=/composer, see packages/docker/rector-php/Dockerfile) or natively inside
// the dev container, which installs it under its own COMPOSER_HOME (see
// .devcontainer/Dockerfile). both layouts put the dependencies at "$COMPOSER_HOME/vendor",
// so resolving the stubs through the environment keeps this config identical for both.
// the fallback keeps older image tags that predate the env var working.
require_once (getenv('COMPOSER_HOME') ?: '/composer') . '/vendor/php-stubs/wordpress-stubs/wordpress-stubs.php';

// skipped as fnmatch patterns rather than __DIR__-relative paths: __DIR__ is the synthetic
// /project root when this config is bind-mounted into the image, but the real
// packages/docker/rector-php directory when rector runs natively - so an absolute path
// built from it would silently stop matching in native mode and let rector rewrite bundled
// dependencies. the patterns mean the same thing in both.
return RectorConfig::configure()->withSkip(['*/vendor/*', '*/languages/*'])->withParallel()
  // see https://github.com/rectorphp/rector-src/blob/3ed476b9ab65958d85416e48a810b11dbaf4283a/build/config/config-downgrade.php
  //->withPHPStanConfigs([__DIR__ . '/phpstan-for-downgrade.neon'])
  ->withPhpVersion(PhpVersion::PHP_83)
  /*
  ->withPreparedSets(
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
  */
  ->withDowngradeSets(php74: true);
