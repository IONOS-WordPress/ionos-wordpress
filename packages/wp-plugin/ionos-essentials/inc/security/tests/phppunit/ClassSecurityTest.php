<?php

use function ionos\essentials\security\obfuscate_email;
use const ionos\essentials\PLUGIN_DIR;

/**
 * covers the tests for Security features
 *
 * run only this test using 'pnpm test:php --php-opts "--filter ClassSecurityTest"'
 *
 * @group dashboard
 * @group essentials
 */
class ClassSecurityTest extends \WP_UnitTestCase {

  public function setUp(): void {
    // ensure that post types and taxonomies are reset for each test.
    if (!defined('WP_RUN_CORE_TESTS')) {
      define('WP_RUN_CORE_TESTS', true);
    }

    parent::setUp();

    \activate_plugin('ionos-essentials/ionos-essentials.php');

    require_once PLUGIN_DIR . '/inc/security/credentials-checking.php';
  }


  function test_obfuscate_email() : void {
    $this->assertEquals('wo*******@*****e.com', obfuscate_email('wordpress@example.com'));
    $this->assertEquals('he***@*****p.de', obfuscate_email('heinz@ketchup.de'));
    $this->assertEquals('***@*******r.com', obfuscate_email('me@hpbaxxter.com'));
    $this->assertEquals('er***@***.com', obfuscate_email('erika@me.com'));
  }
}
