<?php

namespace ionos\essentials\jetpack_flow\Controllers;

use Automatic_Upgrader_Skin;
use Plugin_Upgrader;
use const ionos\essentials\jetpack_flow\INSTALL_JETPACK_OPTION_NAME;
use const ionos\essentials\jetpack_flow\VIEWS_DIR_PATH;

require_once ABSPATH . 'wp-admin/includes/file.php';
require_once ABSPATH . 'wp-admin/includes/plugin.php';
require_once ABSPATH . 'wp-admin/includes/plugin-install.php';
require_once ABSPATH . 'wp-admin/includes/class-wp-upgrader.php';
require_once ABSPATH . 'wp-admin/includes/class-plugin-upgrader.php';

class Install implements ViewController {
	const JETPACK_PLUGIN_FILE         = 'jetpack/jetpack.php';

	public static function setup() {
		$option_value = get_option( INSTALL_JETPACK_OPTION_NAME );

		if ( false === $option_value ) {
			add_action(
				'admin_head',
				function() {
					echo '<meta http-equiv="refresh" content="5">';
				}
			);

			update_option( INSTALL_JETPACK_OPTION_NAME, 0 );
			return;
		}

		if ( '0' === $option_value ) {
			if ( ! self::is_plugin_installed( 'jetpack' ) ) {
				self::install();
			}
			activate_plugin( self::JETPACK_PLUGIN_FILE );

			delete_option( INSTALL_JETPACK_OPTION_NAME );
			wp_redirect( add_query_arg( 'jetpack-partner-coupon', $_GET['coupon'], admin_url() ) );
			exit;
		}
	}

	public static function render() {
		load_template( __DIR__ . '/../views/install.php', true );
	}

	public static function get_page_title() {
		return __( 'Installing Jetpack', 'ionos-assistant' );
	}

	private static function install() {
		// Install from repo
		$api = plugins_api(
			'plugin_information',
			array(
				'slug'   => 'jetpack',
				'fields' => array( 'downloadlink' => true ),
			)
		);

		if ( is_wp_error( $api ) ) {
			return false;
		}

		// Ignore failures on accessing SSL "https://api.wordpress.org/plugins/update-check/1.1/" in `wp_update_plugins()` which seem to occur intermittently.
		set_error_handler( null, E_USER_WARNING | E_USER_NOTICE );

		$plugin_upgrader = new Plugin_Upgrader( new Automatic_Upgrader_Skin() );
		$installed       = $plugin_upgrader->install( $api->download_link );
		return $installed;
	}

	private static function is_plugin_installed( $plugin_slug ) {
		$installed_plugins = get_plugins();

		foreach ( $installed_plugins as $plugin_path => $wp_plugin_data ) {
			if ( explode( '/', $plugin_path )[0] === $plugin_slug ) {
				return true;
			}
		}

		return false;
	}
}
