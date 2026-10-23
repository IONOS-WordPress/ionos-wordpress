<?php

namespace ionos\ionos_core;

/**
 * test double allowing to make selected moves fail or throw, to simulate a full disk and to record maintenance mode changes
 */
class Failing_MU_Plugin_Upgrader extends MU_Plugin_Upgrader {
  /** @var string[] substrings of destination paths for which move() fails */
  public array $fail_on = [];

  /** @var ?callable(string, string): bool if set and returning true, move() fails */
  public $fail_when = null;

  /** @var ?callable(string, string): bool if set and returning true, move() throws (simulates the request dying) */
  public $throw_when = null;

  /** @var ?callable(string, string): bool if set and returning true, move() is performed and then throws (the request dies after the disk changed) */
  public $throw_after_when = null;

  /** @var ?callable(string, string): bool if set and returning true, move() leaves a partial destination behind and fails */
  public $partial_when = null;

  public ?float $free_space = null;

  /** @var bool[] recorded maintenance mode changes */
  public array $maintenance = [];

  protected function free_space(string $dir): ?float {
    return $this->free_space ?? parent::free_space($dir);
  }

  protected function set_maintenance_mode(bool $enable): void {
    $this->maintenance[] = $enable;
  }

  protected function move(string $from, string $to): bool {
    if ($this->throw_when && ($this->throw_when)($from, $to)) {
      throw new \RuntimeException('simulated fatal error');
    }

    if ($this->partial_when && ($this->partial_when)($from, $to)) {
      is_dir($to) || mkdir($to, 0777, true);
      file_put_contents(rtrim($to, '/') . '/partial.php', 'partial');
      return false;
    }

    if ($this->fail_when && ($this->fail_when)($from, $to)) {
      return false;
    }

    foreach ($this->fail_on as $needle) {
      if (str_contains($to, $needle)) {
        return false;
      }
    }

    $result = parent::move($from, $to);

    if ($this->throw_after_when && ($this->throw_after_when)($from, $to)) {
      throw new \RuntimeException('simulated fatal error after move');
    }

    return $result;
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
    $this->write($this->source . 'ionos-core.php', "<?php\n/**\n * Version: 9.9.9\n */\n// new loader");
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
    $this->assertStringContainsString('// new loader', file_get_contents($this->target . 'ionos-core.php'));
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
    $this->assertStringContainsString('// new loader', file_get_contents($this->target . 'ionos-core.php'));
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

  public function test_package_with_file_instead_of_plugin_directory_is_rejected_and_nothing_changes(): void {
    global $wp_filesystem;
    $wp_filesystem->delete($this->source . 'ionos-core', true);
    file_put_contents($this->source . 'ionos-core', 'not a directory');

    $result = (new MU_Plugin_Upgrader())->install($this->source, $this->target);

    $this->assertWPError($result);
    $this->assertSame('ionos_core_update_invalid_package', $result->get_error_code());
    $this->assertOldVersionIntact();
    $this->assertNoLeftovers();
  }

  public function test_package_with_directory_instead_of_loader_file_is_rejected_and_nothing_changes(): void {
    unlink($this->source . 'ionos-core.php');
    mkdir($this->source . 'ionos-core.php');

    $result = (new MU_Plugin_Upgrader())->install($this->source, $this->target);

    $this->assertWPError($result);
    $this->assertSame('ionos_core_update_invalid_package', $result->get_error_code());
    $this->assertOldVersionIntact();
    $this->assertNoLeftovers();
  }

  public function test_interruption_right_after_backup_move_restores_old_version_on_shutdown(): void {
    $upgrader = new Failing_MU_Plugin_Upgrader();
    // the request dies after the plugin directory was moved to the backup (disk changed, move() never returned)
    $upgrader->throw_after_when = fn (string $from, string $to) => str_ends_with($to, '/ionos-core/') && str_contains($to, '.ionos-core-backup-')
      || str_contains($to, '.ionos-core-backup-') && str_ends_with($to, '/ionos-core');

    try {
      $upgrader->install($this->source, $this->target);
      $this->fail('exception expected');
    } catch (\RuntimeException $e) {
      // simulated fatal error
    }

    $this->assertFileDoesNotExist($this->target . 'ionos-core', 'plugin directory must be in the backup');

    $upgrader->restore_on_shutdown();

    $this->assertOldVersionIntact();
    $this->assertNoLeftovers();
  }

  public function test_failed_install_with_partial_destination_restores_old_version(): void {
    $upgrader = new Failing_MU_Plugin_Upgrader();
    // moving the staged plugin directory fails after it left a partial copy in the live location
    $upgrader->partial_when = fn (string $from, string $to) => str_contains($from, '.ionos-core-staging-') && str_ends_with(rtrim($to, '/'), '/mu-plugins/ionos-core');

    $result = $upgrader->install($this->source, $this->target);

    $this->assertWPError($result);
    $this->assertSame('ionos_core_update_failed', $result->get_error_code());
    $this->assertOldVersionIntact();
    $this->assertNoLeftovers();
  }

  public function test_interruption_right_after_install_rename_restores_old_version_on_shutdown(): void {
    $upgrader = new Failing_MU_Plugin_Upgrader();
    // the request dies after the staged plugin directory was renamed into place (disk changed, move() never returned)
    $upgrader->throw_after_when = fn (string $from, string $to) => str_contains($from, '.ionos-core-staging-') && str_ends_with(rtrim($to, '/'), '/mu-plugins/ionos-core');

    try {
      $upgrader->install($this->source, $this->target);
      $this->fail('exception expected');
    } catch (\RuntimeException $e) {
      // simulated fatal error
    }

    $this->assertSame('new index', file_get_contents($this->target . 'ionos-core/index.php'));

    $upgrader->restore_on_shutdown();

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

  public function test_maintenance_mode_is_enabled_during_swap_and_disabled_afterwards(): void {
    $upgrader = new Failing_MU_Plugin_Upgrader();

    $this->assertTrue($upgrader->install($this->source, $this->target));
    $this->assertSame([true, false], $upgrader->maintenance);
  }

  public function test_maintenance_mode_is_disabled_after_rollback(): void {
    $upgrader            = new Failing_MU_Plugin_Upgrader();
    $upgrader->fail_when = fn (string $from, string $to) => str_contains($from, '.ionos-core-staging-');

    $this->assertWPError($upgrader->install($this->source, $this->target));
    $this->assertSame([true, false], $upgrader->maintenance);
  }

  public function test_not_enough_disk_space_is_rejected_and_nothing_changes(): void {
    $upgrader             = new Failing_MU_Plugin_Upgrader();
    $upgrader->free_space = 0.0;

    $result = $upgrader->install($this->source, $this->target);

    $this->assertWPError($result);
    $this->assertSame('ionos_core_update_disk_space', $result->get_error_code());
    $this->assertOldVersionIntact();
    $this->assertNoLeftovers();
    $this->assertSame([], $upgrader->maintenance);
  }

  public function test_interrupted_swap_is_rolled_back_on_shutdown(): void {
    $upgrader = new Failing_MU_Plugin_Upgrader();
    // the request dies while the staged loader is moved in place: the new directory is already live, the old loader is in the backup
    $upgrader->throw_when = fn (string $from, string $to) => str_contains($from, '.ionos-core-staging-') && str_ends_with($to, '/mu-plugins/ionos-core.php');

    try {
      $upgrader->install($this->source, $this->target);
      $this->fail('exception expected');
    } catch (\RuntimeException $e) {
      // simulated fatal error
    }

    // half installed: new directory live, loader missing
    $this->assertSame('new index', file_get_contents($this->target . 'ionos-core/index.php'));
    $this->assertFileDoesNotExist($this->target . 'ionos-core.php');
    $this->assertNotFalse(has_action('shutdown', [$upgrader, 'restore_on_shutdown']));

    $upgrader->restore_on_shutdown();

    $this->assertOldVersionIntact();
    $this->assertNoLeftovers();
    $this->assertSame([true, false], $upgrader->maintenance);
    $this->assertFalse(has_action('shutdown', [$upgrader, 'restore_on_shutdown']));
  }

  public function test_shutdown_handler_does_nothing_after_successful_install(): void {
    $upgrader = new Failing_MU_Plugin_Upgrader();
    $this->assertTrue($upgrader->install($this->source, $this->target));

    $upgrader->restore_on_shutdown();

    $this->assertStringContainsString('// new loader', file_get_contents($this->target . 'ionos-core.php'));
    $this->assertFalse(has_action('shutdown', [$upgrader, 'restore_on_shutdown']));
  }

  public function test_real_fatal_error_mid_swap_is_rolled_back_on_shutdown(): void {
    $process = proc_open(
      [PHP_BINARY, __DIR__ . '/fixtures/fatal-during-swap.php', ABSPATH, $this->source, $this->target],
      [1 => ['pipe', 'w'], 2 => ['pipe', 'w']],
      $pipes
    );
    $output   = stream_get_contents($pipes[1]) . stream_get_contents($pipes[2]);
    $exitcode = proc_close($process);

    $this->assertNotSame(0, $exitcode, 'the child process must have died with a fatal error: ' . $output);
    $this->assertStringContainsString('undefined_function_that_kills_the_request', $output);
    $this->assertOldVersionIntact();
    $this->assertNoLeftovers();
  }

  public function test_package_without_version_header_is_rejected(): void {
    file_put_contents($this->source . 'ionos-core.php', '<?php // no header');

    $result = (new Failing_MU_Plugin_Upgrader())->install($this->source, $this->target);

    $this->assertWPError($result);
    $this->assertSame('ionos_core_update_invalid_package', $result->get_error_code());
    $this->assertOldVersionIntact();
    $this->assertNoLeftovers();
  }

  public function test_non_direct_filesystem_is_rejected_and_nothing_changes(): void {
    global $wp_filesystem;
    $method                = $wp_filesystem->method;
    $wp_filesystem->method = 'ftpext';

    try {
      $result = (new Failing_MU_Plugin_Upgrader())->install($this->source, $this->target);
    } finally {
      $wp_filesystem->method = $method;
    }

    $this->assertWPError($result);
    $this->assertSame('ionos_core_update_unsupported_filesystem', $result->get_error_code());
    $this->assertOldVersionIntact();
    $this->assertNoLeftovers();
  }

  public function test_move_works_across_filesystems(): void {
    $other = '/dev/shm/ionos-core-upgrader-' . uniqid() . '/';
    if (! is_dir('/dev/shm') || ! is_writable('/dev/shm') || stat('/dev/shm')['dev'] === stat($this->base)['dev']) {
      $this->markTestSkipped('no second filesystem available');
    }

    mkdir($other);
    $upgrader = new class () extends MU_Plugin_Upgrader {
      public function move_public(string $from, string $to): bool {
        return $this->move($from, $to);
      }
    };

    try {
      $this->assertTrue($upgrader->move_public($this->target . 'ionos-core', $other . 'ionos-core'));
      $this->assertTrue($upgrader->move_public($this->target . 'ionos-core.php', $other . 'ionos-core.php'));

      $this->assertSame('old index', file_get_contents($other . 'ionos-core/index.php'));
      $this->assertFileExists($other . 'ionos-core/stale/stale.php');
      $this->assertSame('old loader', file_get_contents($other . 'ionos-core.php'));
      $this->assertFileDoesNotExist($this->target . 'ionos-core');
      $this->assertFileDoesNotExist($this->target . 'ionos-core.php');
    } finally {
      global $wp_filesystem;
      $wp_filesystem->delete($other, true);
    }
  }
}
