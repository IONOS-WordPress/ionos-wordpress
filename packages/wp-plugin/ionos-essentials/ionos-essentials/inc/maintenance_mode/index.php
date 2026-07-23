<?php

namespace ionos\essentials\maintenance_mode;

defined('ABSPATH') || exit();

use ionos\essentials\Tenant;

const OPTION_ACTIVATED_AT = 'ionos_maintenance_mode_activated_at';
const OPTION_EMAIL_SENT   = 'ionos_maintenance_mode_email_sent';
const CRON_HOOK           = 'ionos_maintenance_reminder_cron';

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

\add_action('update_option_ionos_essentials_maintenance_mode', function ($old_value, $new_value) {
  if (empty($old_value) && ! empty($new_value)) {
    $activated_at = time();
    \update_option(OPTION_ACTIVATED_AT, $activated_at);
    \wp_clear_scheduled_hook(CRON_HOOK);
    \wp_schedule_single_event($activated_at + 7 * DAY_IN_SECONDS, CRON_HOOK);
  } elseif (! empty($old_value) && empty($new_value)) {
    \delete_option(OPTION_ACTIVATED_AT);
    \delete_option(OPTION_EMAIL_SENT);
    $timestamp = \wp_next_scheduled(CRON_HOOK);
    if ($timestamp) {
      \wp_unschedule_event($timestamp, CRON_HOOK);
    }
  }
}, 10, 2);

\add_action(CRON_HOOK, function () {
  if (! is_maintenance_mode()) {
    return;
  }

  if (\get_option(OPTION_EMAIL_SENT, false)) {
    return;
  }

  $activated_at = \absint(\get_option(OPTION_ACTIVATED_AT, 0));
  if ($activated_at <= 0) {
    return;
  }

  \ionos\essentials\loop\log_loop_event('maintenance_reminder_email_sent', [
    'activated_at' => $activated_at,
    'days_active'  => (int) floor((time() - $activated_at) / DAY_IN_SECONDS),
  ]);

  if (send_maintenance_reminder_email()) {
    \update_option(OPTION_EMAIL_SENT, true);
    return;
  }

  if (! \wp_next_scheduled(CRON_HOOK)) {
    \wp_schedule_single_event(time() + HOUR_IN_SECONDS, CRON_HOOK);
  }
});

function send_maintenance_reminder_email()
{
  $to      = \get_option('admin_email');
  $subject = __('Your Website is Currently in Maintenance Mode', 'ionos-essentials');
  $message = get_maintenance_reminder_mail_content();
  $headers = ['Content-Type: text/html; charset=UTF-8'];

  return \wp_mail($to, $subject, $message, $headers);
}

function get_maintenance_reminder_mail_content(): string
{
  $user          = \wp_get_current_user();
  $customer_name = ! empty($user->display_name) ? $user->display_name : __('Admin', 'ionos-essentials');
  $site_name     = \get_bloginfo('name');
  $brand         = Tenant::get_slug();
  $tenant_label  = Tenant::get_label();
  $settings_url  = \admin_url('admin.php?page=' . $brand . '#tools');

  $mail  = '<p>' . \sprintf(__('Hi %s,', 'ionos-essentials'), \esc_html($customer_name)) . '</p>';
  $mail .= '<p>' . \sprintf(
    __('Just a check-in regarding your website, %s. It has now been in Maintenance Mode for over a week.', 'ionos-essentials'),
    '<strong>' . \esc_html($site_name) . '</strong>'
  ) . '</p>';
  $mail .= '<p>' . __('While this mode is active, visitors see a maintenance page and cannot access your content. Only you, as a logged-in administrator, can continue to view and edit the site.', 'ionos-essentials') . '</p>';
  $mail .= '<p>' . __('If you\'re still busy with updates, you can safely disregard this email. However, if your work is now complete and you\'d like to make your site public again, simply click the button below to update your settings. Alternatively, you can manage these settings at any time directly within your WP Admin dashboard.', 'ionos-essentials') . '</p>';

  $mail .= '<p style="margin: 24px 0;">';
  $mail .= '<a href="' . \esc_url(
    $settings_url
  ) . '" style="background-color: #0066cc; color: #ffffff; padding: 10px 20px; text-decoration: none; border-radius: 4px; display: inline-block;">';
  $mail .= \esc_html__('Configure Maintenance Mode', 'ionos-essentials');
  $mail .= '</a>';
  $mail .= '</p>';

  $mail .= '<p>' . __('Best regards,', 'ionos-essentials') . '<br>';
  $mail .= \sprintf(__('%s WordPress Team', 'ionos-essentials'), \esc_html($tenant_label)) . '</p>';

  return $mail;
}

\register_deactivation_hook(__FILE__, function () {
  $timestamp = \wp_next_scheduled(CRON_HOOK);
  if ($timestamp) {
    \wp_unschedule_event($timestamp, CRON_HOOK);
  }
});
