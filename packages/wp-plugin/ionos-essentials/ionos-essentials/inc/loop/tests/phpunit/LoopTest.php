<?php

namespace ionos\essentials\loop;

use WP_REST_Server;

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

    // Initiating the REST API.
    global $wp_rest_server;
    $this->server = $wp_rest_server = new \WP_REST_Server;

    \activate_plugin('ionos-essentials/ionos-essentials.php');

    // @TODO: for some reason the once required rest_api_init hooks are cleaned up at this point when all phpunit tests are runned.
    \add_action('rest_api_init', function () {
      \register_rest_route(
        IONOS_LOOP_REST_NAMESPACE,
        IONOS_LOOP_REST_ENDPOINT,
        [
          'methods'             => WP_REST_Server::READABLE,
          'permission_callback' => __NAMESPACE__ . '\_rest_permissions_check',
          'callback'            => __NAMESPACE__ . '\_rest_loop_data',
        ]
      );
    });

    \do_action( 'rest_api_init' );
  }

  public function test_rest_loop_endpoint() : void {
    \wp_set_current_user($this->admin_user_id);

    $routes = $this->server->get_routes();
    ksort($routes);

    // try to request the loop datacollector endpoint
    $response = $this->server->dispatch(new \WP_REST_Request('GET', '/' . IONOS_LOOP_REST_NAMESPACE . IONOS_LOOP_REST_ENDPOINT));
    $this->assertEquals(200, $response->status, 'loop endpoint returned 200');

    $json = $response->data;
    // enhance rest data similar to datacollector behavior
    $json['timestamp'] = time(); // set timestamp for schema validation
    $json['instance'] = '75b2707e45c147ea74e32c677ae7b12316acbdc37a70faa63cc2b675b53c3b6d'; // set fixed instance ID for schema validation

    $schema = json_decode(file_get_contents(__DIR__ . './../../schema.json'), true);

    $validation = \rest_validate_value_from_schema($json, $schema);
    $this->assertTrue($validation, 'data returned by loop endpoint matches schema');
  }
}
