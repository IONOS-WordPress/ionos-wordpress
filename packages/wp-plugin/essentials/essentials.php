<?php
/**
 * Plugin Name:       ionos-wordpress/essentials
 * Description:       The essentials plugins hosts IONOS hosting specific functionality.
 * Requires at least: 6.6
 * Requires Plugins:
 * Requires PHP:      8.3
 * Version:           0.0.4
 * Update URI:        https://github.com/IONOS-WordPress/ionos-wordpress/releases
 * Plugin URI:        https://github.com/IONOS-WordPress/ionos-wordpress/tree/main/packages/wp-plugin/essentials
 * License:           GPL-2.0-or-later
 * Author:            IONOS Group
 * Author URI:        https://www.ionos-group.com/brands.html
 * Domain Path:       /languages
 */

namespace ionos_wordpress\essentials;

use stdClass;

defined('ABSPATH') || exit();

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

// only needed for debugging purposes
\add_action('plugins_loaded', function () {
  // if wordpress is in development mode (https://developer.wordpress.org/reference/functions/wp_get_development_mode/)
  // force plugin update checks / disable transient caching
  if( array_search(\wp_get_development_mode(), ['all', 'plugin']) !== false) {
    \delete_site_transient( 'update_plugins' );
  }
});

\add_filter('update_plugins_github.com', function( array|false $update, array $plugin_data, string $plugin_file, array $locales) : array|false {
  if ($plugin_file === \plugin_basename(__FILE__)) {
    $update = '{
      "version": "0.3.9",
      "slug": "essentials/essentials.php",
      "tested": "6.6",
      "icons": {
        "svg": "https://example.com/icon.svg"
      },
      "package": "https://github.com/IONOS-WordPress/ionos-wordpress/releases/download/%40ionos-wordpress%2Fessentials%400.0.4/essentials-0.0.4-php7.4.zip"
    }';
    $update = json_decode( $update, true );
		// $request = wp_remote_get($plugin_data['UpdateURI']);
		// $request_body = wp_remote_retrieve_body( $request );
		// $update = json_decode( $request_body, true );
	}
	return $update;
}, 10, 4);

add_filter( "plugins_api", function ( \stdClass|false $result, string $action, \stdClass $args) : \stdClass|false {
  if( $args->slug !== 'essentials/essentials.php' ) {
    return $result;
  }

  $plugin_data = \get_plugin_data( ABSPATH . 'wp-content/plugins/' . $args->slug, false, false );

  $result = (object) [
    // 'name' => $plugin_data['Name'],
    // 'slug' => $args->slug,
    // 'version' => $plugin_data['Version'],
    'sections' => [
      'changelog' => '<h4>This is the Changelog</h4>',
      // 'description' => '<h4>This is the Description</h4>',
      // 'faq' => '<h4>This is the FAQ</h4>',
    ]
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
