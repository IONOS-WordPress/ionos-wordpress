<?php

namespace ionos\ionos_core;

/**
 * covers the loader ionos-core.php : behaviour without ionos-core/ and loading of the translations
 *
 * run only this test using 'pnpm test:php --php-opts "--filter LoaderTest"'
 *
 * @group ionos-core
 */
class LoaderTest extends \WP_UnitTestCase {
  public function tearDown(): void {
    \restore_previous_locale();

    parent::tearDown();
  }

  public function test_loader_without_plugin_directory_logs_and_keeps_the_update_hook(): void {
    // the loader is already loaded in this process : run a copy of it without ionos-core/ in a separate php process,
    // with just enough of wordpress stubbed to record the registered hooks
    $dir = sys_get_temp_dir() . '/ionos-core-loader-' . uniqid();
    mkdir($dir);
    copy(PLUGIN_FILE, $dir . '/ionos-core.php');
    file_put_contents($dir . '/run.php', <<<'PHP'
      <?php
      define('ABSPATH', '/');
      $hooks = [];
      function add_action(string $hook, callable $callback): void { $GLOBALS['hooks'][] = $hook; }
      require $argv[1];
      echo json_encode($hooks);
      PHP);

    $output = [];
    exec(
      sprintf(
        '%s -d error_log= %s %s 2>&1',
        escapeshellarg(PHP_BINARY),
        escapeshellarg($dir . '/run.php'),
        escapeshellarg($dir . '/ionos-core.php')
      ),
      $output,
      $exit_code
    );
    array_map('unlink', glob($dir . '/*'));
    rmdir($dir);

    $this->assertSame(0, $exit_code, implode("\n", $output));
    $this->assertStringContainsString('ionos-core/index.php" not found, plugin not loaded', implode("\n", $output));
    $this->assertSame('["wp_update_plugins"]', end($output));
  }

  public function test_translations_are_loaded(): void {
    if (! is_file(dirname(PLUGIN_FILE) . '/ionos-core/languages/ionos-core-de_DE.l10n.php')) {
      $this->markTestSkipped('translations not built (WP_CLI_I18N_LOCALES not set)');
    }

    \switch_to_locale('de_DE');

    $this->assertSame('empfiehlt', \__('recommends', 'ionos-core'));
  }
}
