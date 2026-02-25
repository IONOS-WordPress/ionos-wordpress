<?php

/**
 * Maintenance Mode - Integration tests
 *
 * Verifies that activating and deactivating the stretch-extra maintenance mode
 * correctly changes the HTTP response code seen by web clients.
 *
 * Requires a running WordPress HTTP server (pnpm start).
 *
 * Run:   pnpm test:php --php-opts "--filter MaintenanceTest"
 * Group: pnpm test:php --php-opts "--group stretch-extra"
 */

use function ionos\stretch_extra\maintenance\activate;
use function ionos\stretch_extra\maintenance\deactivate;

class MaintenanceTest extends \WP_UnitTestCase
{
  public const TEST_URL = 'http://host.docker.internal:8889';

  public const HTTP_ARGS = [
    'timeout'   => 10,
    'sslverify' => false,
  ];

  protected function setUp(): void
  {
    parent::setUp();
    deactivate(); // ensure a clean state before each test
  }

  protected function tearDown(): void
  {
    deactivate(); // clean up sentinel file and symlink after each test
    parent::tearDown();
  }

  /**
   * Tests the full activate → request → deactivate → request lifecycle.
   *
   * Steps:
   * 1. Before activation  – site must return HTTP 200.
   * 2. Activate.
   * 3. During maintenance – site must return HTTP 503.
   * 4. Deactivate.
   * 5. After deactivation – site must return HTTP 200 again.
   */
  public function testHttpStatusChangesWithMaintenanceMode(): void
  {
    // 1. Before maintenance mode: site is accessible.
    $response = \wp_remote_get(self::TEST_URL, self::HTTP_ARGS);
    $this->assertNotWPError($response, 'HTTP request before activation should not return a WP_Error');
    $this->assertSame(
      200,
      (int) \wp_remote_retrieve_response_code($response),
      'Site should return HTTP 200 before maintenance mode is activated'
    );

    // 2. Activate maintenance mode.
    $this->assertTrue(activate(), 'activate() should return true');

    // 3. During maintenance mode: site returns 503.
    $response = \wp_remote_get(self::TEST_URL, self::HTTP_ARGS);
    $this->assertNotWPError($response, 'HTTP request during maintenance should not return a WP_Error');
    $this->assertSame(
      503,
      (int) \wp_remote_retrieve_response_code($response),
      'Site should return HTTP 503 while maintenance mode is active'
    );

    // 4. Deactivate maintenance mode.
    $this->assertTrue(deactivate(), 'deactivate() should return true');

    // 5. After deactivation: site is accessible again.
    $response = \wp_remote_get(self::TEST_URL, self::HTTP_ARGS);
    $this->assertNotWPError($response, 'HTTP request after deactivation should not return a WP_Error');
    $this->assertSame(
      200,
      (int) \wp_remote_retrieve_response_code($response),
      'Site should return HTTP 200 after maintenance mode is deactivated'
    );
  }
}
