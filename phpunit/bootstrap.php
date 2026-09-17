<?php
$WP_TESTS_DIR = getenv('WP_TESTS_DIR');

// Forward custom PHPUnit Polyfills configuration to PHPUnit bootstrap file.
$_phpunit_polyfills_path = getenv('WP_TESTS_PHPUNIT_POLYFILLS_PATH');
if (false !== $_phpunit_polyfills_path) {
  define('WP_TESTS_PHPUNIT_POLYFILLS_PATH', $_phpunit_polyfills_path);
}

// composer deps (phpunit/phpunit, yoast/phpunit-polyfills) are baked into the
// wordpress-alpine image at /opt/wp-tests (see packages/docker/wordpress-alpine/Dockerfile)
require_once '/opt/wp-tests/vendor/autoload.php';

// Give access to tests_add_filter() function.
require_once $WP_TESTS_DIR . '/includes/functions.php';

/**
 * load the workspace plugins before the first test runs.
 *
 * tests activate the plugin they cover in their own setUp(), but activate_plugin() is the point
 * where the plugin file is first required - and that happens after WP_UnitTestCase has snapshotted
 * the hooks. The restore in tearDown() then drops every hook the plugin registered, and require_once
 * will not run the file again, so from the second test on the plugin is effectively inert: routes
 * registered on 'rest_api_init' are no longer dispatchable.
 *
 * loading the plugins here puts their hooks in place before any snapshot is taken.
 */
function _ionos_load_workspace_plugins()
{
  foreach (glob(WP_PLUGIN_DIR . '/*', GLOB_ONLYDIR) as $plugin_dir) {
    $plugin_file = $plugin_dir . '/' . basename($plugin_dir) . '.php';

    if (is_readable($plugin_file)) {
      require_once $plugin_file;
    }
  }
}

tests_add_filter('muplugins_loaded', '_ionos_load_workspace_plugins');

// Start up the WP testing environment.
require $WP_TESTS_DIR . '/includes/bootstrap.php';
