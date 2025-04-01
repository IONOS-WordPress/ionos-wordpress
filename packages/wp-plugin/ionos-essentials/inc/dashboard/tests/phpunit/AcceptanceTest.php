<?php

use const ionos_wordpress\essentials\PLUGIN_DIR;

/**
 * covers the acceptance tests for the essentials dashboard features.
 *
 * @group dashboard
 * @group essentials
 */
class AcceptanceTest extends \WP_UnitTestCase {
  public function setUp(): void {
    // ensure that post types and taxonomies are reset for each test.
    if (!defined('WP_RUN_CORE_TESTS')) {
      define('WP_RUN_CORE_TESTS', true);
    }

    parent::setUp();

    \activate_plugin('ionos-essentials/ionos-essentials.php');
  }

  function test_dashboard_blocks_registered() : void {
    // our dashboard blocks (aka expected)
    $declared_dashboard_block_names = array_map(
      fn($block) => $block['name'],
      require PLUGIN_DIR . '/build/dashboard/blocks/blocks-manifest.php',
    );

    \do_action('init');

    // registered blocks (aka actual)
    $registered_block_names = array_keys(\WP_Block_Type_Registry::get_instance()->get_all_registered());
    $registered_block_names = array_filter($registered_block_names, fn($_) => str_starts_with($_, 'ionos-dashboard-page/'));

    $this->assertEqualsCanonicalizing($declared_dashboard_block_names, $registered_block_names, 'all dashboard blocks are registered.');
  }
}
