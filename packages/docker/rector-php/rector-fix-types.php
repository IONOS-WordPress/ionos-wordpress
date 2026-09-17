<?php

declare(strict_types=1);

use Rector\Config\RectorConfig;
use Rector\ValueObject\PhpVersion;

// fnmatch patterns rather than __DIR__-relative paths - see rector-config-php7.4.php for
// why __DIR__ cannot be used once rector also runs natively
return RectorConfig::configure()->withSkip(['*/vendor/*', '*/languages/*'])->withParallel()
  ->withPhpVersion(PhpVersion::PHP_83)->withPreparedSets(
    // deadCode : true,
    // codeQuality : true,
    codingStyle : true,
    typeDeclarations : true,
    // privatization : true,
    // naming : true,
    instanceOf : true,
    /* earlyReturn : true, */
    strictBooleans : true,
    // rectorPreset : true,
  );
