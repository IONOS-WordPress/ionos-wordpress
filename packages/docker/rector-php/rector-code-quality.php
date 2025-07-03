<?php

declare(strict_types=1);

use Rector\Config\RectorConfig;
use Rector\ValueObject\PhpVersion;

return RectorConfig::configure()->withSkip([__DIR__ . '/dist/vendor', __DIR__ . '/dist/languages'])->withParallel()
  ->withPhpVersion(PhpVersion::PHP_83)->withPreparedSets(
    // deadCode: true,
    codeQuality: true,
    // codingStyle: true,
    // typeDeclarations: true,
    // privatization: true,
    // naming: true,
    // instanceOf: true,
    // earlyReturn: true,
    // strictBooleans: true,
    // carbon: true,
    // rectorPreset: true,
  );
