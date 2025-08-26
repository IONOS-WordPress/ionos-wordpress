<?php

use ionos\essentials\loop;

use const ionos\essentials\loop\IONOS_LOOP_REST_ENDPOINT;
use const ionos\essentials\loop\IONOS_LOOP_REST_NAMESPACE;

/**
 * run only this test using 'pnpm test:php --php-opts "--filter LoopTest"'
 *
 * @group loop
 * @group essentials
 */
class LoopTest extends \WP_UnitTestCase  {
  private int $admin_user_id;
  private WP_REST_Server $server;

  public function setUp(): void {
    // ensure that post types and taxonomies are reset for each test.
    if (!defined('WP_RUN_CORE_TESTS')) {
      define('WP_RUN_CORE_TESTS', true);
    }

    parent::setUp();

    $this->admin_user_id = \WP_UnitTestCase_Base::factory()->user->create(['role' => 'administrator', 'user_login' => 'test-admin']);

    \activate_plugin('ionos-essentials/ionos-essentials.php');

    // Initiating the REST API.
    global $wp_rest_server;
    $this->server = $wp_rest_server = new \WP_REST_Server;
    \do_action( 'rest_api_init' );
  }

  public function test_rest_loop_endpoint() : void {
    \wp_set_current_user($this->admin_user_id);

    // try to request the loop datacollector endpoint
    $response = $this->server->dispatch(new \WP_REST_Request('GET', '/' . IONOS_LOOP_REST_NAMESPACE . IONOS_LOOP_REST_ENDPOINT));
    $this->assertEquals(200, $response->status, 'loop endpoint returned 200');

    $json = $response->data;

    $schema = json_decode(file_get_contents(__DIR__ . './../../schema.json'), true);

    $validation = \rest_validate_value_from_schema($json, $schema);
    $this->assertTrue($validation, 'data returned by loop endpoint matches schema');
  }
}
