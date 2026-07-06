<?php

/*
 * self-update mechanism pilot for a must-use plugin.
 *
 * WordPress core has no update-checker mechanism for must-use plugins - the
 * `update_plugins_github.com` filter essentials uses (see ../../../../ionos-essentials/inc/update)
 * is only ever invoked from inside wp_update_plugins() while it iterates get_plugins(), which
 * never lists mu-plugins. This piggybacks on the wp_update_plugins cron action WordPress core
 * already schedules twice daily instead, and reimplements the download/swap steps at a lower
 * level than Plugin_Upgrader (which assumes a wp-content/plugins/<slug>/ layout and fires
 * activate/deactivate hooks that don't apply to mu-plugins).
 *
 * this is a scoped pilot exception for test-mu-plugin - other mu-plugins (e.g. stretch-extra)
 * stay download-only by default, see docs/7-release.md.
 */

namespace ionos\test_mu_plugin\update;

use const ionos\test_mu_plugin\PLUGIN_FILE;

defined('ABSPATH') || exit();

const LAST_UPDATE_CHECK_OPTION = 'test_mu_plugin_last_update_check';

\add_action(hook_name: 'wp_update_plugins', callback: __NAMESPACE__ . '\check_for_update');

function check_for_update(): void
{
  require_once ABSPATH . 'wp-admin/includes/plugin.php';

  $plugin_data = \get_plugin_data(PLUGIN_FILE, false, false);
  $update_uri  = $plugin_data['UpdateURI'];

  if ('' === $update_uri) {
    return;
  }

  $response = \wp_remote_get($update_uri, [
    'headers' => [
      'Accept' => 'application/json',
    ],
  ]);

  if (200 !== \wp_remote_retrieve_response_code($response)) {
    record_check_result(
      status: 'failure',
      previous_version: $plugin_data['Version'],
      message: sprintf(
        'failed to fetch "%s" (http-status=%s)',
        $update_uri,
        \wp_remote_retrieve_response_code($response)
      )
    );

    return;
  }

  $info = json_decode(\wp_remote_retrieve_body($response), true);

  if (! isset($info['version'], $info['package'])) {
    record_check_result(
      status: 'failure',
      previous_version: $plugin_data['Version'],
      message: sprintf('malformed info.json response from "%s"', $update_uri)
    );

    return;
  }

  if (version_compare($plugin_data['Version'], $info['version'], '>=')) {
    record_check_result(status: 'up-to-date', previous_version: $plugin_data['Version'], new_version: $info['version']);

    return;
  }

  install_update($plugin_data['Version'], $info);
}

function install_update(string $previous_version, array $info): void
{
  require_once ABSPATH . 'wp-admin/includes/file.php';

  $tmp_zip = \download_url($info['package']);

  if (\is_wp_error($tmp_zip)) {
    record_check_result(
      status: 'failure',
      previous_version: $previous_version,
      new_version: $info['version'],
      message: $tmp_zip->get_error_message()
    );

    return;
  }

  // known limitation: hosts where PHP doesn't own the files directly may require FTP/SSH
  // credentials to initialize the filesystem - there is no UI to supply them in a cron context,
  // so the update silently no-ops (logged below) on such hosts. Acceptable for this pilot.
  if (! \WP_Filesystem()) {
    record_check_result(
      status: 'failure',
      previous_version: $previous_version,
      new_version: $info['version'],
      message: 'could not initialize WP_Filesystem - host may require FTP/SSH credentials that are not available in a cron context'
    );
    @unlink($tmp_zip);

    return;
  }

  global $wp_filesystem;

  $tmp_dir      = \get_temp_dir() . 'test-mu-plugin-update-' . uniqid('', true);
  $unzip_result = \unzip_file($tmp_zip, $tmp_dir);

  @unlink($tmp_zip);

  if (\is_wp_error($unzip_result)) {
    record_check_result(
      status: 'failure',
      previous_version: $previous_version,
      new_version: $info['version'],
      message: $unzip_result->get_error_message()
    );
    $wp_filesystem->delete($tmp_dir, true);

    return;
  }

  // the release pipeline zips a top-level folder named after the package containing exactly one
  // file (test-mu-plugin.php) plus one same-named companion folder (test-mu-plugin/) - swap both
  // in with two moves instead of a generic recursive copy
  $current_file = PLUGIN_FILE;
  $current_dir  = dirname(PLUGIN_FILE) . '/test-mu-plugin';
  $new_file     = $tmp_dir . '/test-mu-plugin/test-mu-plugin.php';
  $new_dir      = $tmp_dir . '/test-mu-plugin/test-mu-plugin';
  $backup_file  = $current_file . '.bak';
  $backup_dir   = $current_dir . '.bak';

  // safety net - rename the current file/folder to .bak before swapping the new ones in, so a
  // bad build can be auto-reverted instead of leaving the site fataling on every page load
  $wp_filesystem->move($current_file, $backup_file, true);
  $wp_filesystem->move($current_dir, $backup_dir, true);

  $wp_filesystem->move($new_file, $current_file, true);
  $wp_filesystem->move($new_dir, $current_dir, true);
  $wp_filesystem->delete($tmp_dir, true);

  $updated_version = \get_plugin_data($current_file, false, false)['Version'];

  if (version_compare($updated_version, $info['version'], '==')) {
    $wp_filesystem->delete($backup_file);
    $wp_filesystem->delete($backup_dir, true);

    record_check_result(status: 'success', previous_version: $previous_version, new_version: $updated_version);

    return;
  }

  // update did not result in the expected version - restore from backup rather than leaving the
  // site on a broken/partial copy
  $wp_filesystem->delete($current_file);
  $wp_filesystem->delete($current_dir, true);
  $wp_filesystem->move($backup_file, $current_file, true);
  $wp_filesystem->move($backup_dir, $current_dir, true);

  record_check_result(
    status: 'failure',
    previous_version: $previous_version,
    new_version: $info['version'],
    message: sprintf(
      'update did not result in the expected version (expected %s, got %s) - restored from backup',
      $info['version'],
      $updated_version
    )
  );
}

function record_check_result(
  string $status,
  ?string $previous_version = null,
  ?string $new_version = null,
  ?string $message = null
): void {
  \update_option(
    LAST_UPDATE_CHECK_OPTION,
    [
      'timestamp'        => \gmdate('Y-m-d\TH:i:s\Z'),
      'status'           => $status,
      'previous_version' => $previous_version,
      'new_version'      => $new_version,
      'message'          => $message,
    ],
    false
  );
}
