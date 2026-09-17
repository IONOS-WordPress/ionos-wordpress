<?php

namespace ionos\ionos_core;

defined('ABSPATH') || exit();

/*
 * must-use plugins have no 'Update URI' header for wordpress to dispatch update checks to, so the
 * two sources are hardcoded here instead of one being read from a header and the other kept as a
 * fallback. s3 is authoritative, github is kept as a fallback for the transition period only and
 * goes away once no installation still needs it.
 */
const INFO_JSON_URL = 'https://s3-de-central.profitbricks.com/web-hosting/__S3_FOLDER__/ionos-core-info.json';

const LEGACY_INFO_JSON_URL = 'https://github.com/IONOS-WordPress/ionos-wordpress/releases/download/%40ionos-wordpress%2Flatest/ionos-core-info.json';

require_once __DIR__ . '/class-mu-plugin-upgrader.php';

/*
 * returns the first update descriptor that answers with usable json, or null if none does.
 * a source is skipped on transport error, on a non-200 status and on a body that is not json.
 */
function fetch_update_info(): array|null
{
  foreach (array_unique([INFO_JSON_URL, LEGACY_INFO_JSON_URL]) as $url) {
    $response = \wp_remote_get($url, [
      'timeout' => 5,
    ]);

    if (\is_wp_error($response)) {
      \error_log(sprintf('ionos-core: failed to request "%s" : %s', $url, $response->get_error_message()));
      continue;
    }

    $status = \wp_remote_retrieve_response_code($response);
    $body   = \wp_remote_retrieve_body($response);

    if (200 !== $status || '' === $body) {
      \error_log(
        sprintf(
          'ionos-core: failed to fetch update information from "%s"(http-status=%s) : %s',
          $url,
          $status,
          '' !== $body ? $body : 'response body was empty',
        )
      );
      continue;
    }

    try {
      $info = json_decode($body, true, 512, JSON_THROW_ON_ERROR);
    } catch (\JsonException $e) {
      \error_log(sprintf('ionos-core: update information from "%s" is not valid json : %s', $url, $e->getMessage()));
      continue;
    }

    if (! isset($info['version'], $info['package'])) {
      \error_log(sprintf('ionos-core: update information from "%s" is missing version or package', $url));
      continue;
    }

    \error_log(sprintf('ionos-core: resolved update information from "%s"', $url));

    return $info;
  }

  return null;
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
