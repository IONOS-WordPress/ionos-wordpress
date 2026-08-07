<?php

use Symplify\EasyCodingStandard\Config\ECSConfig;
use PhpCsFixer\Fixer\ControlStructure\YodaStyleFixer;
use PhpCsFixer\Fixer\Operator\BinaryOperatorSpacesFixer;
use WordPressCS\WordPress\Sniffs\Security\EscapeOutputSniff;

$codeSnifferConfig = new PHP_CodeSniffer\Config(["--standard=./packages/docker/ecs-php/ruleset.xml"]);

// ecs runs either from the ionos-wordpress/ecs-php docker image (COMPOSER_HOME=/composer,
// see packages/docker/ecs-php/Dockerfile) or natively inside the dev container, which
// installs it under its own COMPOSER_HOME (see .devcontainer/Dockerfile). both layouts put
// the dependencies at "$COMPOSER_HOME/vendor", so resolving the wpcs standards through the
// environment keeps this config identical for both - hardcoding /composer would break the
// native path. the fallback keeps older image tags that predate the env var working.
$vendorDir = (getenv('COMPOSER_HOME') ?: '/composer') . '/vendor';
PHP_CodeSniffer\Autoload::addSearchPath("$vendorDir/wp-coding-standards/wpcs/WordPress", "WordPressCS\WordPress");
PHP_CodeSniffer\Autoload::addSearchPath("$vendorDir/wp-coding-standards/wpcs/WordPress-Extra", "WordPressCS\WordPress-Extra");
PHP_CodeSniffer\Autoload::addSearchPath("$vendorDir/wp-coding-standards/wpcs/WordPress-Core", "WordPressCS\WordPress-Core");

$configure = ECSConfig::configure();

$codeSnifferRuleset = new PHP_CodeSniffer\Ruleset($codeSnifferConfig);

$sniffCodes = $codeSnifferRuleset->sniffCodes;
unset( $sniffCodes['WordPress.Security.EscapeOutput']);

if( !class_exists('WordPressSecurityEscapeOutputSniff') ) {
  class WordPressSecurityEscapeOutputSniff extends EscapeOutputSniff {
    public function getGroups() {
      $groups = parent::getGroups();
      // remove 'printf' from the list of printing functions so that we can use it without any errors
      $groups['printing_functions']['functions'] = array_diff($groups['printing_functions']['functions'], ['printf', 'wp_die', 'error_log']);
      return $groups;
    }
  }
}

return $configure->withRules([
    // import the rules from our loaded codesniffer config
    ...array_values($sniffCodes),
    // @TODO: Enable this sniff once we have fixed all the issues
    // WordPressSecurityEscapeOutputSniff::class,
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
      '*/mnt/*',
      '*/.git/*',
      '/tmp/*',
      '*/docs/packages/*',
      '**/ecs-config.php',
      '**/rector-config-php7.4.php',
      '**/rector-fix-types.php',
      '**/stretch-extra/plugins/*',
      '**/stretch-extra/themes/*',
      '**/stretch-extra/inc/apcu/object-cache.php',
      YodaStyleFixer::class
    ]
  )
  ->withPreparedSets(
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
  // align assoc arrays
  ->withConfiguredRule(BinaryOperatorSpacesFixer::class, [
    'default' => 'align',
  ])
;
