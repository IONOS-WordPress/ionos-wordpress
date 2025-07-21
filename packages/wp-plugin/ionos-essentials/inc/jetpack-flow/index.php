<?php

namespace ionos\essentials\jetpack_flow;

require_once ABSPATH . 'wp-admin/includes/plugin.php';

use const ionos\essentials\PLUGIN_DIR;

const HIDDEN_PAGE_SLUG            = 'ionos-assistant-jetpack-backup-flow';
const INSTALL_JETPACK_OPTION_NAME = 'assistant_jetpack_backup_flow_pending';
const SSO_ACTIONS                 = ['ionos_oauth_authenticate', 'ionos_oauth_register'];
const JETPACK_PLUGIN_FILE         = 'jetpack/jetpack.php';

\add_filter(
  'login_redirect',
  fn ($redirect_to, $requested_redirect_to, $user) =>
    _redirect_after_login($user, $redirect_to, $requested_redirect_to),
  90,
  3
);

\add_action(
  'one_time_login_after_auth_cookie_set',
  fn ($user) =>
    \wp_safe_redirect(_redirect_after_login($user, \admin_url())) && exit,
  10,
  1
);

// Redirects the user to the Jetpack page instead of the My Jetpack page.
\add_filter('wp_redirect', function (string $location): string {
  $query = \wp_parse_url($location, PHP_URL_QUERY) ?? '';
  parse_str($query, $query_params);

  if ('my-jetpack' === ($query_params['page'] ?? '')) {
    $query_params['page'] = 'jetpack';
    $location             = \add_query_arg($query_params, \wp_parse_url($location, PHP_URL_PATH));
  }

  return $location;
});

\add_action('init', function (): void {
  if (str_contains(\wp_login_url(), $_SERVER['SCRIPT_NAME'])) {
    \add_filter(
      'ionos_login_redirect_to',
      function ($redirect_to, $requested_redirect_to, $logged_user): string {
        $current_user_authorized = $logged_user instanceof \WP_User && $logged_user->has_cap('manage_options');

        if (! $current_user_authorized) {
          return $redirect_to;
        }

        if ('' === $requested_redirect_to) {
          $requested_redirect_to = \site_url() . $_SERVER['REQUEST_URI'];
        }

        $params = _get_params_from_url($requested_redirect_to);
        if (! _has_jetpack_backup_flow_params($params)) {
          return $redirect_to;
        }

        return add_query_arg([
          'page'   => HIDDEN_PAGE_SLUG,
          'coupon' => $params['coupon'],
        ], \admin_url());
      },
      100,
      3
    );
  }

  $url_params = _get_params_from_url(\site_url() . $_SERVER['REQUEST_URI']);

  if (! _has_jetpack_backup_flow_params($url_params)) {
    return;
  }

  if (in_array(filter_input(INPUT_GET, 'action'), SSO_ACTIONS, true)) {
    return;
  }

  if (! \current_user_can('manage_options')) {
    return;
  }

  if (\is_plugin_active(JETPACK_PLUGIN_FILE)) {
    \wp_redirect(add_query_arg('jetpack-partner-coupon', $_GET['coupon'], \admin_url()));
    exit;
  }

  $menu_page_title = __('Assistant', 'ionos-essentials');
  \add_menu_page(
    page_title: $menu_page_title,
    menu_title: $menu_page_title,
    capability: 'manage_options',
    menu_slug: HIDDEN_PAGE_SLUG,
    callback: function () {
      $step = $_GET['step'] ?? 'confirm';
      if ('install' === $step) {
        _render_install();
      } else {
        _render_confirm();
      }
    }
  );

  \add_action(
    'admin_page_access_denied',
    function (): void {
      $url = add_query_arg([
        'page'   => HIDDEN_PAGE_SLUG,
        'coupon' => $_GET['coupon'],
      ], \admin_url());

      \wp_redirect($url);
      exit;
    }
  );

  \add_action(
    'admin_init',
    function (): void {
      if (! \current_user_can('manage_options')) {
        return;
      }

      if (($_GET['step'] ?? 'confirm') === 'install') {
        _install_jetpack_plugin();
      }
    }
  );

  \add_action('admin_enqueue_scripts', function ($hook_suffix): void {
    if ('toplevel_page_' . HIDDEN_PAGE_SLUG !== $hook_suffix) {
      return;
    }

    \wp_enqueue_style(
      'ionos-assistant-flow',
      \plugins_url('/assets/flow.css', __FILE__),
      [],
      filemtime(PLUGIN_DIR . '/inc/jetpack-flow/assets/flow.css')
    );
  });
});

function _has_jetpack_backup_flow_params($params): bool
{
  return is_array($params)
      && isset($params['page'], $params['coupon'])
      && in_array($params['page'], [HIDDEN_PAGE_SLUG, 'ionos-assistant']);
}

function _get_params_from_url($url): ?array
{
  $url_query_string = \wp_parse_url($url, PHP_URL_QUERY);
  if (! is_string($url_query_string)) {
    return null;
  }

  \wp_parse_str($url_query_string, $params);

  return $params;
}

function _redirect_after_login($user, $redirect_to, $requested_redirect_to = ''): string
{
  return \apply_filters('ionos_login_redirect_to', $redirect_to, $requested_redirect_to, $user);
}

function _is_plugin_installed($plugin_slug): bool
{
  $installed_plugins = \get_plugins();

  foreach ($installed_plugins as $plugin_path => $wp_plugin_data) {
    if (explode('/', $plugin_path)[0] === $plugin_slug) {
      return true;
    }
  }

  return false;
}

function _install_jetpack_plugin(): void
{
  $option_value = \get_option(INSTALL_JETPACK_OPTION_NAME);

  if (false === $option_value) {
    \add_action('admin_head', function () {
      echo '<meta http-equiv="refresh" content="5">';
    });

    \update_option(INSTALL_JETPACK_OPTION_NAME, 0);
    return;
  }

  if ('0' === $option_value) {
    if (! _is_plugin_installed('jetpack')) {
      // Install from repo
      $api = \plugins_api('plugin_information', [
        'slug'   => 'jetpack',
        'fields' => [
          'downloadlink' => true,
        ],
      ]);

      if (\is_wp_error($api)) {
        return;
      }

      // Ignore failures on accessing SSL "https://api.wordpress.org/plugins/update-check/1.1/" in `wp_update_plugins()` which seem to occur intermittently.
      set_error_handler(null, E_USER_WARNING | E_USER_NOTICE);

      $plugin_upgrader = new \Plugin_Upgrader(new \WP_Ajax_Upgrader_Skin());
      $plugin_upgrader->install($api->download_link);
    }
    \activate_plugin(JETPACK_PLUGIN_FILE);

    \delete_option(INSTALL_JETPACK_OPTION_NAME);
    \wp_redirect(\add_query_arg('jetpack-partner-coupon', $_GET['coupon'], \admin_url()));
    exit;
  }
}

function _render_confirm(): void
{
  $coupon                      = \esc_attr($_GET['coupon']);
  $hidden_page_slug_attr       = \esc_attr(HIDDEN_PAGE_SLUG);
  $title                       = \esc_html__('Installing Jetpack Backup', 'ionos-essentials');
  $jetpack_logo_src_attr       = \esc_attr(\plugins_url('assets/jetpack-logo.svg', __FILE__));
  $jetpack_install_message     = \esc_html__('We are going to install Jetpack Backup now.', 'ionos-essentials');
  $jetpack_install_button_text = \esc_html__('Ok', 'ionos-essentials');
  $jetpack_no_thanks_text      = \esc_html__('No thanks', 'ionos-essentials');
  $admin_url_attr              = \esc_attr(\admin_url());
  printf(<<<EOF
  <div class="wrapper">
    <div class="container">
      <form>
        <input type="hidden" name="coupon" value="{$coupon}">
        <input type="hidden" name="page" value="{$hidden_page_slug_attr}">
        <input type="hidden" name="step" value="install">

        <h1 class="screen-reader-text">{$title}</h1>
        <img src="{$jetpack_logo_src_attr}" class="jetpack-logo">
        <p>{$jetpack_install_message}</p>
        <div class="buttons">
          <button class="btn primarybtn" type="submit">{$jetpack_install_button_text}</button>
          <a class="linkbtn" href="{$admin_url_attr}">{$jetpack_no_thanks_text}</a>
        </div>
      </form>
    </div>
  </div>
  EOF);
}

function _render_install(): void
{
  $title                   = \esc_html__('Installing Jetpack Backup', 'ionos-essentials');
  $jetpack_logo_src_attr   = \esc_attr(\plugins_url('assets/jetpack-logo.svg', __FILE__));
  $jetpack_install_message = \esc_html__(
    'Please wait a moment while we are installing Jetpack Backup for you.',
    'ionos-essentials'
  );
  printf(<<<EOF
  <div class="wrapper">
    <div class="container">
      <h1 class="screen-reader-text">{$title}</h1>
      <img src="{$jetpack_logo_src_attr}" class="jetpack-logo">
      <p>{$jetpack_install_message}</p>
    </div>
  </div>
  EOF);
}
