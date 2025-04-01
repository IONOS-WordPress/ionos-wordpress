<?php

use ionos_wordpress\essentials\dashboard\blocks\next_best_actions\NBA;
use ionos_wordpress\essentials\dashboard\blocks\next_best_actions\ActionStatus;

use const ionos_wordpress\essentials\PLUGIN_DIR;

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

    require_once PLUGIN_DIR . '/inc/dashboard/blocks/next-best-actions/class-nba.php';
  }

  function test_nba_action() : void {
    $this->assertNotNull(NBA::getActions());
    $nba_count = count(NBA::getActions());

    $ID = 'my-test-action';

    NBA::register(
      id: $ID,
      title: 'Test title',
      description: 'Test description',
      link: 'https://example.com',
      anchor: 'Test CTA',
      completed : false,
    );

    $new_nba_count = count(NBA::getActions());
    $this->assertEquals($nba_count + 1, $new_nba_count, 'registered nbas should contain our newly radded nba');

    $nba = NBA::getNBA($ID);
    $this->assertTrue($nba->active);

    $nba->setStatus(ActionStatus::completed, true);
    $this->assertFalse($nba->active);
  }

  function test_nba_status() : void {
    $ID = 'my-test-action-2';

    NBA::register(
      id: $ID,
      title: 'Test title 2',
      description: 'Test description 2',
      link: 'https://example.com',
      anchor: 'Test CTA 2',
      completed : false,
    );

    $nba = NBA::getNBA($ID);
    $this->assertTrue($nba->active);
    $nba->setStatus(ActionStatus::dismissed, true);
    $this->assertFalse($nba->active);
  }

  function test_nba_not_active_by_registration() : void {
    $ID = 'my-test-action-3';

    NBA::register(
      id: $ID,
      title: 'Test title 3',
      description: 'Test description 3',
      link: 'https://example.com',
      anchor: 'Test CTA 3',
      completed : true,
    );

    $nba = NBA::getNBA($ID);
    $this->assertFalse($nba->active);
  }
}
