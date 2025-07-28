<?php

use function ionos\essentials\security\obfuscate_email;
use const ionos\essentials\PLUGIN_DIR;

/**
 * covers the tests for Security features
 *
 * run only this test using 'pnpm test:php --php-opts "--filter ClassSecurityTest"'
 */
class ClassSecurityTest extends \WP_UnitTestCase
{
  protected function setUp(): void
  {
    // ensure that post types and taxonomies are reset for each test.
    if (! defined('WP_RUN_CORE_TESTS')) {
      define('WP_RUN_CORE_TESTS', true);
    }

    parent::setUp();

    \activate_plugin('ionos-essentials/ionos-essentials.php');

    require_once PLUGIN_DIR . '/inc/security/credentials-checking.php';
  }

  public function testObfuscateEmail(): void
  {
    $this->assertEquals('w*******s@e*****e.com', obfuscate_email('wordpress@example.com'));
    $this->assertEquals('h***z@k*****p.de', obfuscate_email('heinz@ketchup.de'));
    $this->assertEquals('m*@h*******r.org', obfuscate_email('me@hpbaxxter.org'));
    $this->assertEquals('e***a@m***o.uk', obfuscate_email('erika@me.co.uk'));
    $this->assertEquals('a*@c*.com', obfuscate_email('ab@cd.com'));
    $this->assertEquals('m**@m********n.de', obfuscate_email('max@mustermann.de'));
    $this->assertEquals('m**@m**.de', obfuscate_email('min@max.de'));
    $this->assertEquals('*@*.com', obfuscate_email('a@d.com'));
  }
}
