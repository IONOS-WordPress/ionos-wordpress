<?php

/**
 * Plugin Name:       IONOS Core
 * Description:       The IONOS Core plugin provides hosting specific must use functionality.
 * Requires at least: 6.6
 * Requires Plugins:
 * Requires PHP:      8.3
 * Version:           1.0.0
 * Plugin URI:        https://github.com/IONOS-WordPress/ionos-wordpress/tree/main/packages/wp-mu-plugin/ionos-core
 * License:           GPL-2.0-or-later
 * Author:            IONOS Group
 * Author URI:        https://www.ionos-group.com/brands.html
 * Domain Path:       /ionos-core/languages
 * Text Domain:       ionos-core
 */

namespace ionos\core;

defined('ABSPATH') || exit();

error_log('IONOS Core MU Plugin loaded.');

// Define custom plugins directory
define('IONOS_CUSTOM_PLUGINS_PATH', 'ionos-core/custom-plugins/');
define('IONOS_CUSTOM_PLUGINS_DIR', __DIR__ . '/' . IONOS_CUSTOM_PLUGINS_PATH);
define('IONOS_CUSTOM_PLUGINS_URL', \plugins_url(IONOS_CUSTOM_PLUGINS_PATH, __FILE__));

/**
 * Load custom plugins directly
 */
\add_action('muplugins_loaded', function () {
	if (!is_dir(IONOS_CUSTOM_PLUGINS_DIR)) {
		return;
	}

	$plugin_dirs = glob(IONOS_CUSTOM_PLUGINS_DIR . '*', GLOB_ONLYDIR);

	foreach ($plugin_dirs as $plugin_dir) {
		$plugin_slug = basename($plugin_dir);
		$plugin_files = glob($plugin_dir . '/*.php');

		foreach ($plugin_files as $plugin_file) {
			if (file_exists($plugin_file)) {
				// Check if it's a valid plugin file by looking for plugin headers
				$plugin_data = \get_file_data($plugin_file, ['Name' => 'Plugin Name']);
				if (!empty($plugin_data['Name'])) {
					// Load the plugin file directly
					include_once $plugin_file;
					error_log('IONOS Core: Loaded custom plugin: ' . $plugin_slug . '/' . basename($plugin_file));
					break; // Only load one main plugin file per directory
				}
			}
		}
	}
}, 1);

/**
 * Register custom plugin directory URL handling
 */
\add_filter('plugins_url', function ($url, $path, $plugin) {
	// Check if the plugin file is from our custom plugins directory
	if (str_starts_with($plugin, IONOS_CUSTOM_PLUGINS_DIR)) {
		// Get the plugin directory (without the main plugin file)
		$plugin_relative = str_replace(IONOS_CUSTOM_PLUGINS_DIR, '', $plugin);
		// Remove the plugin filename, keeping only the directory path
		$plugin_dir = dirname($plugin_relative);

		// If path is empty, the plugin is requesting its own directory
		if (empty($path)) {
			return IONOS_CUSTOM_PLUGINS_URL . $plugin_dir;
		}

		// Otherwise, append the path to the plugin directory
		return IONOS_CUSTOM_PLUGINS_URL . $plugin_dir . '/' . ltrim($path, '/');
	}
	return $url;
}, 10, 3);

/**
 * Show custom plugins in the admin plugins list (read-only, can't be deactivated)
 */
\add_filter('all_plugins', function ($plugins) {
	if (!is_dir(IONOS_CUSTOM_PLUGINS_DIR)) {
		return $plugins;
	}

	$plugin_dirs = glob(IONOS_CUSTOM_PLUGINS_DIR . '*', GLOB_ONLYDIR);

	foreach ($plugin_dirs as $plugin_dir) {
		$plugin_slug = basename($plugin_dir);
		$plugin_files = glob($plugin_dir . '/*.php');

		foreach ($plugin_files as $plugin_file) {
			if (file_exists($plugin_file)) {
				// Check if it's a valid plugin file
				$plugin_data = \get_plugin_data($plugin_file, false, false);
				if (!empty($plugin_data['Name'])) {
					$plugin_key = IONOS_CUSTOM_PLUGINS_PATH . $plugin_slug . '/' . basename($plugin_file);
					if (!isset($plugins[$plugin_key])) {
						$plugins[$plugin_key] = $plugin_data;
						$plugins[$plugin_key]['Description'] = $plugin_data['Description'] . ' <strong>(Loaded from custom directory)</strong>';
					}
					break;
				}
			}
		}
	}

	return $plugins;
}, 999);

/**
 * Prevent deactivation, activation, and deletion of custom plugins
 */
\add_filter('plugin_action_links', function ($actions, $plugin_file) {
	if (str_starts_with($plugin_file, IONOS_CUSTOM_PLUGINS_PATH)) {
		// Remove activate, deactivate, and delete links since they're must-use plugins
		unset($actions['activate']);
		unset($actions['deactivate']);
		unset($actions['delete']);
		// Add a notice that it's a must-use plugin
		$actions['must_use'] = '<span style="color: #999;">ionos-core provisioned plugin</span>';
	}
	return $actions;
}, 10, 2);

/**
 * Bypass plugin file existence check during activation
 */
\add_action('admin_init', function () {
	if (isset($_GET['action']) && $_GET['action'] === 'activate' && isset($_GET['plugin'])) {
		$plugin = \wp_unslash($_GET['plugin']);
		if (str_starts_with($plugin, IONOS_CUSTOM_PLUGINS_PATH)) {
			// Redirect back without error since plugin is already loaded
			\wp_redirect(\admin_url('plugins.php?activate=true'));
			exit;
		}
	}
});

