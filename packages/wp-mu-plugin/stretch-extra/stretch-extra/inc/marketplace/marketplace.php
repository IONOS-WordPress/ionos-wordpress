<?php

/**
 * IONOS Marketplace Integration
 *
 * Customizes the WordPress plugin installation interface to feature IONOS
 * recommended plugins and integrates with the WordPress.org plugin API.
 */

namespace ionos\stretch_extra\marketplace;

defined('ABSPATH') || exit();

if (\get_option('ionos_group_brand') !== 'ionos') {
  return;
}

\add_filter(
  hook_name: 'install_plugins_tabs',
  callback: function (array $tabs): array {
    unset($tabs['featured']);

    return [
      'ionos' => 'IONOS ' . \__('recommends', 'stretch-extra'),
      ...$tabs,
    ];
  }
);

\add_action(
  hook_name: 'install_plugins_pre_ionos',
  callback: function (): void {
    global $wp_list_table;

    $config = require_once __DIR__ . '/config.php';

    // 1. Define the plugin slugs you want
    $slugs = $config['wordpress_org_plugins'] ?? [];
    if (empty($slugs)) {
      return;
    }

    // 2. Build an array of request definitions
    $field_query_string = \http_build_query([
      'fields[short_description]' => 'short_description',
      'fields[icons]'             => 'icons',
    ]);

    $requests = [];
    foreach ($slugs as $slug) {
      $requests[] = [
        'url'  => "https://api.wordpress.org/plugins/info/1.2/?action=plugin_information&slug={$slug}&{$field_query_string}",
        'type' => \WpOrg\Requests\Requests::GET,
        'data' => [
          'locale' => \get_user_locale(),
        ],
      ];
    }

    // 3. Execute all requests simultaneously
    $responses = \WpOrg\Requests\Requests::request_multiple($requests);

    // 4. Process the data
    $plugins = [];
    foreach ($responses as $slug => $response) {
      if ($response instanceof \WpOrg\Requests\Response && $response->success) {
        $decoded_data = \json_decode($response->body, true);
        if (\json_last_error() === JSON_ERROR_NONE && isset($decoded_data['slug'])) {
          $wp_list_table->items[] = $decoded_data;
        }
      }
    }

    // 5. Sort items by original slug order
    \usort(
      $wp_list_table->items,
      fn (array $a, array $b): int => \array_search($a['slug'], $slugs, true) <=> \array_search(
        $b['slug'],
        $slugs,
        true
      )
    );

    // 6. Prepend IONOS Plugins
    $ionos_plugins = gather_infos_for_ionos_plugins($config['ionos_plugins'] ?? []);

    $wp_list_table->items = [...$ionos_plugins, ...$wp_list_table->items];
  }
);

function gather_infos_for_ionos_plugins(array $ionos_plugins): array
{

  $requests = [];
  foreach ($ionos_plugins as $plugin) {
    if( ! isset($plugin['info_url']) ) {
      continue;
    }
    $requests[] = [
      'url'   => $plugin['info_url'],
      'type'  => \WpOrg\Requests\Requests::GET,
      'slug' => $plugin['slug'] ?? '',
    ];
  }

  // 3. Execute all requests simultaneously
  $responses = \WpOrg\Requests\Requests::request_multiple($requests);

  // 4. Process the data
  $remote_data=[];
  foreach ($responses as $i => $response) {
    if ($response instanceof \WpOrg\Requests\Response && $response->success) {
      $decoded_data = \json_decode($response->body, true);
      if (\json_last_error() !== JSON_ERROR_NONE) {
        continue;
      }
      $slug = $requests[$i]['slug'] ?? '';
      $remote_data[$slug] = $decoded_data;
    }
  }

  \array_walk($ionos_plugins, function (array &$plugin) use (&$remote_data): void {
    $slug = $plugin['slug'] ?? '';
    $plugin['rating']  = 0;
    $plugin['ratings'] = [
      '5' => 0,
      '4' => 0,
      '3' => 0,
      '2' => 0,
      '1' => 0,
    ];
    $plugin['num_ratings']     = 0;
    $plugin['active_installs'] = 0;

    $plugin['last_updated']  = $remote_data[$slug]['last_updated'] ?? \date('Y-m-d', \strtotime('-2 years'));
    $plugin['version']       = $remote_data[$slug]['version']      ?? '';
    $plugin['download_link'] = $remote_data[$slug]['download_url'] ?? '';
  });

  return $ionos_plugins;
}

\add_action(
  hook_name: 'install_plugins_ionos',
  callback: function (): void {
    global $wp_list_table;

    $total_items = \count($wp_list_table->items ?? []);
    $per_page    = 10;

    $wp_list_table->set_pagination_args([
      'total_items' => $total_items,
      'total_pages' => (int) \ceil($total_items / $per_page),
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
        div.plugin-card-01-ext-ion8dhas7,
        div.plugin-card-woocommerce-german-market-light {
          .column-downloaded,
          .column-rating {
            display: none;
          }
        }

        div.plugin-card-01-ext-ion8dhas7{
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

    // no require_once, because while installing the plugin, the file is already included
    $config = require __DIR__ . '/config.php';

    $ionos_plugins = $config['ionos_plugins'] ?? [];
    if (! \array_key_exists($args->slug, $ionos_plugins)) {
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
    $pi   = \json_decode($body);

    if (! \is_object($pi) || \json_last_error() !== JSON_ERROR_NONE) {
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

function render_essentials($plugin_info, $pi, $args)
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

function render_legacy_ionos_plugins($plugin_info, $pi, $args)
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

function render_beyond_seo_info($plugin_info, $pi, $args)
{
  $pi->name          = $plugin_info['name'];
  $pi->slug          = $args->slug;
  $pi->download_link = $pi->download_url   ?? '';
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
    if (! \is_object($item) || ! isset($item->version, $item->changes)) {
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

  return \implode('', $output);
}

\add_filter(
  hook_name: 'plugins_api_result',
  callback: function (mixed $result, string $action, object $args): mixed {
    // Only target plugin searches
    if ($action !== 'query_plugins' || empty($args->search)) {
      return $result;
    }

    if (! \str_contains($args->search, 'ionos')) {
      return $result;
    }

    $config        = require __DIR__ . '/config.php';
    $ionos_plugins = gather_infos_for_ionos_plugins($config['ionos_plugins'] ?? []);

    // Prepend to the results array
    if (\is_object($result) && isset($result->plugins) && \is_array($result->plugins)) {
      $result->plugins = [...$ionos_plugins, ...$result->plugins];
    }

    return $result;
  },
  priority: 10,
  accepted_args: 3
);
