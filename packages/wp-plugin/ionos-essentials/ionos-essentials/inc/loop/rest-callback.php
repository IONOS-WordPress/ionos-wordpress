<?php

namespace ionos\essentials\loop;

defined('ABSPATH') || exit();

require_once __DIR__ . '/../dashboard/blocks/next-best-actions/index.php';
require_once __DIR__ . '/../dashboard/blocks/next-best-actions/class-nba.php';

use FilterIterator;
use ionos\essentials\dashboard\blocks\next_best_actions\NBA;
use const ionos\essentials\dashboard\blocks\next_best_actions\OPTION_IONOS_ESSENTIALS_NBA_ACTIONS_SHOWN;
use const ionos\essentials\dashboard\blocks\next_best_actions\OPTION_IONOS_ESSENTIALS_NBA_SETUP_COMPLETED;
use ionos\essentials\Tenant;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use SplFileInfo;

const IONOS_LOOP_EVENTS_OPTION = 'ionos-loop-events';
const IONOS_LOOP_CLICKS_OPTION = 'ionos-loop-clicks';
const IONOS_LOOP_MAX_EVENTS    = 200;

function _rest_loop_callback(): \WP_REST_Response
{

  \add_option(IONOS_LOOP_DATACOLLECTOR_LAST_ACCESS, time());

  $core_data = [
    'version'       => '1.0',
    'hosting'       => _get_hosting(),
    'wordpress'     => [
      'user_data'           => \count_users('memory'),
      'active_theme'        => _get_active_theme(),
      'active_plugins'      => _get_plugins(),
      'posts'               => _get_posts_and_pages(),
      'comments'            => _get_comments(),
      'uploads'             => _get_uploads(),
      'installed_themes'    => count(\wp_get_themes()),
      'installed_plugins'   => count(\get_plugins()),
      'permalink_structure' => \get_option('permalink_structure', ''),
      'siteurl'             => \get_option('siteurl', ''),
      'home'                => \get_option('home', ''),
    ],
    'events'        => \get_option(IONOS_LOOP_EVENTS_OPTION, []),
    'clicks'        => \get_option(IONOS_LOOP_CLICKS_OPTION, []),

    'plugin_data' => [
      'ionos-essentials'    => [
        'dashboard'   => _get_dashbord_data(),
        'security'    => \get_option(\ionos\essentials\security\IONOS_SECURITY_FEATURE_OPTION, []),
        'maintenance' => \ionos\essentials\maintenance_mode\is_maintenance_mode(),
      ],
      'extendify' => [
        'extendify_onboarding_completed' => (bool) \get_option('extendify_onboarding_completed', null),
      ],
      'mcp' => [
        'settings' => \get_option('wordpress_mcp_settings', false),
        'tracking' => \get_option('ionos_loop_mcp_tracking', []),
      ],
    ],
  ];

  \delete_option(IONOS_LOOP_EVENTS_OPTION);

  return \rest_ensure_response($core_data);
}

function _rest_loop_click_callback(\WP_REST_Request $request): \WP_REST_Response
{
  $body = $request->get_json_params();

  if (! isset($body['anchor'])) {
    return \rest_ensure_response([
      'error' => 'no anchor',
    ]);
  }

  $key = sanitize_text_field($body['anchor']);

  $data = \get_option(IONOS_LOOP_CLICKS_OPTION, []);
  if (! is_array($data)) {
    $data = [];
  }

  $data[$key] = isset($data[$key]) ? $data[$key] + 1 : 1;

  \update_option(IONOS_LOOP_CLICKS_OPTION, $data);

  return \rest_ensure_response([]);
}

function _rest_sso_click_callback(\WP_REST_Request $request): \WP_REST_Response
{
  // Store the current timestamp when SSO button is clicked
  \update_option(IONOS_LOOP_SSO_CLICK_OPTION, time());

  return \rest_ensure_response([
    'success' => true,
  ]);
}

function _get_dashbord_data(): array
{
  $data = [
    'nba_status'                                => [],
    OPTION_IONOS_ESSENTIALS_NBA_SETUP_COMPLETED => \get_option(OPTION_IONOS_ESSENTIALS_NBA_SETUP_COMPLETED, null),
  ];

  $nba_status = \get_option(NBA::OPTION_STATUS_NAME, []);
  foreach ($nba_status as $key => $value) {
    $data['nba_status'][$key] = join(',', array_keys($value));
  }

  $actions_shown = \get_option(OPTION_IONOS_ESSENTIALS_NBA_ACTIONS_SHOWN, []);
  foreach ($actions_shown as $value) {
    if (! array_key_exists($value, $data['nba_status'])) {
      $data['nba_status'][$value] = null;
    }
  }

  return $data;
}

function _get_all_htaccess_md5(): array
{
  // Setup Iterators for deep search
  $iterator = new RecursiveIteratorIterator(
    new RecursiveDirectoryIterator(ABSPATH, RecursiveDirectoryIterator::SKIP_DOTS)
  );

  // Filter the iterator to only include actual .htaccess files.
  $htaccess_files = iterator_to_array(new class($iterator) extends FilterIterator {
    public function accept(): bool
    {
      $file = $this->current();
      return $file->isFile() && $file->getFilename() === '.htaccess';
    }
  });

  $result = [];

  foreach ($htaccess_files as $file) {
    $path          = str_replace(ABSPATH, '', $file->getRealPath());
    $result[$path] = _analyze_htaccess_file($file);
  }

  return $result;
}

function _analyze_htaccess_file(SplFileInfo $file): array
{
  $real_path = $file->getRealPath();
  $checksum  = md5_file($real_path);
  $content   = file_get_contents($real_path);

  if ($content === false) {
    return [
      'md5'       => $checksum,
      'use_cases' => ['htaccess_error_reading_file'],
    ];
  }

  // Normalize content for analysis
  $normalized = trim($content);

  $use_cases = [];

  // Check for exact "deny from all" (Apache 2.2 style)
  if (preg_match('/^\s*deny\s+from\s+all\s*$/im', $content)) {
    $use_cases[] = 'htaccess_deny_from_all';
  }

  // Check for Apache 2.4 "Require all denied"
  if (preg_match('/^\s*Require\s+all\s+denied\s*$/im', $content)) {
    $use_cases[] = 'htaccess_require_all_denied';
  }

  // Check for WordPress default permalink rules
  if (strpos($content, 'BEGIN WordPress') !== false && strpos($content, 'END WordPress') !== false) {
    $use_cases[] = 'htaccess_wordpress_permalinks';
  }

  // Check for index file blocking
  if (preg_match('/Options\s+-Indexes/i', $content)) {
    $use_cases[] = 'htaccess_disable_directory_listing';
  }

  // Check for redirect rules
  if (preg_match('/Redirect(Match)?\s+(301|302|permanent|temp)/i', $content) ||
      preg_match('/RewriteRule.*\[R=\d{3}\]/i', $content)) {
    $use_cases[] = 'htaccess_redirect_rules';
  }

  // Check for HTTPS enforcement
  if (preg_match('/RewriteCond.*HTTPS.*off/i', $content) ||
      preg_match('/RewriteRule.*https:/i', $content)) {
    $use_cases[] = 'htaccess_force_https';
  }

  // Check for security headers
  if (preg_match(
    '/Header\s+set\s+(X-Content-Type-Options|X-Frame-Options|X-XSS-Protection|Strict-Transport-Security|Content-Security-Policy)/i',
    $content
  )) {
    $use_cases[] = 'htaccess_security_headers';
  }

  // Check for file protection rules
  if (preg_match('/<Files[^>]*>.*deny.*<\/Files>/is', $content) ||
      preg_match('/<FilesMatch[^>]*>.*deny.*<\/FilesMatch>/is', $content)) {
    $use_cases[] = 'htaccess_file_protection';
  }

  // Check for IP blocking/allowing
  if (preg_match('/(Allow|Deny)\s+from\s+\d+\.\d+\.\d+\.\d+/i', $content) ||
      preg_match('/Require\s+(ip|not\s+ip)\s+\d+\.\d+\.\d+\.\d+/i', $content)) {
    $use_cases[] = 'htaccess_ip_filtering';
  }

  // Check for custom error pages
  if (preg_match('/ErrorDocument\s+\d{3}/i', $content)) {
    $use_cases[] = 'htaccess_custom_error_pages';
  }

  // Check for caching rules
  if (preg_match('/(mod_expires|ExpiresActive|ExpiresByType|Cache-Control)/i', $content)) {
    $use_cases[] = 'htaccess_caching_rules';
  }

  // Check for PHP settings
  if (preg_match_all('/php_(value|flag)\s+(\S+)/i', $content, $matches)) {
    foreach ($matches[2] as $setting_name) {
      $use_cases[] = 'htaccess_php_settings_' . $setting_name;
    }
  }

  // Check for RewriteEngine usage (generic rewrite rules)
  if (preg_match('/RewriteEngine\s+On/i', $content)) {
    $use_cases[] = 'htaccess_rewrite_engine_active';
  }

  // Check if file is empty
  if (empty($normalized)) {
    $use_cases[] = 'htaccess_empty_file';
  }

  // Check for WooCommerce protection patterns
  if (preg_match('/woocommerce.*uploads/i', $content)) {
    $use_cases[] = 'htaccess_woocommerce_protection';
  }

  return [
    'md5'       => $checksum,
    'use_cases' => array_unique($use_cases),
  ];
}

function _get_hosting(): array
{
  return [
    'locale'              => \get_locale(),
    'blog_public'         => (bool) \get_option('blog_public'),
    'market'              => _get_market(),
    'tenant'              => Tenant::get_slug(),
    'core_version'        => \get_bloginfo('version'),
    'php_version'         => PHP_VERSION,
    'instance_created'    => _get_instance_creation_date(),
    'htaccess_md5'        => (object) _get_all_htaccess_md5(),
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

function _get_active_theme(): array
{
  $current_theme = \wp_get_theme();

  $parent_theme_slug = $current_theme->parent() ? $current_theme->parent()
    ->get_stylesheet() : null;

  $auto_update_themes = \get_site_option('auto_update_themes', []);
  $current_theme_slug = $current_theme->get_stylesheet();
  $auto_update        = in_array($current_theme_slug, $auto_update_themes, true);

  return [
    'id'                => $current_theme_slug,
    'version'           => $current_theme->get('Version'),
    'parent_theme_slug' => $parent_theme_slug,
    'auto_update'       => $auto_update,
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
  $size       = 0;

  if (! is_dir($basedir)) {
    return [
      'file_count' => 0,
      'size'       => '0',
    ];
  }

  $iterator = new \RecursiveIteratorIterator(
    new \RecursiveDirectoryIterator($basedir, \RecursiveDirectoryIterator::SKIP_DOTS)
  );

  foreach ($iterator as $file) {
    if ($file->isFile()) {
      $file_count++;
      $size += $file->getSize();
    }
  }

  return [
    'file_count' => $file_count,
    'size'       => $size,
  ];
}
