<?php

use ionos\essentials\loop;

use const ionos\essentials\migration\WP_OPTION_LAST_INSTALL_DATA;
use const ionos\essentials\migration\WP_OPTION_LAST_INSTALL_DATA_KEY_PLUGIN_VERSION;

/**
 * run only this test using 'pnpm test:php --php-opts "--filter LoopTest"'
 *
 * @group loop
 * @group essentials
 */
class LoopTest extends \WP_UnitTestCase {

  public function setUp(): void {
    // ensure that post types and taxonomies are reset for each test.
    if (!defined('WP_RUN_CORE_TESTS')) {
      define('WP_RUN_CORE_TESTS', true);
    }

    parent::setUp();

    \activate_plugin('ionos-essentials/ionos-essentials.php');
  }

  public function test_register_datacollector_endpoint() : void {
    $this->markTestSkipped('to be implemented');
  }

  public function test_rest_loop_data() : void {
    $this->markTestSkipped('to be implemented');
  }
}
