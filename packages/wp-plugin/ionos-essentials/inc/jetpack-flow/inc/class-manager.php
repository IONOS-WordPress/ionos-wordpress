<?php

namespace ionos\essentials\jetpack_flow;

use ionos\essentials\jetpack_flow\Controllers\Confirm;
use ionos\essentials\jetpack_flow\Controllers\Install;
use WP_User;

require_once ABSPATH . 'wp-admin/includes/plugin.php';
require_once 'class-loginredirect.php';
require_once 'class-hiddenadminpage.php';
require_once 'controllers/interface-view-controller.php';

require_once 'controllers/class-confirm.php';
require_once 'controllers/class-install.php';

class Manager
{
  public const HIDDEN_PAGE_SLUG = 'ionos-assistant-jetpack-backup-flow';

  private static $hidden_page;

  private static $current_controller;

  private static $url_params;

  private static $has_params;

  private static $controller_by_step = [
    'confirm' => Confirm::class,
    'install' => Install::class,
  ];

  public static function init()
  {

    LoginRedirect::register_redirect();

    self::$url_params = self::get_params_from_url(site_url() . $_SERVER['REQUEST_URI']);
    self::$has_params = self::has_jetpack_backup_flow_params(self::$url_params);

    add_filter('wp_redirect', [__CLASS__, 'prevent_my_jetpack_redirect']);

    if (self::is_login_page()) {
      add_filter(
        'ionos_login_redirect_to',
        function ($redirect_to, $requested_redirect_to, $logged_user) {
          return self::login_redirect($redirect_to, $requested_redirect_to, $logged_user);
        },
        100,
        3
      );
    }

    if (! self::$has_params) {
      return;
    }

    $sso_actions = ['ionos_oauth_authenticate', 'ionos_oauth_register'];

    if (in_array(filter_input(INPUT_GET, 'action'), $sso_actions, true)) {
      return;
    }

    if (! current_user_can('manage_options')) {
      return;
    }

    if (is_plugin_active(Install::JETPACK_PLUGIN_FILE)) {
      wp_redirect(add_query_arg('jetpack-partner-coupon', $_GET['coupon'], admin_url()));
      exit;
    }

    self::$hidden_page = new HiddenAdminPage(
      __('Assistant', 'ionos-essentials'),
      self::HIDDEN_PAGE_SLUG,
      function () {
        $step = (isset(self::$url_params['step']) && array_key_exists(
          self::$url_params['step'],
          self::$controller_by_step
        ))
            ? self::$url_params['step']
            : 'confirm';

        self::$controller_by_step[$step]::render();
      }
    );
    self::$hidden_page->register_page();

    add_action(
      'admin_page_access_denied',
      function () {
        $url = add_query_arg(
          [
            'page'   => self::HIDDEN_PAGE_SLUG,
            'coupon' => self::$url_params['coupon'],
          ],
          admin_url()
        );

        wp_redirect($url);
        exit;
      }
    );

    add_action(
      'admin_init',
      function () {
        if (! current_user_can('manage_options')) {
          return;
        }

        self::setup_controller();
      }
    );

    add_action('admin_enqueue_scripts', [__CLASS__, 'enqueue_flow_assets']);
  }

  /**
   * Redirects the user to the Jetpack page instead of the My Jetpack page.
   *
   * @param string $location The redirect location.
   *
   * @return string The redirect location.
   */
  public static function prevent_my_jetpack_redirect($location)
  {
    $query = wp_parse_url($location, PHP_URL_QUERY);
    parse_str($query, $query_params);

    if (isset($query_params['page']) && 'my-jetpack' === $query_params['page']) {
      $query_params['page'] = 'jetpack';
      $location             = add_query_arg($query_params, wp_parse_url($location, PHP_URL_PATH));
    }

    return $location;
  }

  public static function enqueue_flow_assets($hook_suffix)
  {
    if ('toplevel_page_ionos-assistant-jetpack-backup-flow' !== $hook_suffix) {
      return;
    }

    wp_enqueue_style(
      'ionos-assistant-flow',
      plugins_url('css/flow.css', __DIR__),
      [],
      //filemtime( FEATURE_MAIN_DIR_PATH . '/css/flow.css' )
    );
  }

  /**
   * Check if we are on a wp-login page
   *
   * @return boolean
   */
  public static function is_login_page()
  {
    return false !== stripos(wp_login_url(), $_SERVER['SCRIPT_NAME']);
  }

  private static function setup_controller()
  {
    $step = isset($_GET['step']) ? $_GET['step'] : 'confirm';

    self::$current_controller = isset(self::$controller_by_step[$step]) ? self::$controller_by_step[$step] : '';
    if (empty(self::$current_controller)) {
      return false;
    }

    self::$current_controller::setup();

    return true;
  }

  private static function login_redirect($redirect_to, $requested_redirect_to, $logged_user)
  {
    $current_user_authorized = ($logged_user instanceof WP_User && $logged_user->has_cap('manage_options'));

    if (! $current_user_authorized) {
      return $redirect_to;
    }

    if ('' === $requested_redirect_to) {
      $requested_redirect_to = site_url() . $_SERVER['REQUEST_URI'];
    }

    $params = self::get_params_from_url($requested_redirect_to);
    if (! self::has_jetpack_backup_flow_params($params)) {
      return $redirect_to;
    }

    return add_query_arg([
      'page'   => self::HIDDEN_PAGE_SLUG,
      'coupon' => $params['coupon'],
    ], admin_url());
  }

  /**
   * Checks if the Jetpack Backup Flow Params are present
   *
   * @param array $params
   *
   * @return bool
   */
  private static function has_jetpack_backup_flow_params($params)
  {
    return is_array($params)
        && isset($params['page'])
        && (self::HIDDEN_PAGE_SLUG === $params['page'] || 'ionos-assistant' === $params['page'])
        && isset($params['coupon']);
  }

  private static function get_params_from_url($url)
  {
    $url_query_string = wp_parse_url($url, PHP_URL_QUERY);
    if (! is_string($url_query_string)) {
      return null;
    }

    $params = null;
    wp_parse_str($url_query_string, $params);

    return $params;
  }
}
