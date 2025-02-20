<?php

use Symplify\EasyCodingStandard\Config\ECSConfig;
use PhpCsFixer\Fixer\ControlStructure\YodaStyleFixer;
use PhpCsFixer\Fixer\Operator\BinaryOperatorSpacesFixer;
use WordPressCS\WordPress\Sniffs\WP\GlobalVariablesOverrideSniff;

$codeSnifferConfig = new PHP_CodeSniffer\Config(["--standard=./packages/docker/ecs-php/ruleset.xml"]);
PHP_CodeSniffer\Autoload::addSearchPath('/composer/vendor/wp-coding-standards/wpcs/WordPress', "WordPressCS\WordPress");
PHP_CodeSniffer\Autoload::addSearchPath('/composer/vendor/wp-coding-standards/wpcs/WordPress-Extra', "WordPressCS\WordPress-Extra");
PHP_CodeSniffer\Autoload::addSearchPath('/composer/vendor/wp-coding-standards/wpcs/WordPress-Core', "WordPressCS\WordPress-Core");

$configure = ECSConfig::configure();

$codeSnifferRuleset = new PHP_CodeSniffer\Ruleset($codeSnifferConfig);

return $configure->withRules([
    // import the rules from our loaded codesniffer config
    ...array_values($codeSnifferRuleset->sniffCodes),
])
  ->withPaths(['.'])
  ->withRootFiles()
  ->withSkip(
    [
      '*/vendor/*',
      '*/build/*',
      '*/dist/*',
      '*/node_modules/*',
      '*/languages/*',
      '/phpunit/*',
      '*/wp-env-home/*',
      '*/.git/*',
      '/tmp/*',
      '**/ecs-config.php',
      'rector-config-php7.4.php',
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
  // use editor config if available
  // ->withEditorConfig(true)
  // use 2 spaces instead of psr12 default (4 spaces)
  ->withSpacing(indentation: '  ')

  ->withConfiguredRule(YodaStyleFixer::class, [
    'equal' => true,
    'identical' => true,
    'less_and_greater' => true,
  ])
  // align assoc arrays
  ->withConfiguredRule(BinaryOperatorSpacesFixer::class, [
    'default' => 'align',
  ])
  ->withConfiguredRule(GlobalVariablesOverrideSniff::class, [
    'treat_files_as_scoped' => true,
  ])
;
