<?php

namespace ionos\ionos_core;

/**
 * test double allowing to make selected renames fail
 */
class Failing_MU_Plugin_Upgrader extends MU_Plugin_Upgrader {
  /** @var string[] substrings of destination paths for which move() fails */
  public array $fail_on = [];

  /** @var ?callable(string, string): bool if set and returning true, move() fails */
  public $fail_when = null;

  protected function move(string $from, string $to): bool {
    if ($this->fail_when && ($this->fail_when)($from, $to)) {
      return false;
    }

    foreach ($this->fail_on as $needle) {
      if (str_contains($to, $needle)) {
        return false;
      }
    }

    return parent::move($from, $to);
  }
}

/**
 * covers ionos\ionos_core\MU_Plugin_Upgrader::install()
 *
 * run only this test using 'pnpm test:php --php-opts "--filter MuPluginUpgraderTest"'
 *
 * @group ionos-core
 */
class MuPluginUpgraderTest extends \WP_UnitTestCase {
  private string $base;
  private string $source;
  private string $target;

  public function setUp(): void {
    parent::setUp();

    \WP_Filesystem();

    $this->base   = \trailingslashit(sys_get_temp_dir()) . 'ionos-core-upgrader-' . uniqid();
    $this->source = $this->base . '/source/';
    $this->target = $this->base . '/mu-plugins/';

    // new version: contains a new file and lacks the stale one
    $this->write($this->source . 'ionos-core.php', 'new loader');
    $this->write($this->source . 'ionos-core/index.php', 'new index');
    $this->write($this->source . 'ionos-core/added.php', 'added');

    // installed (old) version
    $this->write($this->target . 'ionos-core.php', 'old loader');
    $this->write($this->target . 'ionos-core/index.php', 'old index');
    $this->write($this->target . 'ionos-core/stale/stale.php', 'stale');
    $this->write($this->target . 'other-plugin.php', 'untouched');
  }

  public function tearDown(): void {
    global $wp_filesystem;
    $wp_filesystem->delete($this->base, true);

    parent::tearDown();
  }

  private function write(string $path, string $content): void {
    is_dir(dirname($path)) || mkdir(dirname($path), 0777, true);
    file_put_contents($path, $content);
  }

  private function assertOldVersionIntact(): void {
    $this->assertSame('old loader', file_get_contents($this->target . 'ionos-core.php'));
    $this->assertSame('old index', file_get_contents($this->target . 'ionos-core/index.php'));
    $this->assertFileExists($this->target . 'ionos-core/stale/stale.php');
    $this->assertFileDoesNotExist($this->target . 'ionos-core/added.php');
    $this->assertSame('untouched', file_get_contents($this->target . 'other-plugin.php'));
  }

  private function assertNoLeftovers(): void {
    $this->assertSame(['ionos-core', 'ionos-core.php', 'other-plugin.php'], $this->entries());
  }

  private function entries(): array {
    $entries = array_values(array_diff(scandir($this->target), ['.', '..']));
    sort($entries);
    return $entries;
  }

  public function test_install_replaces_plugin_and_removes_stale_files(): void {
    $result = (new MU_Plugin_Upgrader())->install($this->source, $this->target);

    $this->assertTrue($result);
    $this->assertSame('new loader', file_get_contents($this->target . 'ionos-core.php'));
    $this->assertSame('new index', file_get_contents($this->target . 'ionos-core/index.php'));
    $this->assertFileExists($this->target . 'ionos-core/added.php');
    $this->assertFileDoesNotExist($this->target . 'ionos-core/stale');
    $this->assertSame('untouched', file_get_contents($this->target . 'other-plugin.php'));
    $this->assertNoLeftovers();
  }

  public function test_install_without_existing_plugin_succeeds(): void {
    unlink($this->target . 'ionos-core.php');
    \WP_Filesystem();
    global $wp_filesystem;
    $wp_filesystem->delete($this->target . 'ionos-core', true);

    $this->assertTrue((new MU_Plugin_Upgrader())->install($this->source, $this->target));
    $this->assertSame('new loader', file_get_contents($this->target . 'ionos-core.php'));
  }

  public function test_package_without_plugin_directory_is_rejected_and_nothing_changes(): void {
    global $wp_filesystem;
    $wp_filesystem->delete($this->source . 'ionos-core', true);

    $result = (new MU_Plugin_Upgrader())->install($this->source, $this->target);

    $this->assertWPError($result);
    $this->assertSame('ionos_core_update_invalid_package', $result->get_error_code());
    $this->assertOldVersionIntact();
    $this->assertNoLeftovers();
  }

  public function test_failing_backup_of_directory_restores_old_version(): void {
    $upgrader          = new Failing_MU_Plugin_Upgrader();
    $upgrader->fail_on = ['/.ionos-core-backup-'];

    $result = $upgrader->install($this->source, $this->target);

    $this->assertWPError($result);
    $this->assertSame('ionos_core_update_failed', $result->get_error_code());
    $this->assertOldVersionIntact();
    $this->assertNoLeftovers();
  }

  public function test_failing_install_of_loader_after_directory_was_installed_restores_old_version(): void {
    $upgrader          = new Failing_MU_Plugin_Upgrader();
    // the loader is installed last: fail moving the staged loader into the live location (but not the rollback)
    $upgrader->fail_when = fn (string $from, string $to) => str_contains($from, '.ionos-core-staging-') && str_ends_with($to, '/mu-plugins/ionos-core.php');

    $result = $upgrader->install($this->source, $this->target);

    $this->assertWPError($result);
    $this->assertSame('ionos_core_update_failed', $result->get_error_code());
    $this->assertOldVersionIntact();
    $this->assertNoLeftovers();
  }

  public function test_failing_rollback_keeps_backup_and_reports_it(): void {
    $upgrader = new Failing_MU_Plugin_Upgrader();
    // installing the loader fails and so does moving anything back from the backup
    $upgrader->fail_when = fn (string $from, string $to) => str_contains($from, '.ionos-core-backup-') || str_ends_with($to, '/mu-plugins/ionos-core.php');

    $result = $upgrader->install($this->source, $this->target);

    $this->assertWPError($result);
    $this->assertSame('ionos_core_update_rollback_failed', $result->get_error_code());
    $this->assertNotEmpty(glob($this->target . '.ionos-core-backup-*'), 'backup must be kept for manual recovery');
  }
}
