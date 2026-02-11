<?php
/**
 * @runTestsInSeparateProcesses
 * @preserveGlobalState disabled
 */
class APCuTest extends WP_UnitTestCase {

  /**
   * This tells PHPUnit to set the ENV var for the isolated process
   * @backupGlobals disabled
   */
  public static function setUpBeforeClass(): void {
      putenv('PHPUNIT_INSTALL_APCU_OBJECT_CACHE=1');
  }

  public function test_object_cache_is_loaded() {
      global $wp_object_cache;

      $this->assertTrue($wp_object_cache::APCU_OBJECT_CACHE_INSTANTIATED, 'APCu object cache should be instantiated');
  }
}
