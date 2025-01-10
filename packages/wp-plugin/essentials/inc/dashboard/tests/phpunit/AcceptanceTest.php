<?php

use const ionos_wordpress\essentials\dashboard\ADMIN_PAGE_SLUG;
use const ionos_wordpress\essentials\dashboard\HIDDEN_ADMIN_PAGE_IFRAME_SLUG;
use const ionos_wordpress\essentials\IONOS_ESSENTIALS_PLUGIN_DIR;

/**
 * covers the acceptance tests for the essentials dashboard feature
 *
 * To test only dashboard features, run `pnpm phpunit:test -- --group dashboard`
 * To test only a single test from this testcase, run `pnpm phpunit:test -- --filter test_dashboard_blocks_registered`
 * To test only ththis unit test, run `pnpm phpunit:test -- --filter AcceptanceTest`
 *
 * @group dashboard
 * @group essentials
 */
class AcceptanceTest extends WP_UnitTestCase {
  public function setUp(): void {
    // ensure that post types and taxonomies are reset for each test.
    if (!defined('WP_RUN_CORE_TESTS')) {
      define('WP_RUN_CORE_TESTS', true);
    }

    parent::setUp();

    \activate_plugin('essentials/essentials.php');
  }

  function test_dashboard_blocks_registered() : void {
    // our dashboard blocks (aka expected)
    $declared_dashboard_block_names = array_map(
      fn($block) => $block['name'],
      require(IONOS_ESSENTIALS_PLUGIN_DIR . '/build/dashboard/blocks/blocks-manifest.php'),
    );

    \do_action('init');

    // registered blocks (aka actual)
    $registered_block_names = array_keys(\WP_Block_Type_Registry::get_instance()->get_all_registered());
    $registered_block_names = array_filter($registered_block_names, fn($_) => str_starts_with($_, 'ionos-dashboard-page/'));

    $this->assertEqualsCanonicalizing($declared_dashboard_block_names, $registered_block_names, 'all dashboard blocks are registered.');
    // alternative
    // $this->assertSameSets($declared_dashboard_block_names, $registered_block_names, 'all dashboard blocks are registered.');
  }
}
