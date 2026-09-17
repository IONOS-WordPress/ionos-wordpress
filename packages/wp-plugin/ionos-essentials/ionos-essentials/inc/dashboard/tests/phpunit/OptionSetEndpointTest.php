<?php

/**
 * covers the access control and the option allowlist of the
 * `ionos/essentials/option/set` REST endpoint.
 *
 * run only this test using 'pnpm test:php --php-opts "--filter OptionSetEndpointTest"'
 *
 * @group dashboard
 * @group essentials
 */

use function ionos\essentials\dashboard\sanitize_option_value;
use const ionos\essentials\dashboard\ALLOWED_TOP_LEVEL_OPTIONS;
use const ionos\essentials\security\IONOS_SECURITY_FEATURE_OPTION;
use const ionos\essentials\security\IONOS_SECURITY_FEATURE_OPTION_XMLRPC;

class OptionSetEndpointTest extends \WP_UnitTestCase
{
  private const ROUTE = '/ionos/essentials/option/set';

  protected function setUp(): void
  {
    // ensure that post types and taxonomies are reset for each test.
    if (! defined('WP_RUN_CORE_TESTS')) {
      define('WP_RUN_CORE_TESTS', true);
    }

    parent::setUp();

    \activate_plugin('ionos-essentials/ionos-essentials.php');

    global $wp_rest_server;
    $wp_rest_server = new \WP_REST_Server();
    \do_action('rest_api_init');
  }

  private function request(array $data): \WP_REST_Response
  {
    $request = new \WP_REST_Request('POST', self::ROUTE);
    $request->set_header('content-type', 'application/json');
    $request->set_body(wp_json_encode($data));

    return \rest_get_server()->dispatch($request);
  }

  public function test_subscriber_cannot_set_any_option(): void
  {
    \wp_set_current_user(self::factory()->user->create([
      'role' => 'subscriber',
    ]));

    $response = $this->request([
      'key'   => 'default_role',
      'value' => 'administrator',
    ]);

    // an authenticated but unprivileged user gets 403, an anonymous one 401
    $this->assertSame(403, $response->get_status());
    $this->assertSame('subscriber', \get_option('default_role'));
  }

  public function test_logged_out_user_cannot_set_any_option(): void
  {
    \wp_set_current_user(0);

    $response = $this->request([
      'key'   => 'users_can_register',
      'value' => 1,
    ]);

    $this->assertSame(401, $response->get_status());
  }

  public function test_administrator_cannot_set_an_option_outside_the_allowlist(): void
  {
    \wp_set_current_user(self::factory()->user->create([
      'role' => 'administrator',
    ]));

    $response = $this->request([
      'key'   => 'users_can_register',
      'value' => 1,
    ]);

    $this->assertSame(400, $response->get_status());
    $this->assertFalse((bool) \get_option('users_can_register'));
  }

  public function test_administrator_cannot_set_an_unknown_key_of_the_security_option(): void
  {
    \wp_set_current_user(self::factory()->user->create([
      'role' => 'administrator',
    ]));

    $response = $this->request([
      'option' => IONOS_SECURITY_FEATURE_OPTION,
      'key'    => 'SOME_UNKNOWN_KEY',
      'value'  => 1,
    ]);

    $this->assertSame(400, $response->get_status());
    $this->assertArrayNotHasKey('SOME_UNKNOWN_KEY', \get_option(IONOS_SECURITY_FEATURE_OPTION, []));
  }

  public function test_administrator_cannot_redirect_a_security_key_into_another_option(): void
  {
    \wp_set_current_user(self::factory()->user->create([
      'role' => 'administrator',
    ]));

    $response = $this->request([
      'option' => 'active_plugins',
      'key'    => IONOS_SECURITY_FEATURE_OPTION_XMLRPC,
      'value'  => 1,
    ]);

    $this->assertSame(400, $response->get_status());
  }

  public function test_administrator_can_set_an_allowlisted_top_level_option(): void
  {
    \wp_set_current_user(self::factory()->user->create([
      'role' => 'administrator',
    ]));

    $response = $this->request([
      'key'   => 'ionos_essentials_maintenance_mode',
      'value' => 1,
    ]);

    $this->assertSame(200, $response->get_status());
    $this->assertSame(1, (int) \get_option('ionos_essentials_maintenance_mode'));
  }

  public function test_administrator_can_set_an_allowlisted_security_feature_key(): void
  {
    \wp_set_current_user(self::factory()->user->create([
      'role' => 'administrator',
    ]));

    $response = $this->request([
      'option' => IONOS_SECURITY_FEATURE_OPTION,
      'key'    => IONOS_SECURITY_FEATURE_OPTION_XMLRPC,
      'value'  => 0,
    ]);

    $this->assertSame(200, $response->get_status());

    $options = \get_option(IONOS_SECURITY_FEATURE_OPTION);
    $this->assertSame(0, $options[IONOS_SECURITY_FEATURE_OPTION_XMLRPC]);
  }

  public function test_a_non_array_security_option_is_replaced_by_the_defaults(): void
  {
    \wp_set_current_user(self::factory()->user->create([
      'role' => 'administrator',
    ]));

    \update_option(IONOS_SECURITY_FEATURE_OPTION, 'not-an-array');

    $response = $this->request([
      'option' => IONOS_SECURITY_FEATURE_OPTION,
      'key'    => IONOS_SECURITY_FEATURE_OPTION_XMLRPC,
      'value'  => 0,
    ]);

    $this->assertSame(200, $response->get_status());

    $options = \get_option(IONOS_SECURITY_FEATURE_OPTION);
    $this->assertIsArray($options);
    $this->assertSame(0, $options[IONOS_SECURITY_FEATURE_OPTION_XMLRPC]);
  }

  public function test_a_bool_option_value_is_coerced_to_an_integer_flag(): void
  {
    \wp_set_current_user(self::factory()->user->create([
      'role' => 'administrator',
    ]));

    $response = $this->request([
      'key'   => 'ionos_essentials_dashboard_mode',
      'value' => '<script>alert(1)</script>',
    ]);

    $this->assertSame(200, $response->get_status());
    $this->assertSame(1, (int) \get_option('ionos_essentials_dashboard_mode'));
  }

  public function test_a_structured_value_is_rejected(): void
  {
    \wp_set_current_user(self::factory()->user->create([
      'role' => 'administrator',
    ]));

    $response = $this->request([
      'key'   => 'ionos_essentials_dashboard_mode',
      'value' => [
        'nope' => true,
      ],
    ]);

    $this->assertSame(400, $response->get_status());
  }

  /**
   * every allowlisted type must be handled by sanitize_option_value().
   *
   * @dataProvider provide_option_values
   */
  public function test_sanitize_option_value(string $type, mixed $value, mixed $expected): void
  {
    $this->assertSame($expected, sanitize_option_value($value, $type));
  }

  public static function provide_option_values(): array
  {
    return [
      'bool truthy'     => ['bool', 'yes', 1],
      'bool falsy'      => ['bool', 0, 0],
      'int numeric'     => ['int', '42', 42],
      'int invalid'     => ['int', 'abc', null],
      'float numeric'   => ['float', '1.5', 1.5],
      'float invalid'   => ['float', 'abc', null],
      'string plain'    => ['string', ' hello ', 'hello'],
      'string stripped' => ['string', '<b>hi</b>', 'hi'],
      'string invalid'  => ['string', 7, null],
      'unknown type'    => ['datetime', 'now', null],
    ];
  }

  public function test_every_allowlisted_type_is_supported(): void
  {
    foreach (ALLOWED_TOP_LEVEL_OPTIONS as $option => $type) {
      $this->assertNotNull(
        sanitize_option_value('1', $type),
        sprintf('unsupported value type "%s" configured for option "%s"', $type, $option)
      );
    }
  }
}
