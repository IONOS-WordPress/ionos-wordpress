<?php

/**
 * IONOS Marketplace Integration
 *
 * Customizes the WordPress plugin installation interface to feature IONOS
 * recommended plugins and integrates with the WordPress.org plugin API.
 */

namespace ionos\ionos_core\marketplace;

use WpOrg\Requests\Requests;
use WpOrg\Requests\Response;

defined('ABSPATH') || exit();

// Set flag indicating ionos-core marketplace is active before any hooks
\define('IONOS_CORE_MARKETPLACE_ACTIVE', true);

// Allow disabling marketplace for testing or migration purposes
if (\get_option('ionos_core_disable_marketplace')) {
  return;
}

// DEBUG NOTICE: Confirms file has been loaded
\add_action('admin_notices', function (): void {
  \printf(
    '<div class="notice notice-info is-dismissible"><p><strong>[DEBUG]:</strong> Marketplace file was successfully loaded!</p></div>'
  );
});

if (! \is_blog_installed()) {
  return;
}

// Uninstall legacy ionos-marketplace plugin when ionos-core marketplace is active
\add_action('admin_init', function (): void {
  // Check if legacy ionos-marketplace plugin is installed
  $legacy_marketplace_plugins = [
    'ionos-marketplace/marketplace.php',
  ];

  // Get list of all plugins (both active and inactive)
  $all_plugins = \get_plugins();
  $active_plugins = \get_option('active_plugins', []);

  // Check which legacy plugins are installed
  $installed_legacy = [];
  foreach ($legacy_marketplace_plugins as $plugin_path) {
    if (\array_key_exists($plugin_path, $all_plugins)) {
      $installed_legacy[] = $plugin_path;
    }
  }

  if (! empty($installed_legacy)) {
    // First, deactivate if active
    foreach ($installed_legacy as $plugin) {
      if (\in_array($plugin, $active_plugins, true)) {
        \deactivate_plugins($plugin, false, false);
      }
    }

    // Then delete the plugin files
    foreach ($installed_legacy as $plugin) {
      \delete_plugins([$plugin]);
    }

    // Show admin notice about uninstallation
    \add_action('admin_notices', function () use ($installed_legacy): void {
      \printf(
        '<div class="notice notice-success is-dismissible"><p>%s</p></div>',
        \esc_html(
          \sprintf(
            \__('The legacy ionos-marketplace plugin (%s) has been automatically uninstalled. Marketplace functionality is now provided by IONOS Core.', 'ionos-core'),
            \implode(', ', $installed_legacy)
          )
        )
      );
    });
  }
});

function get_config()
{
  static $config = null;

  if ($config === null) {
    $base_config = require_once __DIR__ . '/config.php';
    $tenant = strtolower(\get_option('ionos_group_brand', 'ionos'));

    $tenant_additions = $base_config['tenant_additions'][$tenant] ?? null;

    $config = [
      'ionos_plugins'       => $base_config['ionos_plugins'] ?? [],
      'wordpress_org_plugins' => $base_config['wordpress_org_plugins'] ?? [],
    ];

    if ($tenant_additions) {
      $add_ionos = $tenant_additions['ionos_plugins'] ?? [];
      $remove_ionos = $tenant_additions['remove_ionos_plugins'] ?? [];

      foreach ($add_ionos as $slug) {
        if (isset($base_config['ionos_plugins'][$slug])) {
          $config['ionos_plugins'][$slug] = $base_config['ionos_plugins'][$slug];
        }
      }

      foreach ($remove_ionos as $slug) {
        unset($config['ionos_plugins'][$slug]);
      }

      foreach ($tenant_additions['wordpress_org_plugins'] as $slug) {
        if (!\in_array($slug, $config['wordpress_org_plugins'], true)) {
          $config['wordpress_org_plugins'][] = $slug;
        }
      }
    }
  }

  return $config;
}

function get_localized_config(string $key): mixed
{
  $language = \strtolower(\explode('_', \get_locale())[0]);
  $config   = \get_option($key . '.' . $language);

  if (! $config) {
    $config = \get_option($key . '.en');
  }

  return $config ? \json_decode($config) : null;
}

\add_filter(
  hook_name: 'install_plugins_tabs',
  callback: function (array $tabs): array {
    unset($tabs['featured']);

    return [
      'ionos' => 'IONOS ' . \__('recommends', 'ionos-core'),
      ...$tabs,
    ];
  }
);

\add_action(
  hook_name: 'install_plugins_pre_ionos',
  callback: function (): void {
    global $wp_list_table;

    $config = get_config();

    $slugs = $config['wordpress_org_plugins'] ?? [];
    if (empty($slugs)) {
      return;
    }
    $field_query_string = \http_build_query([
      'fields[short_description]' => 'short_description',
      'fields[icons]'             => 'icons',
    ]);

    $requests = [];
    foreach ($slugs as $slug) {
      $requests[] = [
        'url'  => "https://api.wordpress.org/plugins/info/1.2/?action=plugin_information&slug={$slug}&{$field_query_string}",
        'type' => Requests::GET,
        'data' => [
          'locale' => \get_user_locale(),
        ],
      ];
    }

    $responses = Requests::request_multiple($requests);

    $plugins                = [];
    $admin_notice_displayed = false;
    foreach ($responses as $index => $response) {
      $slug = $slugs[$index] ?? 'unknown';
      if ($response instanceof Response && $response->success) {
        $decoded_data = json_decode($response->body, true);
        if (isset($decoded_data['slug'])) {
          $wp_list_table->items[] = $decoded_data;
        }
      } else {
        if (! $admin_notice_displayed) {
          add_action(
            hook_name: 'admin_notices',
            callback: function () use ($slug, $response): void {
              $error_message = 'Unknown error';
              if ($response instanceof Response && isset($response->status_code)) {
                $error_message = sprintf('HTTP %d', $response->status_code);
              } elseif (\is_wp_error($response)) {
                $error_message = $response->get_error_message();
              }

              \printf(
                '<div class="notice notice-warning is-dismissible"><p>%s</p></div>',
                \esc_html(\sprintf('Failed to fetch data in marketplace for plugin "%s": %s', $slug, $error_message))
              );
            }
          );
          $admin_notice_displayed = true;
        }
      }
    }

    if (empty($wp_list_table->items)) {
      $wp_list_table->items = [];
    }

    \usort(
      $wp_list_table->items,
      fn (array $a, array $b): int => array_search($a['slug'], $slugs, true) <=> array_search(
        $b['slug'],
        $slugs,
        true
      )
    );

    $ionos_plugins_list = gather_infos_for_ionos_plugins($config['ionos_plugins'] ?? []);

    $wp_list_table->items = [...$ionos_plugins_list, ...$wp_list_table->items];
  }
);

function gather_infos_for_ionos_plugins(array $ionos_plugins): array
{
  $requests = [];
  foreach ($ionos_plugins as $plugin) {
    if (! isset($plugin['info_url'])) {
      continue;
    }
    $requests[] = [
      'url'  => $plugin['info_url'],
      'type' => Requests::GET,
      'slug' => $plugin['slug'] ?? '',
    ];
  }

  // Execute all requests simultaneously
  $responses = Requests::request_multiple($requests);

  // Process the data
  $remote_data = [];
  foreach ($responses as $i => $response) {
    if (! ($response instanceof Response) || ! $response->success) {
      continue;
    }

    $decoded_data = json_decode($response->body, true);
    if ($decoded_data===null) {
      continue;
    }

    $slug               = $requests[$i]['slug'] ?? '';
    $remote_data[$slug] = $decoded_data;
  }

  \array_walk($ionos_plugins, function (array &$plugin) use ($remote_data): void {
    $slug                      = $plugin['slug'] ?? '';
    $plugin['rating']          = 0;
    $plugin['ratings']         = [
      '5' => 0,
      '4' => 0,
      '3' => 0,
      '2' => 0,
      '1' => 0,
    ];
    $plugin['num_ratings']     = 0;
    $plugin['active_installs'] = 0;

    $plugin['last_updated'] = $remote_data[$slug]['last_updated'] ?? \date('Y-m-d', \strtotime('-2 years'));
    $plugin['version']      = $remote_data[$slug]['version']      ?? '';

    if (isset($remote_data[$slug]['download_url'])) {
      $plugin['download_link'] = $remote_data[$slug]['download_url'];
    }
  });

  return $ionos_plugins;
}

\add_action(
  hook_name: 'install_plugins_ionos',
  callback: function (): void {
    global $wp_list_table;

    $total_items = count($wp_list_table->items ?? []);
    $per_page    = 10;

    $wp_list_table->set_pagination_args([
      'total_items' => $total_items,
      'total_pages' => (int) ceil($total_items / $per_page),
      'per_page'    => $per_page,
    ]);

    \display_plugins_table();
  }
);

\add_action(
  hook_name: 'admin_head-plugin-install.php',
  callback: function (): void {
    \printf(
      <<<HTML
      <style>
        div[class*="plugin-card-ionos-"],
        div.plugin-card-beyond-seo,
        div.plugin-card-01-ext-ion8dhas7-stretch,
        div.plugin-card-woocommerce-german-market-light {
          .column-downloaded,
          .column-rating {
            display: none;
          }
        }

        div.plugin-card-01-ext-ion8dhas7-stretch{
          .plugin-action-buttons{
            .open-plugin-details-modal{
              display: none;
            }
          }
        }
      </style>
      HTML
    );
  }
);

\add_filter(
  hook_name: 'plugins_api',
  callback: function (mixed $result, string $action, object $args): mixed {
    if ($action !== 'plugin_information' || ! isset($args->slug)) {
      return $result;
    }

    $config = get_config();

    $ionos_plugins = $config['ionos_plugins'] ?? [];
    if (! array_key_exists($args->slug, $ionos_plugins)) {
      return $result;
    }

    // No info_url means that there is no additional info to fetch, so we can return the basic info from config.php. Site Assistant uses this.
    $plugin_info = $ionos_plugins[$args->slug];
    if (! isset($plugin_info['info_url'])) {
      return (object) $ionos_plugins[$args->slug];
    }

    $response = \wp_remote_get($plugin_info['info_url']);
    if (\is_wp_error($response) || \wp_remote_retrieve_response_code($response) !== 200) {
      return $result;
    }

    $body = \wp_remote_retrieve_body($response);
    $pi   = json_decode($body);

    if (! is_object($pi)) {
      return $result;
    }

    if ($args->slug === 'beyond-seo') {
      return render_beyond_seo_info($plugin_info, $pi, $args);
    }

    if (str_contains($args->slug, 'ionos-essentials')) {
      return render_essentials($plugin_info, $pi, $args);
    }

    return render_legacy_ionos_plugins($plugin_info, $pi, $args);
  },
  priority: 20,
  accepted_args: 3
);

function render_essentials(array $plugin_info, object $pi, object $args): object
{
  $pi->name          = $plugin_info['name'];
  $pi->slug          = $args->slug;
  $pi->download_link = $pi->download_url   ?? '';
  $pi->version       = $pi->latest_version ?? '';
  $pi->requires      = '6.0';
  $pi->sections      = [
    \_x('Description', 'Plugin installer section title') => $plugin_info['short_description'],
    \_x('Changelog', 'Plugin installer section title')   => $pi->sections->changelog ?? '',
  ];

  return $pi;
}

function render_legacy_ionos_plugins(array $plugin_info, object $pi, object $args): object
{
  $pi->name          = $plugin_info['name'];
  $pi->slug          = $args->slug;
  $pi->download_link = $pi->download_url   ?? '';
  $pi->version       = $pi->latest_version ?? '';
  $pi->requires      = '6.0';
  $pi->sections      = [
    \_x('Description', 'Plugin installer section title') => $plugin_info['short_description'],
    \_x('Changelog', 'Plugin installer section title')   => render_changelog($pi->changelog ?? []),
  ];

  return $pi;
}

function render_beyond_seo_info(array $plugin_info, object $pi, object $args): object
{
  $pi->name          = $plugin_info['name'];
  $pi->slug          = $args->slug;
  $pi->download_link = $pi->download_url ?? '';
  $pi->sections      = (array) $pi->sections;
  $pi->banners       = [];

  return $pi;
}

function render_changelog(array $changelog): string
{
  if (empty($changelog)) {
    return '';
  }

  $output = [];

  foreach ($changelog as $item) {
    if (! is_object($item) || ! isset($item->version, $item->changes)) {
      continue;
    }

    $version  = \esc_html($item->version);
    $output[] = "<h4>{$version}</h4><ul>";

    if (\is_array($item->changes)) {
      foreach ($item->changes as $change) {
        $escaped_change = \esc_html($change);
        $output[]       = "<li>{$escaped_change}</li>";
      }
    }

    $output[] = '</ul>';
  }

  return implode('', $output);
}

\add_filter(
  hook_name: 'plugins_api_result',
  callback: function (mixed $result, string $action, object $args): mixed {
    // Only target plugin searches
    if ($action !== 'query_plugins' || empty($args->search)) {
      return $result;
    }

    $config        = get_config();
    $ionos_plugins = gather_infos_for_ionos_plugins($config['ionos_plugins'] ?? []);

    if ($args->search !== 'ionos') {
      $ionos_plugins = array_filter(
        $ionos_plugins,
        fn (array $plugin): bool => str_contains(
          strtolower(($plugin['short_description'] ?? '') . ($plugin['name'] ?? '') . ($plugin['slug'] ?? '')),
          strtolower(urldecode($args->search))
        )
      );
    }

    // Prepend to the results array
    if (is_object($result) && isset($result->plugins) && \is_array($result->plugins) && ! empty($ionos_plugins)) {
      $result->plugins = [...$ionos_plugins, ...$result->plugins];
    }

    return $result;
  },
  accepted_args: 3
);
