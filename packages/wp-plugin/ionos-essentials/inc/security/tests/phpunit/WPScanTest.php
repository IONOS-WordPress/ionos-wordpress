<?php
/**
 * Base tests for WPScan.
 *
 * @package Ionos_Security
 */

// namespace Ionos\Security;

// use Ionos\Security\Controllers\WPScan;
// use WP_Mock\Tools\TestCase;
// use WP_Mock;

/**
 * Tests for WPScan.
 */
//class WPScanTest extends TestCase {


	/**
	 * Getter should return the correct values.
	 */
	/*public function test_plugin_getter() {
		WP_Mock::userFunction(
			'get_plugin_data',
			[
				'return' => [
					'Name'    => 'ionos-security',
					'Version' => '1.0.0',
				],
			]
		);

		WP_Mock::userFunction(
			'plugin_basename',
			[
				'return' => 'ionos-security/ionos-security.php',
			]
		);

		WP_Mock::userFunction(
			'get_option',
			[
				'return' => json_decode(
					'{"result":
					{"core":{"state":"KNOWN","vulnerabilities":[],"last_update":"2024-04-21T11:12:04.910693Z"},"plugins":[{"slug":"ionos-security","state":"KNOWN","vulnerabilities":[],"requested_version":"1.0.0"},{"slug":"transients-manager","state":"KNOWN","vulnerabilities":[],"requested_version":"2.0.5","latest_version":"2.0.5","last_update":"2024-04-22T08:57:10.499397Z"}],"themes":[{"slug":"twentytwentyfour","state":"KNOWN","vulnerabilities":[],"requested_version":"1.1","latest_version":"1.1","last_update":"2024-04-22T08:57:10.496425Z"},{"slug":"twentytwentythree","state":"KNOWN","vulnerabilities":[],"requested_version":"1.4","latest_version":"1.4","last_update":"2024-04-22T08:57:10.493295Z"},{"slug":"twentytwentytwo","state":"KNOWN","vulnerabilities":[],"requested_version":"1.7","latest_version":"1.7","last_update":"2024-04-22T08:57:10.489439Z"}]}
			}',
					true
				) ,
			]
		);

		WP_Mock::userFunction(
			'set_transient',
			[
				'return_arg' => 1,
			]
		);

		WP_Mock::userFunction(
			'is_plugin_active',
			[
				'return' => true,
			]
		);

		$wpscan = new WPScan();

		$plugin = $wpscan->get_plugin( 'ionos-security' );
		$this->assertArrayHasKey( 'name', $plugin );
		$this->assertArrayHasKey( 'slug', $plugin );
		$this->assertArrayHasKey( 'score', $plugin );
		$this->assertArrayHasKey( 'details', $plugin );
		$this->assertIsInt( $plugin['score'] );
	}
}*/
