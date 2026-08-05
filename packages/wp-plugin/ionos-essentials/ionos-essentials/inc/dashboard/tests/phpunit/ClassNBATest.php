<?php

use ionos\essentials\dashboard\blocks\next_best_actions\NBA;

use const ionos\essentials\PLUGIN_DIR;

/**
 * covers the tests for the NBA class.
 *
 * run only this test using 'pnpm test:php --php-opts "--filter ClassNBATest"'
 *
 * @group dashboard
 * @group essentials
 */
class ClassNBATest extends \WP_UnitTestCase {

  public function setUp(): void {
    // ensure that post types and taxonomies are reset for each test.
    if (!defined('WP_RUN_CORE_TESTS')) {
      define('WP_RUN_CORE_TESTS', true);
    }

    parent::setUp();

    \activate_plugin('ionos-essentials/ionos-essentials.php');
    require_once PLUGIN_DIR . '/ionos-essentials/inc/dashboard/blocks/my-account/index.php';
    require_once PLUGIN_DIR . '/ionos-essentials/inc/dashboard/blocks/next-best-actions/class-nba.php';
  }

  public function test_nba_action() : void {
    $this->assertNotNull(NBA::get_actions());
    $nba_count = count(NBA::get_actions());

    $ID = 'my-test-action';

    // positional, not named, arguments: phpunit/ test files are bind-mounted
    // from source (never rector-transpiled) even under PHP_VERSION_OVERRIDE,
    // and named arguments are a PHP 8.0+ syntax feature - a parse error under
    // PHP_VERSION_OVERRIDE=7.4's real PHP 7.4 interpreter
    NBA::register($ID, 'Test title', 'Test description', 'https://example.com', 'Test CTA', false);

    $new_nba_count = count(NBA::get_actions());
    $this->assertEquals($nba_count + 1, $new_nba_count, 'registered nbas should contain our newly added nba');

    $nba = NBA::get_nba($ID);
    $this->assertTrue($nba->active);

    $nba->set_status('completed', true);
    $this->assertFalse($nba->active);
  }

  public function test_nba_status() : void {
    $ID = 'my-test-action-2';

    // positional, not named, arguments - see test_nba_action() above
    NBA::register($ID, 'Test title 2', 'Test description 2', 'https://example.com', 'Test CTA 2', false);

    $nba = NBA::get_nba($ID);
    $this->assertTrue($nba->active);
    $nba->set_status('dismissed', true);
    $this->assertFalse($nba->active);
  }

  public function test_nba_not_active_by_registration() : void {
    $ID = 'my-test-action-3';

    // positional, not named, arguments - see test_nba_action() above
    NBA::register($ID, 'Test title 3', 'Test description 3', 'https://example.com', 'Test CTA 3', true);

    $nba = NBA::get_nba($ID);
    $this->assertFalse($nba->active);
  }
}
