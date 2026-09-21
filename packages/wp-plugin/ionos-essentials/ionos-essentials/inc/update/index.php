<?php

/*
 * implements plugin update mechanism
 */

namespace ionos\essentials;

defined('ABSPATH') || exit();

/*
// @DEBUG: ENABLE ONLY WHEN DEBUGGING PLUGIN UPDATE CHECKS
if (false !== array_search(\wp_get_development_mode(), ['all', 'plugin'], true)) {
 // if wordpress is in development mode (https://developer.wordpress.org/reference/functions/\wp_get_development_mode/)
 // force plugin update checks / disable transient caching
 \add_action('plugins_loaded', fn () => \delete_site_transient('update_plugins'));
}
*/

/*
 * the github hosted update descriptor, kept as a fallback for the transition period only.
 *
 * the authoritative source is the plugin's own 'Update URI' header, which points at s3. this url
 * is queried when that fails, so an installation still carrying the pre-s3 header keeps updating.
 * it goes away once no such installation is left in the field.
 */
const LEGACY_INFO_JSON_URL = 'https://github.com/IONOS-WordPress/ionos-wordpress/releases/download/%40ionos-wordpress%2Flatest/ionos-essentials-info.json';

/*
 * the changelog is not part of the update descriptor, it is read straight from the repository.
 * this used to be derived from the 'Update URI' header, which no longer points at github.
 */
const CHANGELOG_URL = 'https://raw.githubusercontent.com/IONOS-WordPress/ionos-wordpress/refs/heads/main/packages/wp-plugin/ionos-essentials/CHANGELOG.md';

/*
 * returns the first update descriptor that answers with usable json, or null if none does.
 * a source is skipped on transport error, on a non-200 status and on a body that is not json.
 */
function fetch_update_info(string $update_uri): array|null
{
  foreach (array_unique([$update_uri, LEGACY_INFO_JSON_URL]) as $url) {
    $res = \wp_remote_get($url, [
      'headers' => [
        'Accept' => 'application/json',
      ],
    ]);

    if (\is_wp_error($res)) {
      error_log(sprintf('ionos-essentials: failed to request "%s" : %s', $url, $res->get_error_message()));
      continue;
    }

    $status = \wp_remote_retrieve_response_code($res);
    $body   = \wp_remote_retrieve_body($res);

    if (200 !== $status || '' === $body) {
      error_log(
        sprintf(
          'ionos-essentials: failed to fetch update information from "%s"(http-status=%s) : %s',
          $url,
          $status,
          '' !== $body ? $body : 'response body was empty',
        )
      );
      continue;
    }

    $info = json_decode($body, true);

    if (! is_array($info)) {
      error_log(sprintf('ionos-essentials: update information from "%s" is not valid json', $url));
      continue;
    }

    if (! array_all(['version', 'package'], fn (string $field): bool => is_string($info[$field] ?? null) && '' !== $info[$field])) {
      error_log(sprintf('ionos-essentials: update information from "%s" is missing version or package', $url));
      continue;
    }

    return $info;
  }

  return null;
}

/*
 * wordpress dispatches an update check to 'update_plugins_<host of the Update URI header>'. both
 * hosts are registered during the transition period: an installation that has not been updated
 * since the switch still carries the github header and would stop receiving updates otherwise.
 */
foreach (['s3-de-central.profitbricks.com', 'github.com'] as $update_uri_host) {
  \add_filter(
    hook_name: "update_plugins_{$update_uri_host}",
    accepted_args: 3,
    callback: function (array|false $update, array $plugin_data, string $plugin_slug): array|false {
      if (\plugin_basename(PLUGIN_FILE) !== $plugin_slug) {
        return $update;
      }

      return fetch_update_info($plugin_data['UpdateURI']) ?? $update;
    }
  );
}

/*
* This filter is used to modify the plugin information that is displayed in the WordPress admin panel as plugin details.
*
* see https://gist.github.com/CruelDrool/4cc70b819a33793396456c5ddb81781d
*/
\add_filter(
  hook_name: 'plugins_api',
  accepted_args: 3,
  callback: function (\stdClass|false $result, string $action, \stdClass $args): \stdClass|false {
    if (! isset($args->slug) || "{$args->slug}" !== \plugin_basename(PLUGIN_FILE)) {
      return $result;
    }

    $plugin_data = \get_plugin_data(ABSPATH . 'wp-content/plugins/' . $args->slug, false, false);

    $res = \wp_remote_get(CHANGELOG_URL, [
      'headers' => [
        'Accept' => 'application/json',
      ],
    ]);

    $result = (object) [
      'name'     => $plugin_data['Name'],
      'version'  => $plugin_data['Version'],
      'slug'     => $args->slug,
      'sections' => [
        'changelog' => '',  // will be filled later
      ],
    ];

    // abort if the request failed or the response code is not 200 or the response body is empty
    if ((200 !== \wp_remote_retrieve_response_code($res)) || ('' === \wp_remote_retrieve_body($res))) {
      // abort gracefully
      // show error message in the changelog section
      $result->sections['changelog'] = \esc_html(
        sprintf(
          // translators: first placeholder for the url, second for the plugin name, last one for the response code
          \__('Failed to download <a href=\"%1$s\">%2$s-info.json</a>(response status=%3$s)', 'ionos-essentials'),
          CHANGELOG_URL,
          $plugin_data['Name'],
          print_r(\wp_remote_retrieve_response_code($res), true),
        )
      );

      return $result;
    }

    $md_data   = \wp_remote_retrieve_body($res);
    $html_data = preg_replace('/### (.*?)\n/', '<strong>$1</strong>', $md_data);
    $html_data = preg_replace('/## (.*?)\n/', '<h4>$1</h4>', $html_data);
    $html_data = preg_replace('/# (.*?)\n/', '', $html_data);
    $html_data = preg_replace('/- [a-z0-9]{7}..(.*?)\n/', '<li>$1</li>', $html_data);
    $html_data = preg_replace(
      '/(?:(?<=<\/strong>)|(?<=<\/h4>))\s*((?:<li>.*?<\/li>\s*)+)/si',
      "<ul>\n$1</ul>",
      $html_data
    );

    $result->sections['changelog'] = \wp_kses_post($html_data);

    return $result;
  }
);
