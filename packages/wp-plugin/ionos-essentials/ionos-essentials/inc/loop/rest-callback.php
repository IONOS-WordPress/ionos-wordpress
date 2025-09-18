<?php

namespace ionos\essentials\loop;

use ionos\essentials\Tenant;

defined('ABSPATH') || exit();

const IONOS_LOOP_EVENTS_OPTION = 'ionos-loop-events';
const IONOS_LOOP_MAX_EVENTS    = 200;

function _rest_loop_callback(): \WP_REST_Response
{


  // $message = [
  //   'text' => 'Der Datacollector kam vorbei auf ' . get_home_url(),
  // ];

  // $ch = curl_init('https://chat.googleapis.com/v1/spaces/AAAAawvt2Z8/messages?key=AIzaSyDdI0hCZtE6vySjMm-WEfRq3CPzqKqqsHI&token=EK5V7YWZ0bwEOr2CEMCwTOx0SgXE1D6B1Hq_IUO0_4k');
  // curl_setopt($ch, CURLOPT_POST, true);
  // curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
  // curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($message));
  // curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
  // curl_exec($ch);
  // curl_close($ch);



  \add_option(IONOS_LOOP_DATACOLLECTOR_LAST_ACCESS, time());

  $core_data = [
    'generic'       => _get_generic(),
    'user'          => \count_users('memory'),
    'active_theme'  => _get_themes(),
    'active_plugins'=> _get_plugins(),
    'posts'         => _get_posts_and_pages(),
    'comments'      => _get_comments(),
    'events'        => \get_option(IONOS_LOOP_EVENTS_OPTION, []),
    'uploads'       => _get_uploads(),
  ];

  // empty events after retrieval
  \delete_option(IONOS_LOOP_EVENTS_OPTION);
  return \rest_ensure_response($core_data);
}

function _get_generic(): array
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

function _get_themes(): array
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

function _get_plugins(): array
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

function _get_posts_and_pages(): array
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

function _get_comments(): array
{
  $comments_data = [];

  $comments_data['total'] = array_sum((array) \wp_count_comments());

  $comments_data['comments_active'] = \get_option('default_comment_status') ? true : false;

  return $comments_data;
}

function _get_uploads(): array
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

\add_action('wp_login', function () {
  // Log the login event
  log_loop_event('login', [
    'type' => ($_GET['action'] ?? '') === 'ionos_oauth_authenticate' ? 'sso' : 'default',
  ]);
});

function log_loop_event(string $name, array $payload = []): void
{
  $events = \get_option(IONOS_LOOP_EVENTS_OPTION, []);

  if (! is_array($events)) {
    $events = [];
  }

  $events[] = [
    'name'      => $name,
    'payload'   => $payload,
    'timestamp' => time(),  // current Unix timestamp (UTC)
  ];

  // Optional: limit stored events to avoid bloating options table
  $events = array_slice($events, -IONOS_LOOP_MAX_EVENTS);

  \update_option(IONOS_LOOP_EVENTS_OPTION, $events);
}
