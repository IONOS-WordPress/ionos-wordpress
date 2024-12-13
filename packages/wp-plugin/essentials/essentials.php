<?php
/**
 * Plugin Name:       ionos-wordpress/essentials
 * Description:       The essentials plugin provides IONOS hosting specific functionality.
 * Requires at least: 6.6
 * Requires Plugins:
 * Requires PHP:      8.3
 * Version:           0.0.4
 * Update URI:        https://api.github.com/repos/IONOS-WordPress/ionos-wordpress/releases
 * Plugin URI:        https://github.com/IONOS-WordPress/ionos-wordpress/tree/main/packages/wp-plugin/essentials
 * License:           GPL-2.0-or-later
 * Author:            IONOS Group
 * Author URI:        https://www.ionos-group.com/brands.html
 * Domain Path:       /languages
 */

namespace ionos_wordpress\essentials;

defined('ABSPATH') || exit();

/* this is just demo code how to use enums */
enum Mode: string
{
  case LOCALE = 'local';
  case REMOTE = 'remote';
}

function foo(Mode $mode, int $count): void
{
  // phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log
  error_log("mode={$mode}, count={$count}");
}
/* -- */

\add_action(
  'init',
  fn () => \load_plugin_textdomain(domain: 'essentials', plugin_rel_path: basename(__DIR__) . '/languages/')
);

\add_action('init', function () {
  $translated_text = \__('Hello World !', 'essentials');
  // phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log
  error_log($translated_text);
});

// only needed for debugging purposes
if (array_search(\wp_get_development_mode(), ['all', 'plugin']) !== false) {
  // if wordpress is in development mode (https://developer.wordpress.org/reference/functions/wp_get_development_mode/)
  // force plugin update checks / disable transient caching
  \add_action('plugins_loaded', fn() => \delete_site_transient('update_plugins'));
}

\add_filter('update_plugins_api.github.com', function (
  array|false $update,
  array $plugin_data,
  string $plugin_slug,
  array $locales
): array|false {
  if ($plugin_slug === \plugin_basename(__FILE__)) {
    // get the update information from github releases
    $res = \wp_remote_get($plugin_data['UpdateURI'], [
      'headers' => [
        'Accept' => 'application/json',
      ],
    ]);
    // abort if the request failed or the response code is not 200 or the response body is empty
    if ((\wp_remote_retrieve_response_code($res) !== 200) || ('' === \wp_remote_retrieve_body($res))) {
      return $update;
    }

    // releases is an array of release objects
    $releases = json_decode($res['body'], true);
    if (json_last_error() !== JSON_ERROR_NONE) {
      return $update;
    }

    // we filter out all releases that do not contain the plugin name
    // (remember example: plugin slug is 'essential/essentials.php' and the plugin name is 'ionos-wordpress/essentials')
    $releases = array_filter($releases, fn ($release) => str_contains($release['name'], $plugin_data['Name']));

    // return if no releases for our plugin are found
    if (empty($releases)) {
      return $update;
    }

    // convert the releases array to an associative array with the name as key
    $releases = array_column($releases, null, 'name');

    // get the latest release by sorting the releases names in natural order
    // example release name : '@ionos-wordpress/essentials@0.0.4'
    $releaseNames = array_keys($releases);
    natsort($releaseNames);

    // get the latest release name
    $latestReleaseName = end($releaseNames);

    // extract version from release name
    // (example: '@ionos-wordpress/essentials@0.0.4' => '0.0.4')
    $version = end(explode('@', $latestReleaseName));

    // example value : '0.0.4'
    $latestRelease = $releases[$latestReleaseName];

    // example $assetNameRegExp : '/essentials-0\.\0\.4-php.*\.zip/'
    $assetNameRegExp = '/'
      . preg_quote(end(explode('/', $plugin_data['Name']))) // 'ionos-wordpress/essentials' => 'essentials'
      . '-' . preg_quote($version, '/') // '0\.0\.4'
      . '-php.*\.zip/';

    // find the asset that matches the asset name regular expression
    // and return the $update data if found
    foreach ($latestRelease['assets'] as $asset) {
      if (preg_match($assetNameRegExp, $asset['name'])) {
        return [
          'version' => $version,
          'package' => $asset['browser_download_url'],
          'changelog' => '<h4>This is the Changelog</h4>'
        ];
      }
    }

    // // this is just an example how the $update array should look like
    // $update = [
    //   'version' => '0.0.6',
    //   'package' => 'https://github.com/IONOS-WordPress/ionos-wordpress/releases/download/%40ionos-wordpress%2Fessentials%400.0.4/essentials-0.0.4-php7.4.zip',
    // ];
  }
  return $update;
}, 10, 4);

/*
If testing from a local IP then the filter below is required. WordPress uses wp_safe_remote_get() when downloading plugin packages.
wp_safe_remote_get() sets $args['reject_unsafe_urls'] to true which will reject local IPs.
 */

/*
add_filter('http_request_host_is_external', function($external, $host, $url) {
	$external = $host == "example.com" ? true : $external;
	return $external;
},10, 3);
 */

\add_filter('plugins_api', function (\stdClass|false $result, string $action, \stdClass $args): \stdClass|false {
  if ($args->slug !== \plugin_basename(__FILE__)) {
    return $result;
  }

  $plugin_data = \get_plugin_data(ABSPATH . 'wp-content/plugins/' . $args->slug, false, false);

  $result = (object) [
    // 'name' => $plugin_data['Name'],
    // 'slug' => $args->slug,
    // 'version' => $plugin_data['Version'],
    'sections' => [
      'changelog' => '<h4>This is the Changelog</h4>',
      // 'description' => '<h4>This is the Description</h4>',
      // 'faq' => '<h4>This is the FAQ</h4>',
    ],
  ];

  // Update the $result variable according to your website requirements and return this variable. You can modify the $result variable conditionally too if you want.
  return $result;
}, 10, 3);

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
    'screenshots' => '<ol><li><a href="https:\\/\\/ps.w.org\\/cm4all-wp-impex\\/assets\\/screenshot-1.png?rev=2778231"><img src="https:\\/\\/ps.w.org\\/cm4all-wp-impex\\/assets\\/screenshot-1.png?rev=2778231" alt=""><\\/a><\\/li><\\/ol>',
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
