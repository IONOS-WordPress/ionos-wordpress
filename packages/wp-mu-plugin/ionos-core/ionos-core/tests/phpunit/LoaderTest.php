<?php

namespace ionos\ionos_core;

/**
 * covers the loader ionos-core.php and the entry point ionos-core/index.php : loading of the translations
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

  public function test_translations_are_loaded(): void {
    if (! is_file(WPMU_PLUGIN_DIR . '/ionos-core/languages/ionos-core-de_DE.l10n.php')) {
      $this->markTestSkipped('translations not built (WP_CLI_I18N_LOCALES not set)');
    }

    \switch_to_locale('de_DE');

    $this->assertSame('empfiehlt', \__('recommends', 'ionos-core'));
  }
}
