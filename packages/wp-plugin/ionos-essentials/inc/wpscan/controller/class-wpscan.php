<?php

namespace ionos\essentials\wpscan;

use const ionos\essentials\PLUGIN_DIR;
use const ionos\essentials\security\IONOS_SECURITY_FEATURE_OPTION;
use const ionos\essentials\security\IONOS_SECURITY_FEATURE_OPTION_MAIL_NOTIFY;

class WPScan
{
  private array|bool $issues;

  private bool $error = false;

  public function __construct()
  {
    $this->issues = \get_transient('ionos_wpscan_issues');

    if (false === $this->issues) {
      $this->get_new_middleware_data();
      $this->maybe_send_email();
    }

    if (0 < count($this->get_issues())) {
      add_action('admin_notices', [$this, 'admin_notice']);
      add_action('after_plugin_row', [$this, 'add_plugin_issue_notice'], 10, 3);
    }

    add_action('admin_footer', [$this, 'add_theme_issues_notice']);
    add_action('admin_footer', [$this, 'add_issue_on_plugin_install']);
    add_action('admin_footer', [$this, 'add_issue_on_theme_install']);

    \add_action('upgrader_process_complete', function () {
      \delete_transient('ionos_wpscan_issues');
    }, 10, 2);
  }

  public function get_issues($filter = null)
  {
    // Filter out issues for plugins/themes that are not installed
    $all_slugs    = $this->get_installed_slugs();
    $this->issues = array_filter($this->issues, fn ($issue) => in_array($issue['slug'], $all_slugs, true));

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

  public function admin_notice()
  {
    global $current_screen;

    $brand = strtolower(get_option('ionos_group_brand', 'ionos'));
    if (! isset($current_screen->id) || in_array($current_screen->id, ['toplevel_page_' . $brand], true)) {
      return;
    }

    printf(
      '<div class="notice notice-alt ionos-issues-found-adminbar %s"><p>%s: %d %s. <a href="%s">%s.</a></p></div>',
      (0 < count($this->get_issues('critical'))) ? 'notice-error' : 'notice-warning',
      esc_html__('Vulnerability scan', 'ionos-essentials'),
      count($this->get_issues()),
      (1 === count($this->get_issues())) ? esc_html__('issue found', 'ionos-essentials') :
      esc_html__('issues found', 'ionos-essentials'),
      esc_url(admin_url('admin.php?page=' . $brand . '#tools')),
      esc_html__('More information', 'ionos-essentials')
    );
  }

  public function add_issue_on_theme_install()
  {
    $screen = get_current_screen();
    if (! $screen || 'theme-install' !== $screen->id) {
      return;
    }
    wp_enqueue_script(
      'ionos-wpscan-theme-install',
      plugins_url('ionos-essentials/inc/wpscan/js/theme-install.js', PLUGIN_DIR),
      [],
      filemtime(PLUGIN_DIR . '/inc/wpscan/js/theme-install.js'),
      true
    );

    wp_localize_script(
      'ionos-wpscan-theme-install',
      'ionosWPScanThemes',
      [
        'issues'  => $this->get_issues(),
        'ajaxUrl' => admin_url('admin-ajax.php'),
        'i18n'    => [
          'checking'       => __('Checking for vulnerabilities...', 'ionos-essentials'),
          'warnings_found' => __('Warnings found. Installation is not recommended.', 'ionos-essentials'),
          'critical_found' => __('Critical vulnerabilities found! Installation is not possible.', 'ionos-essentials'),
          'nothing_found'  => __('No vulnerabilities found. You can safely install this theme.', 'ionos-essentials'),
        ],
      ]
    );
  }

  public function add_issue_on_plugin_install()
  {

    $screen = get_current_screen();
    if (! $screen || 'plugin-install' !== $screen->id) {
      return;
    }
    wp_enqueue_script(
      'ionos-wpscan-plugins',
      plugins_url('ionos-essentials/inc/wpscan/js/plugin-install.js', PLUGIN_DIR),
      [],
      filemtime(PLUGIN_DIR . '/inc/wpscan/js/plugin-install.js'),
      true
    );

    wp_localize_script(
      'ionos-wpscan-plugins',
      'ionosWPScanPlugins',
      [
        'issues'  => $this->get_issues(),
        'ajaxUrl' => admin_url('admin-ajax.php'),
        'i18n'    => [
          'checking'       => __('Checking for vulnerabilities...', 'ionos-essentials'),
          'warnings_found' => __('Warnings found. Installation is not recommended.', 'ionos-essentials'),
          'critical_found' => __('Critical vulnerabilities found! Installation is not possible.', 'ionos-essentials'),
          'nothing_found'  => __('No vulnerabilities found. You can safely install this plugin.', 'ionos-essentials'),
        ],
      ]
    );
  }

  public function add_theme_issues_notice()
  {
    $screen = get_current_screen();
    if (! $screen || 'themes' !== $screen->id) {
      return;
    }
    $isses  = $this->get_issues();
    $issues = array_filter($isses, function ($issue) {
      return 'theme' === $issue['type'];
    });

    if (0 === count($issues)) {
      return;
    }

    wp_enqueue_script(
      'ionos-essentials-themes',
      plugins_url('ionos-essentials/inc/wpscan/js/theme-overview.js', PLUGIN_DIR),
      [],
      filemtime(PLUGIN_DIR . '/inc/wpscan/js/theme-overview.js'),
      true
    );

    wp_localize_script(
      'ionos-essentials-themes',
      'ionosWPScanThemes',
      [
        'slugs' => array_column($issues, 'slug'),
        'brand' => strtolower(get_option('ionos_group_brand', 'ionos')),
        'i18n'  => [
          'issues_found'  => __('The vulnerability scan has found issues', 'ionos-essentials'),
          'no_activation' => __('Activation is not recommended', 'ionos-essentials'),
          'more_info'     => __('More information', 'ionos-essentials'),
        ],
      ]
    );
  }

  public function add_plugin_issue_notice($plugin_file, $plugin_data, $status)
  {

    $screen = get_current_screen();
    if (! $screen || 'plugins' !== $screen->id) {
      return;
    }
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

    $brand = strtolower(get_option('ionos_group_brand', 'ionos'));

    printf(
      '<tr class="plugin-update-tr %s ionos-wpscan-notice"><td colspan="4" class="plugin-update colspanchange %s"><div class="update-message notice inline %s notice-alt">%s %s. <a href="%s">%s.</a></div></td></tr>',
      \is_plugin_active($plugin_file) ? 'active' : 'inactive',
      esc_attr($noshadowclass ?? ''),
      esc_attr('notice-error'),
      esc_html__('The vulnerability scan has found issues for', 'ionos-essentials'),
      esc_html($plugin_data['Name']),
      esc_url(admin_url('admin.php?page=' . $brand . '#tools')),
      esc_html__('More information', 'ionos-essentials')
    );
  }

  public function get_lastscan()
  {
    $last_run = \get_transient('ionos_wpscan_last_scan');
    if (false === $last_run) {
      return \__('No scan has been performed yet.', 'ionos-essentials');
    }
    return human_time_diff($last_run, time());
  }

  public function has_error()
  {
    return $this->error;
  }

  private function maybe_send_email()
  {
    if (empty(\get_option(IONOS_SECURITY_FEATURE_OPTION, [])[IONOS_SECURITY_FEATURE_OPTION_MAIL_NOTIFY])) {
      return;
    }

    $user_knows_about = \get_transient('ionos_wpscan_slugs_sent_to_user') ?: [];

    $type_and_slugs = array_map(fn ($issue) => $issue['type'] . ':' . $issue['slug'], $this->get_issues());

    $unknown_slugs = array_diff($type_and_slugs, $user_knows_about);

    if (empty($unknown_slugs)) {
      return;
    }

    \set_transient('ionos_wpscan_issues_sent_to_user', $unknown_slugs, 6 * MONTH_IN_SECONDS);

    $unknown_names = [];
    foreach ($this->get_issues() as $issue) {
      $key = $issue['type'] . ':' . $issue['slug'];
      if (in_array($key, $unknown_slugs, true)) {
        $unknown_names[$key] = $issue['name'] ?? $issue['slug'];
      }
    }

    $to      = get_option('admin_email');
    $subject = __('Important Security Notification: Vulnerability detected in your WordPress website', 'ionos-essentials');
    $message = $this->get_mail_content(array_values($unknown_names));
    $headers = ['Content-Type: text/html; charset=UTF-8'];

    error_log('Mailed about ' . print_r($unknown_slugs, true) . print_r(array_values($unknown_names), true));
    wp_mail($to, $subject, $message, $headers);
  }

  private function get_new_middleware_data()
  {
    $middleware   = new WPScanMiddleware();
    $data         = $middleware->download_wpscan_data();
    if (empty($data) || ! is_array($data)) {
      error_log('WPScan middleware: No data received');
      $this->issues = [];
      $this->error  = true;
      return;
    }
    $data         = $middleware->convert_middleware_data($data);

    $next_run = (strpos(json_encode($data), 'UNKNOWN')) ? 5 * MINUTE_IN_SECONDS : 6 * HOUR_IN_SECONDS;

    \set_transient('ionos_wpscan_last_scan', time(), $next_run);
    \set_transient('ionos_wpscan_issues', $data, $next_run);

    $this->issues = $data;
  }

  private function is_update_available($slug): bool
  {
    $plugins_updates = \get_site_transient('update_plugins');
    $theme_updates   = \get_site_transient('update_themes');
    $updates         = array_keys(array_merge($plugins_updates->response ?? [], $theme_updates->response ?? []));

    $short_slugs = array_map(fn ($update) => basename($update, '.php'), $updates);

    return in_array($slug, $short_slugs, true);
  }

  private function get_installed_slugs(): array
  {
    $plugins = \get_plugins();
    $themes  = \wp_get_themes();

    $plugin_slugs = array_map(fn ($plugin) => basename($plugin, '.php'), array_keys($plugins));

    $theme_slugs = array_keys($themes);

    return array_merge($plugin_slugs, $theme_slugs);
  }

  private function get_mail_content(array $vulnerable_plugins): string
  {
    $tenant             = \get_option('ionos_group_brand', 'ionos');
    $mail               = '<p>' . __('Dear user,<br />We want to inform you that our recent vulnerability scan has detected one or more issues that require your attention:', 'ionos-essentials') . '</p>';
    $mail              .= '<ul>';
    foreach ($vulnerable_plugins as $plugin) {
      $mail .= '<li>' . $plugin . '</li>';
    }
    $mail .= '</ul>';

    $mail .= '<p>' . __('To ensure the safety and security of your website, we recommend reviewing the findings of the scan and taking appropriate actions. For more detailed information about the specific vulnerabilities identified, please visit the following link:', 'ionos-essentials');
    $mail .= '<br><a href="' . admin_url('admin.php?page=' . $tenant . '#tools') . '">' . admin_url() . '</a></p>';

    $mail .= '<p>' . \sprintf(
      // Translators: %1$s and %2$s is a placeholder for link.
      __('%1$sTurn off this notification here%2$s.', 'ionos-essentials'),
      '<a href="' . admin_url('admin.php?page=' . $tenant . '#tools') . '">',
      '</a>'
    ) . '</p>';

    $mail .= \sprintf(
      // Translators: %s is the tenant name.
      __('Your %s plugin team', 'ionos-essentials'),
      \get_option('ionos_group_brand_menu', 'Ionos')
    );

    return $mail;
  }
}
