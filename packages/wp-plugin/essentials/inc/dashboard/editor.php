<?php

namespace ionos_wordpress\essentials\dashboard;

/*
    This file features the editing part of the ionos dashboard features.
    This file defines a custom post type for an IONOS Dashboard in WordPress.
    It registers the custom post type, assigns a custom template to it, and ensures that the template is always used.
    It also handles the creation of an initial dashboard post if none exists, and persists the rendered HTML page and post content.
    Additionally, it removes unnecessary feed links from the custom post type page and converts enqueued style files into inline styles.
 */

const POST_TYPE_TEMPLATE_SLUG = 'custom-dashboard-template';

const DASHBOARD_POST_TITLE = 'Custom IONOS Dashboard';

// register our initial custom dashboard post type and register/assign the template with it
\add_action('init', function () {
  define('GLOBAL_STYLES_FILE', __DIR__ . '/data/' . \get_stylesheet() . '-global-styles.json');
  $post_type = \register_post_type(
    POST_TYPE_SLUG,
    [
      'labels' => [
        'name'          => 'IONOS Dashboards',
        'singular_name' => 'IONOS Dashboard',
      ],
      'public'             => true,
      'publicly_queryable' => true,
      'show_in_menu'       => 'options-general.php',
      'show_in_admin_bar'  => true,
      'show_in_rest'       => true,
      'supports'           => ['title', 'editor'],
    ]
  );

  \register_block_template('ionos-dashboard-page//' . POST_TYPE_TEMPLATE_SLUG, [
    'title'   => POST_TYPE_TEMPLATE_SLUG,
    'content' => (function () {
      ob_start();
      require_once(__DIR__ . '/data/block-template.php');
      $output = ob_get_contents();
      ob_end_clean();
      return $output;
    })(),
    'post_type' => POST_TYPE_SLUG,
  ]);
});

\add_action('admin_init', function () {
  if (wp_doing_ajax()) {
    return;
  }
  // create dashboard posts for all saved dashboards
  $ionos_dashboard_names = array_column(
    \get_posts([
      'post_type'      => POST_TYPE_SLUG,
      'posts_per_page' => -1,
    ]),
    'post_name'
  );
  $dashboard_dirs = glob(__DIR__ . '/data/*', GLOB_ONLYDIR) ?: [];
  $dashboard_dirs = array_filter($dashboard_dirs, fn ($dir) => is_file($dir . '/post_content.html'));
  foreach ($dashboard_dirs as $dir) {
    $name = basename($dir);
    if (in_array($name, $ionos_dashboard_names, true)) {
      continue;
    }
    \wp_insert_post([
      'post_title'   => $name,
      'post_name'    => $name,
      'post_content' => file_get_contents($dir . '/post_content.html'),
      'post_status'  => 'publish',
      'post_author'  => 1,
      'post_type'    => POST_TYPE_SLUG,
      // 'page_template' => POST_TYPE_TEMPLATE_SLUG // templates registered by plugins are not supported yet (as of WP 6.7)
    ]);
  }
});

// always assign our custom template to our custom post type
\add_action(
  hook_name: 'wp_after_insert_post',
  callback: function (int $post_id, \WP_Post $post, bool $update): void {
    if (POST_TYPE_SLUG !== $post->post_type) {
      return;
    }

    // if the post has not yet our template assigned, assign it
    if (POST_TYPE_TEMPLATE_SLUG !== \get_post_meta($post_id, '_wp_page_template', true)) {
      \update_post_meta($post_id, '_wp_page_template', POST_TYPE_TEMPLATE_SLUG);
    }

    // only prerender if the post is published
    if ('publish' === $post->post_status && $update) {
      _persist_dashboard($post);
    }
  },
  accepted_args: 3,
);

/**
 * fetches published post and persist the rendered html page and post_content
 */
function _persist_dashboard(\WP_Post $post): void
{
  $permalink = \get_permalink($post->ID);

  // fetch rendered page
  // if we are in development mode and the permalink is not localhost, replace the host with host.docker.internal
  if (
    false !== array_search(\wp_get_development_mode(), ['all', 'plugin'], true) &&
    // indicates wp-env environment
    function_exists('\getenv_docker') &&
    'localhost' === \wp_parse_url($permalink, PHP_URL_HOST)
  ) {
    // replace the host with host.docker.internal to get the request working
    $permalink = str_replace(\wp_parse_url($permalink, PHP_URL_HOST), 'host.docker.internal', $permalink);
  }

  // fetch rendered page
  $res = \wp_remote_get($permalink);

  $post_base_path = __DIR__ . "/data/{$post->post_name}";
  if (! is_dir($post_base_path)) {
    mkdir($post_base_path, 0775, true);
  }

  // abort if the request failed or the response code is not 200 or the response body is empty
  if ((200 !== \wp_remote_retrieve_response_code($res)) || ('' === \wp_remote_retrieve_body($res))) {
    // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
    \wp_die("Failed to fetch rendered page(url={$permalink}): " . print_r($res, true));
  }

  $html = $res['body'];

  # strip page content
  $start_marker_pos = strpos($html, POST_TYPE_TEMPLATE_CONTENT_START_MARKER);
  if (false === $start_marker_pos) {
    $error_message = sprintf(
      'Could not find start marker "%s" in file "%s"',
      \esc_html(POST_TYPE_TEMPLATE_CONTENT_START_MARKER),
      \esc_html($html)
    );
    // phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log
    error_log($error_message);
    // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
    \wp_die($error_message);
  }

  $end_marker_pos = strpos($html, POST_TYPE_TEMPLATE_CONTENT_END_MARKER, $start_marker_pos);
  if (false === $end_marker_pos) {
    $error_message = sprintf(
      'Could not find end marker "%s" in file "%s"',
      \esc_html(POST_TYPE_TEMPLATE_CONTENT_END_MARKER),
      \esc_html($html)
    );
    // phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log
    error_log($error_message);
    // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
    \wp_die($error_message);
  }

  // workaround :	the core/post-content block encapsulates the post content in a <div class="..."> tag
  // we need to fake this behaviour by adding the div to the post content
  $snippet = '<div class="entry-content wp-block-post-content has-global-padding is-layout-constrained wp-block-post-content-is-layout-constrained">'
      . "\n"
      . POST_TYPE_TEMPLATE_CONTENT_START_MARKER
      . POST_TYPE_TEMPLATE_CONTENT_END_MARKER
      . "\n"
      . '</div>'
  ;

  // strip post content from fetched html
  $html = substr_replace(
    $html,
    $snippet,
    $start_marker_pos,
    $end_marker_pos - $start_marker_pos + strlen(POST_TYPE_TEMPLATE_CONTENT_END_MARKER)
  );

  file_put_contents("{$post_base_path}/rendered-skeleton.html", $html);

  // save post content
  file_put_contents("{$post_base_path}/post_content.html", $post->post_content);
}

// remove feed links from our custom post type page
\add_action('wp', function () {
  if (\is_singular(POST_TYPE_SLUG)) {
    // removes various useless '<link...' tags
    \remove_action('wp_head', 'feed_links_extra', 3);
    \remove_action('wp_head', 'feed_links', 2);
    \remove_action('wp_head', 'rsd_link');
    \remove_action('wp_head', 'wp_shortlink_wp_head');
    \remove_action('wp_head', 'wp_generator');
    \remove_action('wp_head', 'rel_canonical');
    \remove_action('wp_head', 'wp_oembed_add_discovery_links');
    \remove_action('wp_head', 'wp_resource_hints', 2);
  }
});

// convert all enqueued style files into inline styles
// to enable us to capture everything in one file
\add_action('wp_print_styles', function () {
  // only do this on our custom dashboard page
  if (\is_singular(POST_TYPE_SLUG)) {
    $wp_styles = \wp_styles();

    // get only queued style files
    $queued_styles = array_map(fn ($handle) => $wp_styles->registered[$handle], $wp_styles->queue);

    // convert referenced styles into inline style references
    foreach ($queued_styles as $style) {
      $css_url = $style->src;
      if ($css_url) {
        $style->src  = false;
        $css_file    = \wp_parse_url($css_url, PHP_URL_PATH);
        $css_content = file_get_contents(\wp_normalize_path(ABSPATH . $css_file));
        \wp_add_inline_style($style->handle, $css_content);
      }
    }
  }
});

// persist global styles when they are edited and saved in the site-editor
\add_action(
  hook_name: 'rest_after_insert_wp_global_styles',
  callback: function ($post) {
    $data = json_decode($post->post_content, true);
    if (false === file_put_contents(GLOBAL_STYLES_FILE, \wp_json_encode($data, JSON_PRETTY_PRINT))) {
      \wp_die('Failed to save global styles to file');
    }
  }
);

// when the global styles post is created, merge the global styles from the file into the post
\add_filter(
  hook_name: 'wp_insert_post_data',
  callback: function ($data, $postarr, $unsanitized_postarr, $update) {
    if (file_exists(GLOBAL_STYLES_FILE) && 'wp_global_styles' === $data['post_type'] && ! $update) {
      // post_content can be slashed and is expected slashed after the filter
      $post_content   = json_decode(wp_unslash($data['post_content']), true);
      $data_from_file = \wp_json_file_decode(GLOBAL_STYLES_FILE, [
        'associative' => true,
      ]);
      $new_data             = array_merge($post_content, $data_from_file);
      $data['post_content'] = wp_slash(\wp_json_encode($new_data));
    }
    return $data;
  },
  accepted_args: 4
);
