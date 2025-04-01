<?php

use const ionos_wordpress\essentials\migration\WP_OPTION_LAST_INSTALL_DATA;
use const ionos_wordpress\essentials\PLUGIN_FILE;
use const ionos_wordpress\essentials\migration\WP_OPTION_LAST_INSTALL_DATA_KEY_PLUGIN_VERSION;

use function ionos_wordpress\essentials\migration\_install;

/**
 * covers the migration tests.
 *
 * run only this test using 'pnpm test:php --php-opts "--filter MigrationTest"'
 *
 * @group essentials
 */
class MigrationTest extends \WP_UnitTestCase {
  public function setUp(): void {
    // ensure that post types and taxonomies are reset for each test.
    if (!defined('WP_RUN_CORE_TESTS')) {
      define('WP_RUN_CORE_TESTS', true);
    }

    parent::setUp();

    \activate_plugin('ionos-essentials/ionos-essentials.php');
  }

  function test_initial() : void {
    $this->assertFalse(get_option(WP_OPTION_LAST_INSTALL_DATA), 'initial install data should not exist');
    _install();
    $install_data = \get_option(WP_OPTION_LAST_INSTALL_DATA);
    $this->assertIsArray($install_data, 'option install data should be an array');

    $this->assertArrayHasKey(WP_OPTION_LAST_INSTALL_DATA_KEY_PLUGIN_VERSION, $install_data, 'plugin version should be set');

    $this->assertEquals(\get_plugin_data(PLUGIN_FILE)['Version'], $install_data[WP_OPTION_LAST_INSTALL_DATA_KEY_PLUGIN_VERSION], 'plugin version should match');
  }
}
