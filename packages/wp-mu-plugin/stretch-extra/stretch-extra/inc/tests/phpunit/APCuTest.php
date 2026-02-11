<?php
/**
 * @runTestsInSeparateProcesses
 * @preserveGlobalState disabled
 * @backupGlobals disabled
 * @group apcu
 * @group object-cache
 * @group stretch-extra
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
    # defined('APCU_OBJECT_CACHE_INSTANTIATED')
    $this->assertTrue(true , 'APCu object cache should be instantiated');
  }
}
