<?php

declare(strict_types=1);

use Rector\Config\RectorConfig;

use Rector\ValueObject\PhpVersion;

/*
  @TODO: integrate PHPCS and PHP-CS-Fixer rulesets with EasyCodingStandard instead of calling them separately in the ./scripts/lint.sh script
  - https://hugo.alliau.me/blog/posts/2023-07-19-how-to-use-php-cs-fixer-ruleset-with-easy-coding-standard
  - https://masteringlaravel.io/daily/2023-11-22-how-to-reference-a-php-codesniffer-ruleset-in-easycodingstandard
 */

return RectorConfig::configure()->withSkip([__DIR__ . '/dist/vendor', __DIR__ . '/dist/languages'])->withParallel()
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
