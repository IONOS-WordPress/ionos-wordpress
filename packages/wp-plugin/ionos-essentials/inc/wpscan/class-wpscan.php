<?php

namespace ionos\essentials\wpscan;
use const ionos\essentials\PLUGIN_DIR;

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

    add_action('rest_api_init', function () {
      \register_rest_route('ionos/essentials', '/wpscan', [
        'methods'             => 'POST',
        'callback'            => [$this, 'handle_ajax_wpscan'],
        'permission_callback' => function () {
          return current_user_can('update_plugins');
        },
      ]);
    });

    if (0 < count($this->get_issues())) {
      add_action('admin_notices', [$this, 'admin_notice']);
      add_action('after_plugin_row', [$this, 'after_plugin_row'], 10, 3);
    }


    add_action('admin_footer', [ $this, 'add_theme_issues_notice' ]);
    add_action('admin_footer', [ $this, 'add_issue_on_plugin_install' ]);
    add_action('admin_footer', [ $this, 'add_issue_on_theme_install' ]);


    \add_action('upgrader_process_complete', function ($upgrader, $options) {
      \delete_transient('ionos_wpscan_issues');
    }, 10, 2);
  }

  public function has_error()
  {
    return $this->error;
  }

  public function handle_ajax_wpscan(\WP_REST_Request $request)
  {
    $data = $request->get_json_params()['data'] ?? [];
    if (empty($data)) {
      return new \WP_REST_Response([
        'status'  => 'error',
        'message' => __('No data provided', 'ionos-essentials'),
      ], 400);
    }
    $data   = json_decode($data);
    $slug   = $data->slug   ?? '';
    $path   = $data->path   ?? '';
    $action = $data->action ?? '';
    $type   = $data->type   ?? '';

    if (empty($slug) || empty($action) || empty($type)) {
      return new \WP_REST_Response([
        'status'  => 'error',
        'message' => __('Missing required parameters', 'ionos-essentials'),
      ], 400);
    }

    $status_code = 200;
    $message     = \__('Operation completed successfully', 'ionos-essentials');
    $status      = 'success';

    if ('plugin' === $type) {
      if ('delete' === $action) {
        \deactivate_plugins($path, true);
        $response = \delete_plugins([$path]);

        if (is_wp_error($response)) {
          $status_code = 500;
          $status      = 'error';
          $message     = __('Failed to delete plugin', 'ionos-essentials');
        }
      }
      if ('update' === $action) {
        include_once ABSPATH . 'wp-admin/includes/class-wp-upgrader.php';
        $upgrader = new \Plugin_Upgrader(new \WP_Ajax_Upgrader_Skin());

        $upgrader->upgrade($path);
        if (is_wp_error($upgrader->skin->result)) {
          $status_code = 500;
          $status      = 'error';
          $message     = __('Failed to update plugin', 'ionos-essentials');
        }

        \delete_transient('ionos_wpscan_issues');
      }
    }

    if ('theme' === $type) {
      if ('delete' === $action) {
        $theme = \wp_get_theme();

        if (strToLower($theme->get('Name')) === strToLower($slug)) {
          $status_code = 500;
          $status      = 'error';
          $message     = __('Active theme cannot be deleted', 'ionos-essentials');
        } else {
          require_once ABSPATH . 'wp-admin/includes/theme.php';

          $response = \delete_theme($slug);
          if (is_wp_error($response)) {
            $status_code = 500;
            $status      = 'error';
            $message     = __('Failed to delete theme', 'ionos-essentials');
          }
        }
      }

      if ('update' === $action) {
        include_once ABSPATH . 'wp-admin/includes/class-wp-upgrader.php';
        $upgrader = new \Theme_Upgrader(new \WP_Ajax_Upgrader_Skin());

        $upgrader->upgrade($slug);

        if (is_wp_error($upgrader->skin->result)) {
          $status_code = 500;
          $status      = 'error';
          $message     = __('Failed to update theme', 'ionos-essentials');
        }

        \delete_transient('ionos_wpscan_issues');
      }
    }

    return new \WP_REST_Response([
      'status_code'    => $status_code,
      'status'         => $status,
      'message'        => $message,
    ], $status_code);
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
      if (null === $issue['update']) {
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

  public function admin_notice()
  {
    global $current_screen;
    if (! isset($current_screen->id) || in_array($current_screen->id, ['toplevel_page_ionos'], true)) {
      return;
    }

    printf(
      '<div class="notice notice-alt ionos-issues-found-adminbar %s"><p>%s: %d %s. <a href="%s">%s.</a></p></div>',
      (0 < count($this->get_issues('critical'))) ? 'notice-error' : 'notice-warning',
      esc_html__('Vulnerability scan', 'ionos-essentials'),
      count($this->get_issues()),
      (1 === count($this->get_issues())) ? esc_html__('issue found', 'ionos-essentials') :
      esc_html__('issues found', 'ionos-essentials'),
      esc_url(admin_url('admin.php?page=ionos#tools')),
      esc_html__('More information', 'ionos-essentials')
    );
  }

  public function after_plugin_row($plugin_file, $plugin_data, $status)
  {

    $paths = array_column($this->get_issues(), 'path');
    if (! in_array($plugin_file, $paths, true)) {
      return;
    }
    echo '<script>
      document.addEventListener("DOMContentLoaded", function() {
        const row = document.querySelector("tr[data-plugin=\'' . esc_js($plugin_file) . '\']");
        if (row) {
          row.classList.add("update");
        }
      });
    </script>';

    $updates       = get_site_transient('update_plugins');
    $noshadowclass = isset($updates->response[$plugin_file]) ? 'ionos-plugin-noshadow' : '';

    printf(
      '<tr class="plugin-update-tr %s ionos-wpscan-notice"><td colspan="4" class="plugin-update colspanchange %s"><div class="update-message notice inline %s notice-alt">%s %s. <a href="%s">%s.</a></div></td></tr>',
      \is_plugin_active($plugin_file) ? 'active' : 'inactive',
      esc_attr($noshadowclass ?? ''),
      esc_attr('notice-error'),
      esc_html__('The vulnerability scan has found issues for', 'ionos-essentials'),
      esc_html($plugin_data['Name']),
      esc_url(admin_url('admin.php?page=ionos#tools')),
      esc_html__('More information', 'ionos-essentials')
    );
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
          'update' => (strpos(json_encode($item['vulnerabilities']), 'fixed_in')) ? null : false,
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

  public function add_issue_on_theme_install()
  {
    $screen = get_current_screen();
    if (!$screen || 'theme-install' !== $screen->id) {
      return;
    }
    wp_enqueue_script(
      'ionos-wpscan-theme-install',
      plugins_url('ionos-essentials/inc/wpscan/theme-install.js', PLUGIN_DIR),
      [],
      filemtime(PLUGIN_DIR . '/inc/wpscan/theme-install.js'),
      true
    );

    wp_localize_script(
      'ionos-wpscan-theme-install',
      'ionosEssentialsThemeInstall',
      [
        'issues' => $this->get_issues(),
        'i18n'   => [
          'checking' => __('Checking for vulnerabilities...', 'ionos-essentials'),
          'warnings_found' => __('Warnings found. Installation is not recommended.', 'ionos-essentials'),
          'critical_found' => __('Critical vulnerabilities found! Installing is not possible.', 'ionos-essentials'),
          'nothing_found' => __('No vulnerabilities found. You can safely install this theme.', 'ionos-essentials'),
        ],
      ]
    );
  }

  public function add_issue_on_plugin_install(){

    $screen = get_current_screen();
    if (!$screen || 'plugin-install' !== $screen->id) {
      return;
    }
    wp_enqueue_script(
      'ionos-wpscan-plugins',
      plugins_url('ionos-essentials/inc/wpscan/plugins.js', PLUGIN_DIR),
      [],
      filemtime(PLUGIN_DIR . '/inc/wpscan/plugins.js'),
      true
    );

    wp_localize_script(
      'ionos-wpscan-plugins',
      'ionosEssentialsPlugins',
      [
        'issues' => $this->get_issues(),
        'i18n'   => [
          'checking' => __('Checking for vulnerabilities...', 'ionos-essentials'),
          'warnings_found' => __('Warnings found. Installation is not recommended.', 'ionos-essentials'),
          'critical_found' => __('Critical vulnerabilities found! Installing is not possible.', 'ionos-essentials'),
          'nothing_found' => __('No vulnerabilities found. You can safely install this plugin.', 'ionos-essentials'),
        ],
      ]
    );
  }

  public function add_theme_issues_notice()
  {
    $screen = get_current_screen();
    if (!$screen || 'themes' !== $screen->id) {
      return;
    }
      $isses = $this->get_issues();
      $issues = array_filter($isses, function($issue) {
        return $issue['type'] === 'theme';
      });

      if (count($issues) === 0) {
        return;
      }

      wp_enqueue_script(
        'ionos-essentials-themes',
        plugins_url('ionos-essentials/inc/wpscan/themes.js', PLUGIN_DIR),
        [],
        filemtime(PLUGIN_DIR . '/inc/wpscan/themes.js'),
        true
      );

      wp_localize_script(
        'ionos-essentials-themes',
        'ionosEssentialsThemes',
        [
          'slugs' =>  array_column($issues, 'slug'),
          'i18n' => [
            'issues_found' => __('The vulnerability scan has found issues', 'ionos-essentials'),
            'no_activation' => __('Activation is not recommended', 'ionos-essentials'),
            'more_info' => __('More information', 'ionos-essentials'),
          ],
        ]
      );
  }
}
