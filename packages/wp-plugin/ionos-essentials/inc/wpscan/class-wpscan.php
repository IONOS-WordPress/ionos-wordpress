<?php

namespace ionos\essentials\wpscan;

class WPScan
{
  /**
   * @var array
   */
  private $issues;

  /**
   * @var bool
   */
  private $error;

  public function __construct()
  {
    $this->issues = \get_transient('ionos_wpscan_issues');

    if (false === $this->issues) {
      $data         = $this->download_wpscan_data();
      if (empty($data)) {
        error_log('WPScan middleware: No data received');
        $this->issues = [];
        $this->error  = true;
        return;
      }
      $data         = $this->convert_middleware_data($data);

      $next_run = (strpos(json_encode($data), 'UNKNOWN')) ? 5 * MINUTE_IN_SECONDS : 6 * HOUR_IN_SECONDS;

      \set_transient('ionos_wpscan_last_scan', time(), $next_run);
      \set_transient('ionos_wpscan_issues', $data, $next_run);

      $this->issues = $data;
    }
  }

  public function has_error()
  {
    return $this->error;
  }

  public function get_issues($filter = null)
  {
    // Filter out issues for plugins/themes that are not installed
    $all_slugs    = $this->get_installed_slugs();
    $this->issues = array_filter(
      $this->issues,
      function ($issue) use ($all_slugs) {
        return in_array($issue['slug'], $all_slugs, true);
      }
    );

    // update the update information
    foreach ($this->issues as &$issue) {
      if($issue['update'] === null ) {
        $issue['update'] = $this->is_update_available($issue['slug']);
      }
    }

    if (null === $filter) {
      return $this->issues;
    }

    return array_filter(
      $this->issues ?? [],
      fn ($issue) => ('critical' === $filter) ? 7 < $issue['score'] : 7 >= $issue['score']
    );
  }

  public function get_lastscan()
  {
    $last_run = \get_transient('ionos_wpscan_last_scan');
    if (false === $last_run) {
      return __('No scan has been performed yet.', 'ionos-essentials');
    }
    return human_time_diff($last_run, time());
  }

  private function download_wpscan_data()
  {
    $url   = 'https://webapps-vuln-scan.hosting.ionos.com/api/v1/vulnerabilities';
    $token = get_option('ionos_security_wpscan_token', '');
    if (empty($token)) {
      return;
    }

    $response = wp_remote_post(
      $url,
      [
        'headers' => [
          'Accept'        => 'application/json',
          'Authorization' => 'API-Key ' . $token,
          'Content-Type'  => 'application/json',
          'User-Agent'    => 'Security-Plugin',
        ],
        'timeout' => 15,
        'body'    => $this->get_my_info(),
      ]
    );

    if (\is_wp_error($response)) {
      error_log('WPScan middleware error: ' . $response->get_error_message());
      return false;
    }

    $status_code = \wp_remote_retrieve_response_code($response);
    if (200 !== $status_code) {
      error_log('WPScan middleware error: ' . \wp_remote_retrieve_response_message($response));
      return false;
    }

    $body = \wp_remote_retrieve_body($response);
    if (empty($body)) {
      error_log('WPScan middleware error: Empty response');
      return false;
    }

    return \json_decode($body, true);
  }

  /**
   * Gathers information about the plugins and themes, that are installed.
   *
   * @return string JSON encoded information about the installed plugins and themes.
   */
  private function get_my_info()
  {
    if (! function_exists('get_plugins')) {
      require_once ABSPATH . 'wp-admin/includes/plugin.php';
    }

    $info              = [
      'coreVersion' => get_bloginfo('version'),
      'plugins'     => [],
      'themes'      => [],
    ];
    $installed_plugins = array_keys(get_plugins());
    foreach ($installed_plugins as $plugin) {
      $version = get_plugin_data(WP_PLUGIN_DIR . "/{$plugin}")['Version'];
      if (! empty($version)) {
        $info['plugins'][] = [
          'slug'    => dirname(plugin_basename(WP_PLUGIN_DIR . "/{$plugin}")),
          'version' => $version,
        ];
      }
    }
    $installed_themes = wp_get_themes();
    foreach ($installed_themes as $theme) {
      $version = $theme->get('Version');
      if (! empty($version)) {
        $info['themes'][] = [
          'slug'    => $theme->get_stylesheet(),
          'version' => $version,
        ];
      }
    }
    return json_encode($info);
  }

  private function convert_middleware_data($issues)
  {
    // Filter out items without issues
    foreach (['plugins', 'themes'] as $type) {
      $issues[$type] = array_values(array_filter(
        $issues[$type],
        function ($item) {
          return ! empty($item['vulnerabilities']);
        }
      ));
    }

    // Leave the highest vulnerability for each plugin/theme, delete the rest
    foreach (['plugins', 'themes'] as $type) {
      foreach ($issues[$type] as &$item) {

        // Sort vulnerabilities by score, descending
        usort($item['vulnerabilities'], function ($a, $b) {
          return $b['score'] <=> $a['score'];
        });
        // Keep only the highest vulnerability
        $item['vulnerabilities'] = array_slice($item['vulnerabilities'], 0, 1);
      }
      unset($item);
    }

    $converted = [];
    foreach (['plugins', 'themes'] as $type) {
      foreach ($issues[$type] as $item) {
        $converted[] = [
          'name'   => $this->get_name($item['slug']),
          'slug'   => $item['slug'],
          'path'   => $item['slug'] . '/' . $item['slug'] . '.php',
          'type'   => substr($type, 0, -1),
          'update' =>  (strpos(json_encode($item['vulnerabilities']), 'fixed_in')) ? null : false,
          'score'  => $item['vulnerabilities'][0]['score'] ?? 0,
        ];
      }
    }

    return $converted;
  }

  private function get_name($slug)
  {
    if (! function_exists('get_plugins')) {
      require_once ABSPATH . 'wp-admin/includes/plugin.php';
    }
    $plugins = get_plugins();
    foreach ($plugins as $file => $data) {
      if (dirname($file) === $slug || basename($file, '.php') === $slug) {
        return $data['Name'] ?? $slug;
      }
    }
    $themes = wp_get_themes();
    foreach ($themes as $theme_slug => $theme_obj) {
      if ($theme_slug === $slug) {
        return $theme_obj->get('Name') ?? $slug;
      }
    }
    return $slug;
  }

  private function is_update_available($slug)
  {
    $plugins_updates = get_site_transient('update_plugins');
    $theme_updates   = get_site_transient('update_themes');
    $updates         = array_keys(array_merge($plugins_updates->response ?? [], $theme_updates->response ?? []));

    $short_slugs = array_map(function ($update) {
      return basename($update, '.php');
    }, $updates);

    return in_array($slug, $short_slugs, true);
  }

  private function get_installed_slugs(): array
  {
    $plugins = \get_plugins();
    $themes  = \wp_get_themes();

    $plugin_slugs = array_map(function ($plugin) {
      return basename($plugin, '.php');
    }, array_keys($plugins));

    $theme_slugs = array_keys($themes);

    return array_merge($plugin_slugs, $theme_slugs);
  }
}
