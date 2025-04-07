<?php

namespace ionos\essentials;

use const ionos\essentials\PLUGIN_FILE;

/**
 * implements plugin update mechanism
 */

// @DEBUG: ENABLE ONLY WHEN DEBUGGING PLUGIN UPDATE CHECKS
if (array_search(\wp_get_development_mode(), ['all', 'plugin'], true) !== false) {
  // if wordpress is in development mode (https://developer.wordpress.org/reference/functions/wp_get_development_mode/)
  // force plugin update checks / disable transient caching
  \add_action('plugins_loaded', fn () => \delete_site_transient('update_plugins'));
}

\add_filter('update_plugins_api.github.com', function (
  array|false $update,
  array $plugin_data,
  string $plugin_slug,
): array|false {
  if (\plugin_basename(PLUGIN_FILE) !== $plugin_slug) {
    return $update;
  }

  // get the redirect URL from the UpdateURI
  $res = \wp_remote_get($plugin_data['UpdateURI'] . '/latest', [
    'headers' => [
      'Accept' => 'application/json',
    ],
  ]);

  // abort if the request failed or the response code is not 200 or the response body is empty
  if ((200 !== \wp_remote_retrieve_response_code($res)) || ('' === \wp_remote_retrieve_body($res))) {
    if ('' !== \wp_remote_retrieve_response_code($res)) {
      // may happen for rate limit exceeded
      // phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log
      error_log(
        sprintf(
          'Failed to fetch latest update information from "%s"(http-status=%s) : %s',
          $plugin_data['UpdateURI'],
          \wp_remote_retrieve_response_code($res),
          \wp_remote_retrieve_body($res),
        )
      );
    } else {
      // phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log
      error_log(sprintf('Failed to download update information from "%s"', $plugin_data['UpdateURI']));
    }
    return $update;
  }

  $release = json_decode($res['body'], true);

  // extract version from release name
  $_              = explode('@', $release['tag_name']);
  $version        = end($_);

  // example : '/essentials-0\.\0\.4-php.*\.zip/'
  $_                 = explode('/', $plugin_data['Name']);
  $asset_name_regexp = '/'
    . preg_quote(end($_), '/') // 'ionos-wordpress/essentials' => 'essentials'
    . '-' . preg_quote($version, '/') // '0\.0\.4'
    . '-php.*\.zip/';

  // find the asset that matches the asset name regular expression
  // and return the $update data if found
  foreach ($release['assets'] as $asset) {
    if (preg_match($asset_name_regexp, $asset['name'])) {
      return [
        'version' => $version,
        'package' => $asset['browser_download_url'],
        // slug is required to trigger the 'plugins_api' filter below
        'slug' => $plugin_slug,
      ];
    }
  }
  return $update;
}, 10, 3);

// action in_plugin_update_message-{$file}"in_plugin_update_message-{$file}"

/*
* This filter is used to modify the plugin information that is displayed in the WordPress admin panel as plugin details.
*
* see https://gist.github.com/CruelDrool/4cc70b819a33793396456c5ddb81781d
*/
\add_filter('plugins_api', function (\stdClass|false $result, string $action, \stdClass $args): \stdClass|false {
  if (! isset($args->slug) || "{$args->slug}" !== \plugin_basename(PLUGIN_FILE)) {
    return $result;
  }

  $plugin_data = \get_plugin_data(ABSPATH . 'wp-content/plugins/' . $args->slug, false, false);

  $result = (object) [
    'name'     => $plugin_data['Name'],
    'version'  => $plugin_data['Version'],
    'slug'     => $args->slug,
    'sections' => [
      'changelog' => '',  // will be filled later
    ],
  ];

  // fetch changelog from github
  $res = \wp_remote_get($plugin_data['UpdateURI'] . '/latest', [
    'headers' => [
      'Accept' => 'application/json',
    ],
  ]);

  // abort if the request failed or the response code is not 200 or the response body is empty
  if ((200 !== \wp_remote_retrieve_response_code($res)) || ('' === \wp_remote_retrieve_body($res))) {
    // abort gracefully
    // show error message including link in the changelog section
    $result->sections['changelog'] = sprintf(
      'Failed to download <a href=\"%s\">changelog</a>(response=%s)',
      $plugin_data['PluginURI'] . '/latest',
      print_r(\wp_remote_retrieve_response_code($res), true),
    );

    return $result;
  }

  $release = json_decode($res['body'], true);

  $changelog_html = $release['body'];

  // Basic markdown to HTML conversion (example implementation)
  $changelog_html = htmlspecialchars($changelog_html, ENT_QUOTES, 'UTF-8');
  $changelog_html = preg_replace('/\*\*(.*?)\*\*/', '<strong>$1</strong>', $changelog_html); // Bold
  $changelog_html = preg_replace('/\*(.*?)\*/', '<em>$1</em>', $changelog_html); // Italic
  $changelog_html = preg_replace('/\#\#\# (.*?)\n/', '<h3>$1</h3>', $changelog_html); // H3
  $changelog_html = preg_replace('/\#\# (.*?)\n/', '<h2>$1</h2>', $changelog_html); // H2
  $changelog_html = preg_replace('/\# (.*?)\n/', '<h1>$1</h1>', $changelog_html); // H1
  // $changelog_html = preg_replace('/\n/', '<br>', $changelog_html); // Line breaks

  // Unordered list parsing
  $changelog_html = preg_replace_callback(
    '/(?:^|\n)- (.*?)(?=\n|$)/',
    fn ($matches) => '<li>' . $matches[1] . '</li>',
    $changelog_html
  );
  $changelog_html = preg_replace('/(<li>.*?<\/li>)/s', '<ul>$1</ul>', $changelog_html);

  $result->sections['changelog'] = $changelog_html;

  return $result;
}, 10, 3);
