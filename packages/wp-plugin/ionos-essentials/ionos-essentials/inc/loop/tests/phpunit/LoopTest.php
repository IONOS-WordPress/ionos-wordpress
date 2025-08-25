<?php

use ionos\essentials\loop;

use const ionos\essentials\loop\IONOS_LOOP_CONSENT_OPTION;
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

  public function test_migration() : void {
    // set pre migration state
    \delete_option(IONOS_LOOP_CONSENT_OPTION);
    \update_option('ionos_loop_consent', '1');
    \add_option(WP_OPTION_LAST_INSTALL_DATA, [
      WP_OPTION_LAST_INSTALL_DATA_KEY_PLUGIN_VERSION => '1.0.10',
    ]);

    // trigger migration
    //do_action('admin_init');
    ionos\essentials\migration\_install();

    $this->assertEquals(
      true,
      \get_option(IONOS_LOOP_CONSENT_OPTION),
      sprintf('option "ionos_loop_consent" should be migrated to "%s"', IONOS_LOOP_CONSENT_OPTION)
    );
    $this->assertFalse(
      \get_option('ionos_loop_consent', false),
      'option "ionos_loop_consent" should be deleted'
    );
  }
}
