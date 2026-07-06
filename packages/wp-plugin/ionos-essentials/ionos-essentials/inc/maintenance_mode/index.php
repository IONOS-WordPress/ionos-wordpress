<?php

namespace ionos\essentials\maintenance_mode;

defined('ABSPATH') || exit();

use ionos\essentials\Tenant;

function is_maintenance_mode()
{
  return \get_option('ionos_essentials_maintenance_mode', false);
}

\add_action('admin_bar_menu', function ($wp_admin_bar) {
  $brand = Tenant::get_slug();

  $args = [
    'id'    => 'ionos_maintenance_mode',
    'title' => \esc_html('Maintenance page active', 'ionos-essentials'),
    'href'  => \admin_url('admin.php?page=' . $brand . '#tools'),
    'meta'  => [
      'class' => 'ionos-maintenance-mode ionos-maintenance-only',
      'title' => \esc_attr('Maintenance page active', 'ionos-essentials'),
    ],
  ];
  $wp_admin_bar->add_node($args);

  \wp_enqueue_style(
    'ionos-maintenance-mode-admin',
    \plugin_dir_url(__FILE__) . 'maintenance.css',
    [],
    filemtime(plugin_dir_path(__FILE__) . 'maintenance.css')
  );
}, 31);

add_action('init', function () {
  if (! is_maintenance_mode()) {
    return;
  }

  if (\is_user_logged_in()) {
    return;
  }

  $request_uri  = $_SERVER['REQUEST_URI'] ?? '';
  $request_path = trim((string) parse_url($request_uri, PHP_URL_PATH), '/\\');
  $home_path    = trim((string) parse_url(home_url('/'), PHP_URL_PATH), '/\\');

  if ('' !== $home_path && ($request_path === $home_path || str_starts_with($request_path, $home_path . '/'))) {
    $site_request_path = ltrim(substr($request_path, strlen($home_path)), '/\\');
  } else {
    $site_request_path = $request_path;
  }

  $rest_route      = isset($_GET['rest_route']) ? trim((string) \wp_unslash($_GET['rest_route']), '/\\') : '';
  $rest_prefix     = trim((string) \rest_get_url_prefix(), '/\\');
  $is_rest_request = '' !== $rest_route || $site_request_path === $rest_prefix || str_starts_with(
    $site_request_path,
    $rest_prefix . '/'
  );

  if ('wp-login.php' === $GLOBALS['pagenow'] || 'wp-admin' === $site_request_path || str_starts_with(
    $site_request_path,
    'wp-admin/'
  )) {
    return;
  }

  if (
    (defined('DOING_AJAX') && DOING_AJAX) ||
    (defined('WP_CLI')     && WP_CLI)     ||
    $is_rest_request
  ) {
    return;
  }

  if (isset($_GET['ionos_maintenance_mode']) || get_query_var(
    'ionos_maintenance_mode'
  ) || 'maintenance' === $site_request_path) {
    readfile(plugin_dir_path(__FILE__) . 'assets/maintenance.html');
    exit;
  }

  \add_rewrite_rule('maintenance/?$', 'index.php?ionos_maintenance_mode=1', 'top');
  if ('maintenance' === $site_request_path) {
    return;
  }
  global $wp_rewrite;
  if ($wp_rewrite->using_permalinks()) {
    \wp_redirect(home_url('/maintenance'), 302);
    exit;
  }
  \wp_redirect('index.php?ionos_maintenance_mode=1', 302);
  exit;
});

add_filter('admin_body_class', function ($classes) {
  if (is_maintenance_mode()) {
    $classes .= ' ionos-maintenance-mode';
  }
  return $classes;
});

add_filter('body_class', function ($classes) {
  if (is_maintenance_mode()) {
    $classes[] = 'ionos-maintenance-mode';
  }
  return $classes;
});

\add_action('update_option_ionos_essentials_maintenance_mode', __NAMESPACE__ . '\manage_maintenance_mode_timer', 10, 2);

function manage_maintenance_mode_timer($old_value, $new_value)
{
  if (empty($old_value) && ! empty($new_value)) {
    \update_option('ionos_maintenance_mode_activated_at', time());
  } elseif (! empty($old_value) && empty($new_value)) {
    \delete_option('ionos_maintenance_mode_activated_at');
    \delete_option('ionos_maintenance_mode_email_sent');
  }
}

\add_action('admin_init', function () {
  if (! \wp_next_scheduled('ionos_maintenance_reminder_cron')) {
    \wp_schedule_event(time(), 'daily', 'ionos_maintenance_reminder_cron');
  }
});

\add_action('init', function () {
  \add_action('ionos_maintenance_reminder_cron', function () {
    if (! is_maintenance_mode()) {
      return;
    }

    if (\get_option('ionos_maintenance_mode_email_sent', false)) {
      return;
    }

    $activated_at = \get_option('ionos_maintenance_mode_activated_at');
    if (! $activated_at) {
      return;
    }

    $seven_days_in_seconds = 7 * DAY_IN_SECONDS;
    if ((time() - $activated_at) >= $seven_days_in_seconds) {
      \update_option('ionos_maintenance_mode_email_sent', true);

      send_maintenance_reminder_email();
    }
  });
});

function send_maintenance_reminder_email()
{
  $to      = \get_option('admin_email');
  $subject = __('Reminder: Your website is still in maintenance mode', 'ionos-essentials');
  $message = get_maintenance_reminder_mail_content();
  $headers = ['Content-Type: text/html; charset=UTF-8'];

  return \wp_mail($to, $subject, $message, $headers);
}

function get_maintenance_reminder_mail_content(): string
{
  $tenant_label = Tenant::get_label();
  $login_url    = \wp_login_url();

  $mail  = '<p>' . __('Hello WordPress Admin,', 'ionos-essentials') . '</p>';
  $mail .= '<p>' . __('We noticed that you put your WordPress site into maintenance mode about 7 days ago.', 'ionos-essentials') . '</p>';
  $mail .= '<p>' . __('We just wanted to check in and see how your changes are coming along! Taking time to update your site is great, but leaving it in maintenance mode for too long might mean your visitors are missing out on your awesome content.', 'ionos-essentials') . '</p>';

  $mail .= '<p>' . __('Best regards,', 'ionos-essentials') . '<br>';
  $mail .= \sprintf(__('%s WordPress Team', 'ionos-essentials'), $tenant_label) . '</p>';

  $mail .= '<p>' . __('PS: log in ', 'ionos-essentials');
  $mail .= '<a href="' . \esc_url($login_url) . '">' . __('HERE', 'ionos-essentials') . '</a></p>';

  return $mail;
}

\register_deactivation_hook(__FILE__, function () {
  $timestamp = \wp_next_scheduled('ionos_maintenance_reminder_cron');
  if ($timestamp) {
    \wp_unschedule_event($timestamp, 'ionos_maintenance_reminder_cron');
  }
});
