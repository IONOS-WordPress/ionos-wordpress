<?php

declare(strict_types=1);

// use Symplify\CodingStandard\Fixer\LineLength\LineLengthFixer;
use Symplify\EasyCodingStandard\Config\ECSConfig;

return ECSConfig::configure()
  ->withPaths([__DIR__])
  ->withRootFiles()
  ->withSkip(
    ['*/vendor/*', '*/build/*', '*/dist/*', '*/node_modules/*', '*/languages/*', '/wp-env-home/*', '/phpunit/*']
  )
  ->withPreparedSets(
    symplify: true,
    // psr12: true,
    // arrays: true,
    common: true, // (arrays | spaces | namespaces | docblocks | controlStructures | phpunit | comments)
    cleanCode: true,
    // comments: true,
    // docblocks: true,
    // spaces: true,
    // namespaces : true,
    // controlStructures: true,
    // phpunit : true
    // strict: true,
    // docblocks: true,
  )
  // use 2 spaces instead of psr12 default (4 spaces)
  ->withSpacing(indentation: '  ');
/*
  ->withConfiguredRule(LineLengthFixer::class, [
    LineLengthFixer::LINE_LENGTH => 80,
    LineLengthFixer::BREAK_LONG_LINES => true,
  ])*/
