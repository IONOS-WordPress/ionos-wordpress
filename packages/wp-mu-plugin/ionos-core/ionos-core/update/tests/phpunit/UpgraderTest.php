<?php

namespace ionos\ionos_core;

/**
 * covers the directory swap of install_update(). each test works on a temporary directory standing in for mu-plugins/.
 *
 * run only this test using 'pnpm test:php --php-opts "--filter UpgraderTest"'
 *
 * @group ionos-core
 */
class UpgraderTest extends \WP_UnitTestCase {
  private string $dir = '';
  private string $package_dir = '';
  private \WP_Filesystem_Base $filesystem;

  public function setUp(): void {
    parent::setUp();

    require_once ABSPATH . 'wp-admin/includes/file.php';
    \WP_Filesystem();
    $this->filesystem = $GLOBALS['wp_filesystem'];

    $root              = sys_get_temp_dir() . '/ionos-core-upgrader-' . uniqid();
    $this->dir         = $root . '/mu-plugins';
    $this->package_dir = $root . '/package';
    mkdir($this->dir, 0777, true);
    mkdir($this->package_dir, 0777, true);
  }

  public function tearDown(): void {
    $this->filesystem->delete(dirname($this->dir), true);

    parent::tearDown();
  }

  private static function loader_source(string $version): string {
    return "<?php\n/**\n * Plugin Name: Ionos Core\n * Version: {$version}\n */\n";
  }

  /*
   * writes ionos-core.php and ionos-core/ of $version into $dir, plus the extra files of ionos-core/ in $files
   */
  private static function write_plugin(string $dir, string $version, array $files = []): void {
    file_put_contents($dir . '/ionos-core.php', self::loader_source($version));
    if (! is_dir($dir . '/ionos-core')) {
      mkdir($dir . '/ionos-core');
    }
    file_put_contents($dir . '/ionos-core/index.php', "<?php // {$version}\n");
    foreach ($files as $file) {
      file_put_contents($dir . '/ionos-core/' . $file, $version);
    }
  }

  private function assert_installed(string $version): void {
    $this->assertSame(self::loader_source($version), file_get_contents($this->dir . '/ionos-core.php'));
    $this->assertSame("<?php // {$version}\n", file_get_contents($this->dir . '/ionos-core/index.php'));
  }

  private function assert_no_leftovers(): void {
    $this->assertSame(['ionos-core', 'ionos-core.php'], array_values(array_diff(scandir($this->dir), ['.', '..'])));
  }

  public function test_update_replaces_directory_and_loader(): void {
    self::write_plugin($this->dir, '1.0.0', ['removed-in-1.0.1.php']);
    self::write_plugin($this->package_dir, '1.0.1', ['added-in-1.0.1.php']);

    $result = install_update($this->package_dir, $this->dir, $this->filesystem);

    $this->assertTrue($result);
    $this->assert_installed('1.0.1');
    $this->assertFileExists($this->dir . '/ionos-core/added-in-1.0.1.php');
    $this->assertFileDoesNotExist($this->dir . '/ionos-core/removed-in-1.0.1.php');
    $this->assert_no_leftovers();
  }
}
