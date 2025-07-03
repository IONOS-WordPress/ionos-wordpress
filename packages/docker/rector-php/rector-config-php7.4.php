<?php

declare(strict_types=1);

use Rector\Config\RectorConfig;
use Rector\ValueObject\PhpVersion;

require_once '/composer/vendor/php-stubs/wordpress-stubs/wordpress-stubs.php';

return RectorConfig::configure()->withSkip([__DIR__ . '/dist/vendor', __DIR__ . '/dist/languages'])->withParallel()
  // see https://github.com/rectorphp/rector-src/blob/3ed476b9ab65958d85416e48a810b11dbaf4283a/build/config/config-downgrade.php
  //->withPHPStanConfigs([__DIR__ . '/phpstan-for-downgrade.neon'])
  ->withPhpVersion(PhpVersion::PHP_83)
  ->withDowngradeSets(php74: true);
