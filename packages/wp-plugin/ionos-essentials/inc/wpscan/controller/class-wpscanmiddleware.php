<?php

namespace ionos\essentials\wpscan;

class WPScanMiddleware
{
  private const URL = 'https://webapps-vuln-scan.hosting.ionos.com/api/v1/vulnerabilities';

  private string $error = '';

  public function get_instant_data(string $type, string $slug): bool|string
  {
    $token = \get_option('ionos_security_wpscan_token', '');
    if (empty($token)) {
      $this->error = \esc_html__('Vulnerability Scan not possible. Please contact Customer Care.', 'ionos-essentials');
      return false;
    }

    $endpoint = self::URL . '/' . $type . 's/' . $slug;
    $response = \wp_remote_get(
      $endpoint,
      [
        'headers' => [
          'Authorization' => 'API-Key ' . $token,
          'User-Agent'    => 'Security-Plugin',
        ],
        'timeout' => 15,
      ]
    );
    if (\is_wp_error($response)) {
      error_log('WPScan middleware error: ' . $response->get_error_message());
      return false;
    }

    $status_code = \wp_remote_retrieve_response_code($response);
    if (200 !== $status_code) {
      error_log('WPScan middleware error statuscode: ' . \wp_remote_retrieve_response_message($response));
      return false;
    }

    $body = \wp_remote_retrieve_body($response);
    if (empty($body)) {
      return 'nothing_found';
    }

    $scores = json_decode($body);
    if (! is_array($scores) || empty($scores)) {
      return 'nothing_found';
    }

    $highest_score = 0;
    foreach ($scores as $score) {
      if (isset($score->score) && $score->score > $highest_score) {
        $highest_score = $score->score;
      }
    }

    return ($highest_score >= 7) ? 'criticals_found' : 'warnings_found';
  }

  public function download_wpscan_data(): bool|array
  {
    $url   = self::URL;
    $token = \get_option('ionos_security_wpscan_token', '');

    if (empty($token)) {
      $this->error = __('Vulnerability Scan not possible. Please contact Customer Care.', 'ionos-essentials');
      return false;
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
        'body'    => $this->get_themes_and_plugins(),
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

  public function convert_middleware_data(array $issues): array
  {
    // Delete items without issues
    foreach (['plugins', 'themes'] as $type) {
      $issues[$type] = array_values(array_filter($issues[$type], fn ($item) => ! empty($item['vulnerabilities'])));
    }

    // Leave the highest vulnerability for each plugin/theme, delete the rest
    foreach (['plugins', 'themes'] as $type) {
      foreach ($issues[$type] as &$item) {
        $item['vulnerabilities'] = array_reduce(
          $item['vulnerabilities'],
          fn ($carry, $vuln) => ($carry === null || ($vuln['score'] ?? 0) > ($carry['score'] ?? 0)) ? $vuln : $carry
        );
      }
      unset($item);
    }

    $converted = [];
    foreach (['plugins', 'themes'] as $type) {
      foreach ($issues[$type] as $item) {
        $converted[] = [
          'name'   => $this->get_name($item['slug']),
          'slug'   => $item['slug'],
          // @TODO: path - this is not always correct -> a plugin can also be foo/plugin.php or just a plugin file
          'path'   => $item['slug'] . '/' . $item['slug'] . '.php',
          'type'   => substr($type, 0, -1),
          'update' => (strpos(json_encode($item['vulnerabilities']), 'fixed_in')) ? null : false,
          'score'  => $item['vulnerabilities']['score'] ?? 0,
        ];
      }
    }

    return $converted;
  }

  public function get_error_message(): string
  {
    return $this->error ?? __('An error occurred while fetching the vulnerability data.', 'ionos-essentials');
  }

  /**
   * Gathers information about the plugins and themes, that are installed.
   *
   * @return string JSON encoded information about the installed plugins and themes.
   */
  private function get_themes_and_plugins()
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

  private function get_name($slug)
  {
    if (! function_exists('get_plugins')) {
      require_once ABSPATH . 'wp-admin/includes/plugin.php';
    }
    $plugins = \get_plugins();
    foreach ($plugins as $file => $data) {
      // @TODO: path - this is not always correct -> a plugin can also be foo/plugin.php or just a plugin file
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
}
