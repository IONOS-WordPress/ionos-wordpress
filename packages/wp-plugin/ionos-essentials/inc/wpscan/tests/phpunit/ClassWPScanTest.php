<?php

use ionos\essentials\wpscan\WPScan;
use ionos\essentials\wpscan\WPScanMiddleware;
use const ionos\essentials\PLUGIN_DIR;

/**
 * covers the tests for WPScan
 *
 * run only this test using 'pnpm test:php --php-opts "--filter ClassNBATest"'
 *
 * @group dashboard
 * @group essentials
 */
class ClassWPScanTest extends \WP_UnitTestCase {

  public function setUp(): void {
    // ensure that post types and taxonomies are reset for each test.
    if (!defined('WP_RUN_CORE_TESTS')) {
      define('WP_RUN_CORE_TESTS', true);
    }

    parent::setUp();

    \activate_plugin('ionos-essentials/ionos-essentials.php');

    require_once PLUGIN_DIR . '/inc/wpscan/controller/class-wpscan.php';
    require_once PLUGIN_DIR . '/inc/wpscan/controller/class-wpscanmiddleware.php';
  }

  public function test_data_conversion() : void {
    $wp_scan = new WPScanMiddleware();
    $data = [
      'plugins' => [
        [
          'slug' => 'test-plugin',
          'version' => '1.0.0',
          'vulnerabilities' => [
            [
              'id' => 'CVE-2023-1234',
              'score' => 7.5,
              'fixed_in' => '1.0.1',
            ],
            [
              'id' => 'CVE-2023-5678',
              'score' => 4.0,
            ],
          ],
        ],
      ],
      'themes' => [
        [
          'slug' => 'test-theme',
          'version' => '1.0.0',
          'vulnerabilities' => [
            [
              'id' => 'CVE-2023-4321',
              'score' => 6.0,
              'fixed_in' => '1.0.1',
            ],
            [
              'id' => 'CVE-2023-8765',
              'score' => 4.0,
            ],
            [
              'id' => 'CVE-2023-9999',
              'score' => 3.0,
            ],
          ],
        ],
      ],
    ];
    $converted_data = $wp_scan->convert_middleware_data($data);

    $expected = [
      [
        'name'   => 'test-plugin',
        'slug'   => 'test-plugin',
        'path'   => 'test-plugin/test-plugin.php',
        'type'   => 'plugin',
        'update' => null,
        'score'  => 7.5,
      ],
      [
        'name'   => 'test-theme',
        'slug'   => 'test-theme',
        'path'   => 'test-theme/test-theme.php',
        'type'   => 'theme',
        'update' => null,
        'score'  => 6.0,
      ],
    ];
    $this->assertEquals($expected, $converted_data);
  }

  public function test_sending_email() : void {
    update_option('IONOS_SECURITY_FEATURE_OPTION', ['IONOS_SECURITY_FEATURE_OPTION_MAIL_NOTIFY' => true]);
    set_transient('ionos_wpscan_issues', [
      [
        'name'   => 'Essentials Mock',
        'slug'   => 'ionos-essentials',
        'path'   => 'ionos-essentials/ionos-essentials.php',
        'type'   => 'plugin',
        'update' => null,
        'score'  => 9.0,
      ]
    ]);

    $wp_scan = new WPScan();

    $result = $wp_scan->maybe_send_email();
    $this->assertTrue($result);

    // Do not send twice
    $result = $wp_scan->maybe_send_email();
    $this->assertFalse($result);
  }
}
