<?php

/**
 * Plugin Name:       ionos-essentials
 * Description:       The essentials plugin provides IONOS hosting specific functionality.
 * Requires at least: 6.6
 * Requires Plugins:
 * Requires PHP:      8.3
 * Version:           0.1.0
 * Update URI:        https://api.github.com/repos/IONOS-WordPress/ionos-wordpress/releases
 * Plugin URI:        https://github.com/IONOS-WordPress/ionos-wordpress/tree/main/packages/wp-plugin/essentials
 * License:           GPL-2.0-or-later
 * Author:            IONOS Group
 * Author URI:        https://www.ionos-group.com/brands.html
 * Domain Path:       /languages
 * Text Domain:       ionos-essentials
 */

namespace ionos_wordpress\essentials;

const PLUGIN_FILE = __FILE__;
const PLUGIN_DIR  = __DIR__;

defined('ABSPATH') || exit();

\add_action(
  'init',
  fn () => \load_plugin_textdomain(domain: 'ionos-essentials', plugin_rel_path: basename(__DIR__) . '/languages/')
);

/*
// @DEBUG: ENABLE ONLY WHEN DEBUGGING PLUGIN UPDATE CHECKS
if (array_search(\wp_get_development_mode(), ['all', 'plugin'], true) !== false) {
  // if wordpress is in development mode (https://developer.wordpress.org/reference/functions/wp_get_development_mode/)
  // force plugin update checks / disable transient caching
  \add_action('plugins_loaded', fn () => \delete_site_transient('update_plugins'));
}
*/

\add_filter('update_plugins_api.github.com', function (
  array|false $update,
  array $plugin_data,
  string $plugin_slug,
): array|false {
  if (\plugin_basename(__FILE__) !== $plugin_slug) {
    return $update;
  }
  // get the update information from github releases
  $res = \wp_remote_get($plugin_data['UpdateURI'], [
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
          'Failed to download update information from "%s"(http-status=%s) : %s',
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

  // releases is an array of release objects
  $releases = json_decode($res['body'], true);
  if (JSON_ERROR_NONE !== json_last_error()) {
    return $update;
  }

  // we filter out all releases that do not contain the plugin name
  $releases = array_filter($releases, fn ($release) => str_contains($release['name'], $plugin_data['Name']));
  if (empty($releases)) {
    return $update;
  }

  // convert the releases array to an associative array with the name as key
  $releases = array_column($releases, null, 'name');

  // get the latest release by sorting the releases names in natural order
  // example release name : '@ionos-wordpress/essentials@0.0.4'
  $release_names = array_keys($releases);
  natsort($release_names);
  $latest_release_name = end($release_names);

  // extract version from release name
  $_              = explode('@', $latest_release_name);
  $version        = end($_);
  $latest_release = $releases[$latest_release_name];

  // example : '/essentials-0\.\0\.4-php.*\.zip/'
  $_                 = explode('/', $plugin_data['Name']);
  $asset_name_regexp = '/'
    . preg_quote(end($_), '/') // 'ionos-wordpress/essentials' => 'essentials'
    . '-' . preg_quote($version, '/') // '0\.0\.4'
    . '-php.*\.zip/';

  // find the asset that matches the asset name regular expression
  // and return the $update data if found
  foreach ($latest_release['assets'] as $asset) {
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
  if (! isset($args->slug) || "{$args->slug}" !== \plugin_basename(__FILE__)) {
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
  // (example : https://github.com/IONOS-WordPress/.../packages/wp-plugin/essentials/CHANGELOG.md)
  $res = \wp_remote_get($plugin_data['PluginURI'] . '/CHANGELOG.md');

  // abort if the request failed or the response code is not 200 or the response body is empty
  if ((200 !== \wp_remote_retrieve_response_code($res)) || ('' === \wp_remote_retrieve_body($res))) {
    // abort gracefully
    // show error message including link in the changelog section
    $result->sections['changelog'] = sprintf(
      'Failed to download <a href=\"%s\">changelog</a>(response=%s)',
      $plugin_data['PluginURI'] . '/CHANGELOG.md',
      print_r(\wp_remote_retrieve_response_code($res), true),
    );

    return $result;
  }

  // extract changelog from response
  $body  = $res['body'];
  $start = strpos($body, '<article');
  $end   = strpos($body, '</article>', $start);
  if (false === $start || false === $end) {
    // abort gracefully
    // show error message including link in the changelog section
    $result->sections['changelog'] = sprintf(
      'Failed to extract %s tag from <a href=\"%s\">changelog</a>',
      \esc_html('<article>'),
      $plugin_data['PluginURI'] . '/CHANGELOG.md',
    );

    return $result;
  }

  $article_html                  = substr($body, $start, $end - $start + strlen('</article>'));
  $article_html                  = str_replace('@' . $result->name, $result->name, $article_html);
  $result->sections['changelog'] = $article_html;

  return $result;
}, 10, 3);

// features
require_once __DIR__ . '/inc/switch-page/index.php';
require_once __DIR__ . '/inc/dashboard/index.php';

// soc plugin components
require_once __DIR__ . '/inc/migration/index.php';

/*
[
  'name' => 'cm4all-wp-impex',
  'slug' => 'cm4all-wp-impex',
  'version' => '1.6.0',
  'author' => '<a href="https:\\/\\/cm4all.com">Lars Gersmann, CM4all<\\/a>',
  'author_profile' => 'https:\\/\\/profiles.wordpress.org\\/cm4all\\/',
  'contributors' => [
    'cm4all' => [
      'profile' => 'https:\\/\\/profiles.wordpress.org\\/cm4all\\/',
      'avatar' => 'https:\\/\\/secure.gravatar.com\\/avatar\\/c50169eef63e643a96efc174cf099032?s=96&d=monsterid&r=g',
      'display_name' => 'cm4all',
    ],
  ],
  'requires' => '5.7',
  'tested' => '6.2.6',
  'requires_php' => '7.4',
  'requires_plugins' => [
  ],
  'rating' => 0,
  'ratings' => [
    1 => 0,
    2 => 0,
    3 => 0,
    4 => 0,
    5 => 0,
  ],
  'num_ratings' => 0,
  'support_url' => 'https:\\/\\/wordpress.org\\/support\\/plugin\\/cm4all-wp-impex\\/',
  'support_threads' => 0,
  'support_threads_resolved' => 0,
  'active_installs' => 10,
  'last_updated' => '2024-02-12 8:58am GMT',
  'added' => '2022-02-02',
  'homepage' => 'https:\\/\\/github.com\\/IONOS-WordPress\\/cm4all-wp-impex',
  'sections' => [
    'description' => '<p>ImpEx is a WordPress plugin that allows you to import and ...<\\/p>',
    'faq' => '<p>Impex uses modern browser features as building blocks...<\\/p>',
    'changelog' => '<p><em>Features<\\/em><\\/p>',
    'screenshots' => '<ol><li><a href="https:\\/\\/ps.w.org\\/cm4all-wp...-wp-impex\\/<\\/ol>',
    'reviews' => '',
  ],
  'download_link' => 'https:\\/\\/downloads.wordpress.org\\/plugin\\/cm4all-wp-impex.1.6.0.zip',
  'upgrade_notice' => [
    '' => '<p>There is currently no upgrade needed.<\\/p>',
  ],
  'screenshots' => [
    1 => [
      'src' => 'https:\\/\\/ps.w.org\\/cm4all-wp-impex\\/assets\\/screenshot-1.png?rev=2778231',
      'caption' => '',
    ],
  ],
  'tags' => [
    'export' => 'export',
    'import' => 'import',
    'migration' => 'migration',
  ],
  'versions' => [
    '1.1.0' => 'https:\\/\\/downloads.wordpress.org\\/plugin\\/cm4all-wp-impex.1.1.0.zip',
    '1.6.0' => 'https:\\/\\/downloads.wordpress.org\\/plugin\\/cm4all-wp-impex.1.6.0.zip',
    ...
    'trunk' => 'https:\\/\\/downloads.wordpress.org\\/plugin\\/cm4all-wp-impex.zip',
  ],
  'business_model' => false,
  'repository_url' => '',
  'commercial_support_url' => '',
  'donate_link' => '',
  'banners' => [
    'low' => 'https:\\/\\/ps.w.org\\/cm4all-wp-impex\\/assets\\/banner-772x250.png?rev=2778231',
    'high' => 'https:\\/\\/ps.w.org\\/cm4all-wp-impex\\/assets\\/banner-1544x500.png?rev=2778231',
  ],
  'preview_link' => '',
]
*/
