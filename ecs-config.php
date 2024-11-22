<?php

// use Symplify\CodingStandard\Fixer\LineLength\LineLengthFixer;
// use WordPressCS\WordPress\Sniffs\Arrays\ArrayDeclarationSpacingSniff;
use Symplify\EasyCodingStandard\Config\ECSConfig;

// use PhpCsFixer\Fixer\ArrayNotation\TrailingCommaInMultilineArrayFixer;
// $codeSnifferConfig = new PHP_CodeSniffer\Config(["--standard=WordPress"]);
// print_r( $codeSnifferConfig);
// $codeSnifferRuleset = new PHP_CodeSniffer\Ruleset($codeSnifferConfig);

return ECSConfig::configure()
  ->withPaths([__DIR__])
  ->withRootFiles()
  ->withSkip(
    [
      '*/vendor/*',
      '*/build/*',
      '*/dist/*',
      '*/node_modules/*',
      '*/languages/*',
      '/wp-env-home/*',
      '/phpunit/*',
      '/tmp/*',
    ]
  )
  ->withPreparedSets(
    symplify: true,
    psr12: true,
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
  ->withSpacing(indentation: '  ')

  /*
    // example of adding a single rule without configuration
    // force trailing commas in multiline arrays
    ->withRules([
      TrailingCommaInMultilineArrayFixer::class,
    ]);
  */

  /*
    // example of adding a single rule with configuration
    ->withConfiguredRule(LineLengthFixer::class, [
      LineLengthFixer::LINE_LENGTH => 80,
      LineLengthFixer::BREAK_LONG_LINES => true,
    ])
  */

  /*
    // example of importing a WordPress Coding standard rule
    ->withRules([ArrayDeclarationSpacingSniff::class])
  */

  /*
    // example of importing a WordPress Coding standard rule with configuration

    ->withConfiguredRule(ArrayDeclarationSpacingSniff::class, [])
      // rulesWithConfiguration
    ->withRules([
      // our existing PSR12 set from PHP Code Sniffer
      ...array_values($codeSnifferRuleset->sniffCodes),

      // // and the two new rules I wanted from PHP CS Fixer
      // PhpCsFixer\Fixer\PhpUnit\PhpUnitMethodCasingFixer::class,
      // PhpCsFixer\Fixer\PhpUnit\PhpUnitTestAnnotationFixer::class,
    ])
  */
;
