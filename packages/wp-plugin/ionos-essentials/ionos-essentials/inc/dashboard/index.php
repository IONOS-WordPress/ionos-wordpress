<?php

namespace ionos\essentials\dashboard;

use ionos\essentials\Tenant;

defined('ABSPATH') || exit();

require_once ABSPATH . 'wp-admin/includes/plugin.php';
require_once ABSPATH . 'wp-admin/includes/class-wp-upgrader.php';
require_once ABSPATH . 'wp-admin/includes/plugin-install.php';
require_once ABSPATH . 'wp-admin/includes/file.php';
require_once ABSPATH . 'wp-admin/includes/misc.php';
require_once ABSPATH . 'wp-admin/includes/class-wp-site-health.php';

use const ionos\essentials\security\IONOS_SECURITY_FEATURE_OPTION;
use const ionos\essentials\security\IONOS_SECURITY_FEATURE_OPTION_DEFAULT;

const REQUIRED_USER_CAPABILITIES = 'read';

\add_action('init', function () {
  define('IONOS_ESSENTIALS_DASHBOARD_ADMIN_PAGE_TITLE', Tenant::get_label());
  define('ADMIN_PAGE_SLUG', Tenant::get_slug());
  define('ADMIN_PAGE_HOOK', 'toplevel_page_' . ADMIN_PAGE_SLUG);
});

\add_action('admin_menu', function () {
  $tenant_icon = '';

  $file_path = __DIR__ . '/data/tenant-icons/' . Tenant::get_slug() . '.svg';
  if (file_exists($file_path)) {
    $svg         = file_get_contents($file_path);
    $tenant_icon = 'data:image/svg+xml;base64,' . base64_encode($svg);
  }

  \add_menu_page(
    page_title : IONOS_ESSENTIALS_DASHBOARD_ADMIN_PAGE_TITLE,
    menu_title : IONOS_ESSENTIALS_DASHBOARD_ADMIN_PAGE_TITLE,
    capability : REQUIRED_USER_CAPABILITIES,
    menu_slug  : ADMIN_PAGE_SLUG,
    icon_url   : $tenant_icon,
    position: 1,
    // no callback because submenu page renders content
  );

  // add submenu with same menu_slug as parent so that title of sub is different
  \add_submenu_page(
    parent_slug: ADMIN_PAGE_SLUG,
    page_title : __('Overview', 'ionos-essentials'),
    menu_title : __('Overview', 'ionos-essentials'),
    capability : REQUIRED_USER_CAPABILITIES,
    menu_slug  : ADMIN_PAGE_SLUG,
    callback   : fn () => require_once __DIR__ . '/view.php',
  );

  \add_submenu_page(
    parent_slug: ADMIN_PAGE_SLUG,
    page_title : __('Tools & Security', 'ionos-essentials'),
    menu_title : __('Tools & Security', 'ionos-essentials'),
    capability : REQUIRED_USER_CAPABILITIES,
    menu_slug  : 'admin.php?page=' . ADMIN_PAGE_SLUG . '#tools'
  );

  // we stop ionos-library from removing our submenu item
  \add_action('admin_menu', function () {
    global $wp_filter;
    // ionos-library uses a priority of 999 to remove the submenu item
    if (isset($wp_filter['admin_menu']->callbacks[999])) {
      foreach ($wp_filter['admin_menu']->callbacks[999] as $callback) {
        if (is_array($callback['function']) && 'remove_unwanted_submenu_item' === $callback['function'][1]) {
          remove_action('admin_menu', $callback['function'], 999);
        }
      }
    }
  });
}, 1);

// we want to be presented as "default page" in wp-admin
// redirect to our custom dashboard page if /wp-admin/ is requested
if (\get_option('ionos_essentials_dashboard_mode', true)) {
  \add_action('load-index.php', function () {
    if (\current_user_can(REQUIRED_USER_CAPABILITIES)) {
      $current_url = \home_url($_SERVER['REQUEST_URI']);
      $admin_url   = \get_admin_url();

      if ($current_url !== $admin_url) { // only redirect if we are on empty /wp-admin/
        return;
      }

      \wp_safe_redirect(\menu_page_url(ADMIN_PAGE_SLUG, false));
    }
  });
}

// fixes the displayed page title for our custom admin page.
\add_filter(
  hook_name: 'admin_title',
  callback: function ($admin_title, $title) {
    if (ADMIN_PAGE_HOOK === \get_current_screen()?->id) {
      return IONOS_ESSENTIALS_DASHBOARD_ADMIN_PAGE_TITLE;
    }
    return $admin_title;
  },
  accepted_args : 2
);

add_filter('admin_body_class', function ($classes) {
  if (ADMIN_PAGE_HOOK === \get_current_screen()?->id) {
    $classes .= ' ionos-dashboard';
  }
  return $classes;
});

\add_action('rest_api_init', function () {
  \register_rest_route(
    'ionos/essentials/dashboard/welcome/v1',
    '/closer',
    [
      'methods'             => 'GET',
      'permission_callback' => fn () => 0 !== \get_current_user_id(),
      'callback'            => function () {
        $meta  = \update_user_meta(\get_current_user_id(), 'ionos_essentials_welcome', true);
        $popup = \update_user_meta(\get_current_user_id(), 'ionos_popup_after_timestamp', time() + 7 * DAY_IN_SECONDS);

        if (false === $meta || false === $popup) {
          return rest_ensure_response(new \WP_REST_Response([
            'error' => \__('failed to update user meta', 'ionos-essentials'),
          ], 500));
        }

        return rest_ensure_response(new \WP_REST_Response([
          'status' => $meta && $popup,
        ], 200));
      },
    ]
  );

  function install_plugin_from_url($plugin_url)
  {
    $upgrader = new \Plugin_Upgrader(new \WP_Ajax_Upgrader_Skin());
    $result   = $upgrader->install($plugin_url);

    return ! \is_wp_error($result);
  }

  \register_rest_route('ionos/essentials/dashboard/nba/v1', '/dismiss/(?P<id>[a-zA-Z0-9-]+)', [
    'methods'  => 'POST',
    'callback' => function ($request) {
      require_once __DIR__ . '/blocks/my-account/index.php';
      require_once __DIR__ . '/blocks/next-best-actions/class-nba.php';
      $params = $request->get_params();
      $nba_id = $params['id'];

      $nba = blocks\next_best_actions\NBA::get_nba($nba_id);
      $res = $nba->set_status('dismissed', true);
      if ($res) {
        return new \WP_REST_Response([
          'status' => 'success',
          'res'    => $res,
        ], 200);
      }
      return new \WP_REST_Response([
        'status' => 'error',
      ], 500);
    },
    'permission_callback' => function () {
      return \current_user_can('manage_options');
    },
  ]);

  \register_rest_route(
    'ionos/essentials/dashboard/nba/v1',
    'install-gml',
    [
      'methods'             => 'GET',
      'permission_callback' => function () {
        return \current_user_can('install_plugins');
      },
      'callback'            => function () {
        $plugin_slug = 'woocommerce-german-market-light/WooCommerce-German-Market-Light.php';
        if (! file_exists(WP_PLUGIN_DIR . '/' . $plugin_slug)) {
          if (! install_plugin_from_url(
            'https://marketpress.de/mp-download/no-key-woocommerce-german-market-light/woocommerce-german-market-light/1und1/'
          )) {
            return new \WP_REST_Response([
              'status' => 'error',
            ], 501);
          }
        }
        \activate_plugin($plugin_slug, '', false, true);

        return new \WP_REST_Response([
          'status' => 'success',
        ], 200);
      },
    ]
  );
});

\add_action(
  'wp_ajax_ionos-nba-setup-complete',
  function () {
    $status = (string) $_POST['status'] ?? 'unknown';

    \update_option('ionos_essentials_nba_setup_completed', $status);
    \wp_die();
  }
);

\add_action('admin_enqueue_scripts', function ($hook) {
  if (ADMIN_PAGE_HOOK !== $hook) {
    return;
  }
  \wp_enqueue_script(
    'ionos-essentials-dashboard-js',
    \plugin_dir_url(__FILE__) . 'dashboard.js',
    [],
    filemtime(\plugin_dir_path(__FILE__) . 'dashboard.js'),
    true
  );

  $get_issues   = get_transient('health-check-site-status-result');
  $issue_counts = [];
  if (false !== $get_issues) {
    $issue_counts = json_decode($get_issues, true);
  }
  if (! is_array($issue_counts) || ! $issue_counts) {
    $issue_counts = [
      'good'        => 0,
      'recommended' => 0,
      'critical'    => 0,
    ];
  }

  \wp_localize_script('ionos-essentials-dashboard-js', 'wpData', [
    'nonce'               => \wp_create_nonce('wp_rest'),
    'healthCheckNonce'    => \wp_create_nonce('health-check-site-status-result'),
    'restUrl'             => \esc_url_raw(rest_url()),
    'ajaxUrl'             => admin_url('admin-ajax.php'),
    'securityOptionName'  => IONOS_SECURITY_FEATURE_OPTION,
    'tenant'              => Tenant::get_slug(),
    'siteHealthIssueCount'=> $issue_counts,
    'i18n'                => [
      'installing'            => \esc_html__('Installing...', 'ionos-essentials'),
      'activated'             => \esc_html__('activated.', 'ionos-essentials'),
      'deactivated'           => \esc_html__('deactivated.', 'ionos-essentials'),
      'updating'              => \esc_html__('updating...', 'ionos-essentials'),
      'deleting'              => \esc_html__('deleting...', 'ionos-essentials'),
      'loading'               => \esc_html__('Loading content ...', 'ionos-essentials'),
      'siteHealthImprovable'  => \esc_html__('Should be improved', 'ionos-essentials'),
      'siteHealthGood'        => \esc_html__('Good', 'ionos-essentials'),
    ],
  ]);
});

\add_action('rest_api_init', function () {
  \register_rest_route(
    'ionos/essentials/option',
    '/set',
    [
      'methods'             => 'POST',
      'permission_callback' => fn () => 0 !== \get_current_user_id(),
      'callback'            => function ($request) {
        $params = $request->get_json_params();
        $option = $params['option'] ?? '';
        $key    = $params['key']    ?? '';
        $value  = $params['value']  ?? '';

        if (empty($option)) {
          \update_option($key, $value);
        } else {
          $options       = \get_option($option, IONOS_SECURITY_FEATURE_OPTION_DEFAULT);
          $options[$key] = $value;
          \update_option($option, $options);
        }

        return rest_ensure_response(new \WP_REST_Response([
          'status' => $key,
          'value'  => $value,
          'option' => $option,

        ], 200));
      },
    ]
  );
}, 1);

add_action('admin_enqueue_scripts', function () {
  \wp_enqueue_style(
    'ionos-maintenance-mode-admin',
    \plugin_dir_url(__FILE__) . 'outside-shadow-dom.css',
    [],
    filemtime(plugin_dir_path(__FILE__) . 'outside-shadow-dom.css')
  );
});

require_once __DIR__ . '/blocks/quick-links/index.php';

\add_action(
  'wp_ajax_ionos-popup-dismiss',
  fn () => (\delete_user_meta(\get_current_user_id(), 'ionos_popup_after_timestamp') && \wp_die())
);

/* hide admin bar, when query param /?hidetoolbar=1 is set */
add_filter('show_admin_bar', function ($show) {
  if (isset($_GET['hidetoolbar'])) {
    return false;
  }
  return $show;
});
