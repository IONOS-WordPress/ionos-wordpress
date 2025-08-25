<?php

namespace ionos\essentials\loop\data\core;

use Ionos\Library\Options;
use ionos\essentials\loop\data\DataProvider;
use function ionos\essentials\loop\normalize_version_string;

/**
 * Data provider for posts and any post types.
 */
class GenericData extends DataProvider {

	/**
	 * Collects all generic data.
	 *
	 * @return array
	 */
	protected function collect_data() {
		$data['locale']                = get_locale();
		$data['blog_public']           = (bool) get_option( 'blog_public' );
		$data['market']                = $this->get_market();
		$data['tenant']                = $this->get_tenant();
		$data['is_customer_domain']    = $this->is_customer_domain();
		$data['core_version']          = $this->get_core_version();
		$data['php_version']           = normalize_version_string( $this->get_php_version() );
		$data['installed_themes']      = $this->get_installed_themes();
		$data['installed_plugins']     = $this->get_installed_plugins();
		$data['active_plugins']        = $this->get_active_installed_plugins();
		$data['month_of_installation'] = $this->get_month_of_installation();

		return $data;
	}

	/**
	 * Gets the month of installation of the current blog.
	 * Will use the month the first user was created.
	 *
	 * @return string
	 */
	private function get_month_of_installation() {
		$first_user        = get_users( [ 'number' => 1 ] );
		$registration_date = $first_user[0]->user_registered;

		return gmdate( 'Y-m', strtotime( $registration_date ) );
	}

	/**
	 * Gets the number themes installed on the current blog.
	 *
	 * @return int
	 */
	private function get_installed_themes() {
		return count( wp_get_themes() );
	}

	/**
	 * Gets the number of plugins installed on the current blog.
	 *
	 * @return int
	 */
	private function get_installed_plugins() {
		require_once ABSPATH . 'wp-admin/includes/plugin.php';
		return count( get_plugins() );
	}

	/**
	 * Gets the number of active plugins on the current blog.
	 *
	 * @return int
	 */
	private function get_active_installed_plugins() {
		return count( get_option( 'active_plugins' ) );
	}

	/**
	 * Gets the Market of the current blog.
	 *
	 * Depends on IONOS Library.
	 *
	 * @return string
	 */
	private function get_market() {
		return Options::get_market();
	}

	/**
	 * Gets the Tenant of the current blog.
	 *
	 * Depends on IONOS Library.
	 *
	 * @return string
	 */
	private function get_tenant() {
		return Options::get_tenant_name();
	}

	/**
	 * Gets the core version of the current blog.
	 *
	 * @return string
	 */
	private function get_core_version() {
		// Include an unmodified $wp_version.
		require ABSPATH . WPINC . '/version.php';

		return $wp_version;
	}

	/**
	 * Gets the current PHP version.
	 *
	 * @return string
	 */
	private function get_php_version() {
		return phpversion();
	}

	/**
	 * Checks if the domain identifies as customer domain or as generic domain.
	 *
	 * @return bool
	 */
	private function is_customer_domain() {
		$valid_domains = apply_filters(
			'ionos_loop_non_customer_domains',
			[
				'apps-1and1.net',
				'apps-1and1.com',
				'online.de',
				'live-website.com',
			]
		);

		array_walk( $valid_domains, 'preg_quote' );

		switch ( preg_match( '#^[a-z0-9\-\.]+\.(?:' . implode( '|', $valid_domains ) . ')$#i', wp_parse_url( get_home_url(), PHP_URL_HOST ) ) ) {
			case 1:
				return false;
			case 0:
				return true;
			case false:
				_doing_it_wrong( __FUNCTION__, 'Regular Expression malformed', '0.0.1' );
				return '-';
		}
	}
}
