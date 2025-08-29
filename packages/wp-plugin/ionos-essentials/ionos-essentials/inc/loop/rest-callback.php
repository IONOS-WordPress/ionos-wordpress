<?php

namespace ionos\essentials\loop;

use ionos\essentials\Tenant;

defined('ABSPATH') || exit();

function _rest_loop_data(): \WP_REST_Response
{
  \add_option(IONOS_LOOP_DATACOLLECTOR_LAST_ACCESS, time());

  $core_data = [
    'generic'       => _get_generic_data(),
    'user'          => \count_users('memory'),
    'active_theme'  => _get_themes_data(),
    'active_plugins'=> _get_plugins_data(),
    'posts'         => _get_posts_and_pages_data(),
    'comments'      => _get_comments_data(),
    'events'        => _get_instance_events_data(),
    'uploads'       => _get_uploads_data(),
    'timestamp'     => _get_timestamp_of_data_collection(),
  ];

  return \rest_ensure_response($core_data);
}

function _get_generic_data(): array
{
  return [
    'locale'              => \get_locale(),
    'blog_public'         => (bool) \get_option('blog_public'),
    'market'              => _get_market(),
    'tenant'              => Tenant::get_slug(),
    'core_version'        => \get_bloginfo('version'),
    'php_version'         => PHP_VERSION,
    'installed_themes'    => count(\wp_get_themes()),
    'installed_plugins'   => count(\get_plugins()),
    'instance_created'    => _get_instance_creation_date(),
    'last_login'          => _get_last_login_date(),
    'permalink_structure' => \get_option('permalink_structure', ''),
    'siteurl'             => \get_option('siteurl', ''),
    'home'                => \get_option('home', ''),
  ];
}

function _get_market(): string
{
  return strtolower(\get_option(Tenant::get_slug() . '_market', 'de'));
}

function _get_instance_creation_date(): ?int
{
  $saved_value = \get_option('instance_creation_date');

  if ($saved_value && is_numeric($saved_value) && $saved_value > 946684800) {
    return (int) $saved_value;
  }

  $first_user        = get_users([
    'number' => 1,
  ]);
  $timestamp = strtotime($first_user[0]->user_registered . ' UTC');
  \update_option('instance_creation_date', $timestamp);
  return $timestamp;
}

function _get_last_login_date(): ?int
{
  $saved_timestamp = \get_option('last_login_date');

  if ($saved_timestamp && is_numeric($saved_timestamp) && $saved_timestamp > 946684800) {
    return (int) $saved_timestamp;
  }
  return 0;
}

function _get_themes_data(): array
{
  $current_theme = \wp_get_theme();

  $parent_theme_slug = $current_theme->parent() ? $current_theme->parent()
    ->get_stylesheet() : null;

  $auto_update_themes = \get_site_option('auto_update_themes', []);
  $current_theme_slug = $current_theme->get_stylesheet();
  $auto_update        = in_array($current_theme_slug, $auto_update_themes, true);

  return [
    [
      'id'                => $current_theme_slug,
      'version'           => $current_theme->get('Version'),
      'parent_theme_slug' => $parent_theme_slug,
      'auto_update'       => $auto_update,
    ],
  ];
}

function _get_plugins_data(): array
{
  if (! function_exists('get_plugins')) {
    require_once ABSPATH . 'wp-admin/includes/plugin.php';
  }

  $all_plugins    = \get_plugins();
  $active_plugins = \get_option('active_plugins', []);
  $auto_updates   = \get_site_option('auto_update_plugins', []);

  $active_plugins_data = [];

  foreach ($active_plugins as $plugin_slug) {
    if (isset($all_plugins[$plugin_slug])) {
      $plugin_data = $all_plugins[$plugin_slug];

      $active_plugins_data[] = [
        'plugin_slug' => $plugin_slug,
        'version'     => $plugin_data['Version'],
        'auto_update' => in_array($plugin_slug, $auto_updates),
      ];
    }
  }

  return $active_plugins_data;
}

function _get_posts_and_pages_data(): array
{
  $post_counts = \wp_count_posts('post');
  $page_counts = \wp_count_posts('page');

  $posts_data = [
    [
      'type'  => 'post',
      'count' => (int) $post_counts->publish,
    ],
    [
      'type'  => 'page',
      'count' => (int) $page_counts->publish,
    ],
  ];

  return $posts_data;
}

function _get_comments_data(): array
{
  $comments_data = [];

  $comments_data['total'] = array_sum((array) \wp_count_comments());

  $comments_data['comments_active'] = \get_option('default_comment_status') ? true : false;

  return $comments_data;
}

function _get_instance_events_data(): array
{
  $events = \get_option('instance_events', []);
  if (! is_array($events)) {
    return [];
  }
  // TODO: more sophisticated handling
  // empty after retrieval
  \update_option('instance_events', []);
  return $events;
}

function _get_uploads_data(): array
{
  $uploads_dir = \wp_get_upload_dir();
  $basedir     = $uploads_dir['basedir'];

  $file_count = 0;
  $file_size  = 0;

  if (! is_dir($basedir)) {
    return [
      'file_count' => 0,
      'file_size'  => '0',
    ];
  }

  $iterator = new \RecursiveIteratorIterator(
    new \RecursiveDirectoryIterator($basedir, \RecursiveDirectoryIterator::SKIP_DOTS)
  );

  foreach ($iterator as $file) {
    if ($file->isFile()) {
      $file_count++;
      $file_size += $file->getSize();
    }
  }

  return [
    'file_count' => $file_count,
    'file_size'  => (string) $file_size,  // as string to allow big filesize numbers
  ];
}

function _get_timestamp_of_data_collection(): int
{
  return time();
}

\add_action('wp_login', function ($user_login, $user) {
  // Log the login event
  log_instance_event('login', [
    //TODO: larsify
    'type' => isset($_GET['action']) && $_GET['action'] === 'ionos_oauth_authenticate' ? 'sso' : 'default',
  ]);
  \update_option('last_login_date', time());
}, 10, 2);

function log_instance_event(string $name, array $payload = []): void
{
  $events = \get_option('instance_events', []);

  if (! is_array($events)) {
    $events = [];
  }

  $events[] = [
    'name'      => $name,
    'payload'   => $payload,
    'timestamp' => time(),  // current Unix timestamp (UTC)
  ];

  // TODO: limit?
  // Optional: limit stored events to last 100 to avoid bloating options table
  $events = array_slice($events, -100);

  \update_option('instance_events', $events);
}
