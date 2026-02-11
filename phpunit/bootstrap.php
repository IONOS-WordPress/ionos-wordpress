<?php
$WP_TESTS_DIR = getenv('WP_TESTS_DIR');

// Forward custom PHPUnit Polyfills configuration to PHPUnit bootstrap file.
$_phpunit_polyfills_path = getenv('WP_TESTS_PHPUNIT_POLYFILLS_PATH');
if (false !== $_phpunit_polyfills_path) {
  define('WP_TESTS_PHPUNIT_POLYFILLS_PATH', $_phpunit_polyfills_path);
}

{
  /*
   * This bootstrap file is responsible for setting up the testing environment for the APCu object cache tests.
   */

  $target_drop_in = '/var/www/html/wp-content/object-cache.php';
  $apcu_object_cache = '/var/www/html/wp-content/mu-plugins/stretch-extra/inc/apcu/object-cache.php';

  // Check if we are in the specific process that needs the cache
  if ( getenv('PHPUNIT_INSTALL_APCU_OBJECT_CACHE') === '1' ) {
    copy($apcu_object_cache, $target_drop_in);

    // Clean up immediately after this specific process exits
    register_shutdown_function(function() use ($target_drop_in) {
      if (file_exists($target_drop_in)) {
        unlink($target_drop_in);
      }
    });
  } else {
    // Ensure the file is NOT there for all other tests
    if (file_exists($target_drop_in)) {
      unlink($target_drop_in);
    }
  }
}
require_once getenv('HOME') . '/.composer/vendor/autoload.php';

// Give access to tests_add_filter() function.
require_once $WP_TESTS_DIR . '/includes/functions.php';

// /**
//  * Manually load the plugin being tested.
//  */
// function _manually_load_plugin() {
// 	require dirname( dirname( __FILE__ ) ) . '/starter-plugin.php';
// }

// tests_add_filter( 'muplugins_loaded', '_manually_load_plugin' );

// Start up the WP testing environment.
require $WP_TESTS_DIR . '/includes/bootstrap.php';
