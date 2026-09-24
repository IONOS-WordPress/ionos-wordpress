<?php

namespace ionos\ionos_core;

defined('ABSPATH') || exit();

/*
 * must-use plugins have no 'Update URI' header for wordpress to dispatch update checks to, so the
 * source is hardcoded here instead of being read from a header.
 */
const INFO_JSON_URL = 'https://s3-de-central.profitbricks.com/web-hosting/__S3_FOLDER__/ionos-core-info.json';

require_once __DIR__ . '/class-mu-plugin-upgrader.php';

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

  $result = (new MU_Plugin_Upgrader())->upgrade($package);

  if (\is_wp_error($result)) {
    \error_log('ionos-core: Update failed: ' . $result->get_error_message());
  }
});
