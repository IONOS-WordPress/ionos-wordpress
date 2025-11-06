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

\add_filter(
  hook_name: 'update_plugins_github.com',
  accepted_args: 3,
  callback: function (array|false $update, array $plugin_data, string $plugin_slug): array|false {

    if (\plugin_basename(PLUGIN_FILE) !== $plugin_slug) {
      return $update;
    }

    // get the redirect URL from the UpdateURI
    $res = \wp_remote_get($plugin_data['UpdateURI'], [
      'headers' => [
        'Accept' => 'application/json',
      ],
    ]);

    // if the request was successful
    if ((200 === \wp_remote_retrieve_response_code($res)) || ('' !== \wp_remote_retrieve_body($res))) {
      $info_json = json_decode($res['body'], true);

      return $info_json;
    }

    if ((200 !== \wp_remote_retrieve_response_code($res))) {
      error_log(
        sprintf(
          'Failed to fetch latest update information from "%s"(http-status=%s) : %s',
          $plugin_data['UpdateURI'],
          \wp_remote_retrieve_response_code($res),
          '' !== \wp_remote_retrieve_body($res) ? \wp_remote_retrieve_body($res) : 'response body was empty',
        )
      );
    }

    return $update;
  }
);

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

    // fetch changelog from github
    $res = \wp_remote_get(
      'https://raw.githubusercontent.com/IONOS-WordPress/ionos-wordpress/refs/heads/main/packages/wp-plugin/ionos-essentials/CHANGELOG.md',
      [
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
      // show error message including link in the changelog section
      $result->sections['changelog'] = \esc_html(
        sprintf(
          // translators: first placeholder for the url, second for the plugin name, last one for the response code
          \__('Failed to download <a href=\"%1$s\">%2$s-info.json</a>(response status=%3$s)', 'ionos-essentials'),
          $plugin_data['UpdateURI'],
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
