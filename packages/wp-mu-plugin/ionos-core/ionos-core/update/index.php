<?php

namespace ionos\ionos_core;

defined('ABSPATH') || exit();

/*
 * must-use plugins have no 'Update URI' header for wordpress to dispatch update checks to, so the
 * source is hardcoded here instead of being read from a header.
 */
const INFO_JSON_URL = 'https://s3-de-central.profitbricks.com/web-hosting/__S3_FOLDER__/ionos-core.info.json';

/*
 * returns the update descriptor from INFO_JSON_URL, or null if it does not answer with usable json.
 */
function fetch_update_info(): array|null
{
  $response = \wp_remote_get(INFO_JSON_URL, [
    'timeout' => 5,
  ]);

  if (\is_wp_error($response)) {
    \error_log(sprintf('ionos-core: failed to request "%s" : %s', INFO_JSON_URL, $response->get_error_message()));
    return null;
  }

  $status = \wp_remote_retrieve_response_code($response);
  $body   = \wp_remote_retrieve_body($response);

  if (200 !== $status || '' === $body) {
    \error_log(
      sprintf(
        'ionos-core: failed to fetch update information from "%s"(http-status=%s) : %s',
        INFO_JSON_URL,
        $status,
        '' !== $body ? $body : 'response body was empty',
      )
    );
    return null;
  }

  try {
    $info = json_decode($body, true, 512, JSON_THROW_ON_ERROR);
  } catch (\JsonException $e) {
    \error_log(sprintf('ionos-core: update information from "%s" is not valid json : %s', INFO_JSON_URL, $e->getMessage()));
    return null;
  }

  // array_all() is PHP 8.4+ only, but this mu-plugin also runs on PHP 7.4/8.3 (see
  // packages/docker/rector-php/rector-config-php7.4.php) - a plain loop keeps this
  // check working on every shipped runtime
  if (! is_array($info)) {
    \error_log(sprintf('ionos-core: update information from "%s" is not a json object', INFO_JSON_URL));
    return null;
  }

  if (
    ! is_string($info['version'] ?? null) || ''                                          === $info['version']
                                          || ! is_string($info['package'] ?? null) || '' === $info['package']
  ) {
    \error_log(sprintf('ionos-core: update information from "%s" is missing version or package', INFO_JSON_URL));
    return null;
  }

  return $info;
}

/*
 * brings $dir (the mu-plugins directory) back to a clean, not updated state after an interrupted update :
 * puts the previous version back if the update stopped between the two directory renames and removes all leftovers.
 * an interrupted update never reached its last step (replacing the loader), so the loader still reports the
 * previous version and the next update check simply runs the update again.
 */
function cleanup_update(string $dir, \WP_Filesystem_Base $filesystem): void
{
  $plugin_dir = $dir . '/ionos-core';

  if (! is_dir($plugin_dir) && is_dir($plugin_dir . '.bak') && ! rename($plugin_dir . '.bak', $plugin_dir)) {
    \error_log(sprintf('ionos-core: failed to restore "%s" from "%s.bak"', $plugin_dir, $plugin_dir));
  }

  $leftovers = [$plugin_dir . '.new', $plugin_dir . '.bak', $dir . '/ionos-core.php.new', $dir . '/ionos-core.php.tmp'];
  foreach ($leftovers as $leftover) {
    if (file_exists($leftover)) {
      $filesystem->delete($leftover, true);
    }
  }
}

/*
 * installs the unpacked package in $package_dir (ionos-core.php + ionos-core/) into $dir (the mu-plugins directory).
 *
 * everything is prepared next to the live plugin first (names never end with '.php', mu-plugins would load them).
 * then the directory is swapped by two renames and the loader is replaced last - that last rename completes the
 * update. a kill at any point leaves the old loader, and cleanup_update() repairs the state before the next update.
 */
function install_update(
  string $package_dir,
  string $dir,
  string $version,
  \WP_Filesystem_Base $filesystem
): bool|\WP_Error {
  $loader     = $dir . '/ionos-core.php';
  $plugin_dir = $dir . '/ionos-core';

  // a package with another version than announced would be installed again on every update check
  $package_version = is_file($package_dir . '/ionos-core.php') ? \get_file_data($package_dir . '/ionos-core.php', [
    'version' => 'Version',
  ])['version'] : '';
  if ($package_version !== $version || ! is_file($package_dir . '/ionos-core/index.php')) {
    return new \WP_Error(
      'ionos_core_update_invalid_package',
      sprintf('package is not a complete ionos-core %s (found version "%s")', $version, $package_version)
    );
  }

  // 1. prepare the new version next to the live one
  if (! $filesystem->mkdir($plugin_dir . '.new')) {
    return new \WP_Error('ionos_core_update_stage_failed', sprintf('failed to create "%s.new"', $plugin_dir));
  }
  $result = \copy_dir($package_dir . '/ionos-core', $plugin_dir . '.new');
  if (\is_wp_error($result)) {
    $filesystem->delete($plugin_dir . '.new', true);
    return $result;
  }
  // via .tmp : a ionos-core.php.new only exists complete
  if (! copy($package_dir . '/ionos-core.php', $loader . '.tmp') || ! rename($loader . '.tmp', $loader . '.new')) {
    cleanup_update($dir, $filesystem);
    return new \WP_Error('ionos_core_update_stage_failed', sprintf('failed to write "%s.new"', $loader));
  }
  if (is_file($loader)) {
    chmod($loader . '.new', fileperms($loader) & 0777);
  }

  // 2. swap the directory
  if (is_dir($plugin_dir) && ! rename($plugin_dir, $plugin_dir . '.bak')) {
    cleanup_update($dir, $filesystem);
    return new \WP_Error('ionos_core_update_swap_failed', sprintf('failed to move "%s" aside', $plugin_dir));
  }
  if (! rename($plugin_dir . '.new', $plugin_dir)) {
    // puts ionos-core.bak back
    cleanup_update($dir, $filesystem);
    return new \WP_Error('ionos_core_update_swap_failed', sprintf('failed to move "%s.new" in place', $plugin_dir));
  }

  // 3. replace the loader : completes the update
  if (! rename($loader . '.new', $loader)) {
    // the new code is live but the loader still reports the previous version : the next update check repeats the update
    return new \WP_Error('ionos_core_update_swap_failed', sprintf('failed to move "%s.new" in place', $loader));
  }

  if (function_exists('wp_opcache_invalidate_directory')) {
    \wp_opcache_invalidate_directory($plugin_dir);
  }
  \wp_opcache_invalidate($loader);

  $filesystem->delete($plugin_dir . '.bak', true);

  return true;
}

/*
 * loads the wordpress admin apis the update needs and returns the filesystem abstraction.
 * returns a WP_Error unless files are accessed directly : the update relies on rename() of local paths.
 */
function _update_filesystem(): \WP_Filesystem_Base|\WP_Error
{
  global $wp_filesystem;

  require_once ABSPATH . 'wp-admin/includes/file.php';
  require_once ABSPATH . 'wp-admin/includes/class-wp-upgrader.php';

  if (! \WP_Filesystem() || 'direct' !== $wp_filesystem->method) {
    return new \WP_Error(
      'ionos_core_update_filesystem',
      sprintf("update requires the 'direct' filesystem method (is '%s')", $wp_filesystem->method ?? 'unavailable')
    );
  }

  return $wp_filesystem;
}

/*
 * downloads the package (zip with ionos-core.php + ionos-core/) and installs it into $dir.
 */
function update(string $package_url, string $version, string $dir = WPMU_PLUGIN_DIR): bool|\WP_Error
{
  $filesystem = _update_filesystem();
  if (\is_wp_error($filesystem)) {
    return $filesystem;
  }

  // the update check runs from wp-cron : two overlapping runs must not swap the directories at the same time
  if (! \WP_Upgrader::create_lock('ionos_core_update')) {
    return new \WP_Error('ionos_core_update_locked', 'another update is in progress');
  }

  try {
    cleanup_update($dir, $filesystem);

    $upgrader = new \WP_Upgrader(new \Automatic_Upgrader_Skin());
    $upgrader->init();

    $package = $upgrader->download_package($package_url);
    if (\is_wp_error($package)) {
      return $package;
    }

    $working_dir = $upgrader->unpack_package($package, true);
    if (\is_wp_error($working_dir)) {
      return $working_dir;
    }

    $result = install_update(\untrailingslashit($working_dir), $dir, $version, $filesystem);
    $filesystem->delete($working_dir, true);

    return $result;
  } finally {
    \WP_Upgrader::release_lock('ionos_core_update');
  }
}

\add_action('wp_update_plugins', function (): void {
  $info = fetch_update_info();

  if (! $info) {
    return;
  }

  $latest  = $info['version'];
  $package = $info['package'];

  $current_version = \get_file_data(__DIR__ . '/../../ionos-core.php', [
    'version' => 'Version',
  ])['version'] ?? null;

  if (! \version_compare($latest, $current_version, '>')) {
    return;
  }

  $result = update($package, $latest);

  if (\is_wp_error($result)) {
    \error_log('ionos-core: Update failed: ' . $result->get_error_message());
  }
});
