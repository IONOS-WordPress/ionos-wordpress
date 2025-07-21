<?php

namespace ionos\essentials\dashboard;

if (! defined('ABSPATH')) {
  exit();
}

require_once ABSPATH . 'wp-admin/includes/plugin.php';
require_once ABSPATH . 'wp-admin/includes/class-wp-upgrader.php';
require_once ABSPATH . 'wp-admin/includes/plugin-install.php';
require_once ABSPATH . 'wp-admin/includes/file.php';
require_once ABSPATH . 'wp-admin/includes/misc.php';

use const ionos\essentials\PLUGIN_DIR;
use const ionos\essentials\security\IONOS_SECURITY_FEATURE_OPTION;
use const ionos\essentials\security\IONOS_SECURITY_FEATURE_OPTION_DEFAULT;

const REQUIRED_USER_CAPABILITIES = 'read';

\add_action('init', function () {
  define('IONOS_ESSENTIALS_DASHBOARD_ADMIN_PAGE_TITLE', \get_option('ionos_group_brand_menu', 'IONOS'));
  define('ADMIN_PAGE_SLUG', strtolower(\get_option('ionos_group_brand_menu', 'IONOS')));
  define('ADMIN_PAGE_HOOK', 'toplevel_page_' . ADMIN_PAGE_SLUG);
});

\add_action('admin_menu', function () {
  $tenant_name = \get_option('ionos_group_brand', 'ionos');
  $tenant_icon = '';

  $file_path = __DIR__ . "/data/tenant-icons/{$tenant_name}.svg";
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
    callback   : function () {
      require_once PLUGIN_DIR . '/inc/dashboard/view.php';
    },
  );

  \add_submenu_page(
    parent_slug: ADMIN_PAGE_SLUG,
    page_title : __('Tools', 'ionos-essentials'),
    menu_title : __('Tools', 'ionos-essentials'),
    capability : REQUIRED_USER_CAPABILITIES,
    menu_slug  : 'admin.php?page=' . ADMIN_PAGE_SLUG . '#tools'
  );

  // we stop ionos-library from removing our submenu item
  add_action('admin_menu', function () {
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

\add_action('rest_api_init', function () {
  \register_rest_route(
    'ionos/essentials/dashboard/welcome/v1',
    '/closer',
    [
      'methods'             => 'GET',
      'permission_callback' => fn () => 0 !== \get_current_user_id(),
      'callback'            => function () {
        $meta = \update_user_meta(\get_current_user_id(), 'ionos_essentials_welcome', true);

        if (false === $meta) {
          return rest_ensure_response(new \WP_REST_Response([
            'error' => 'failed to update user meta',
          ], 500));
        }

        return rest_ensure_response(new \WP_REST_Response([
          'status' => $meta,
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
      require_once PLUGIN_DIR . '/inc/dashboard/blocks/my-account/index.php';
      require_once PLUGIN_DIR . '/inc/dashboard/blocks/next-best-actions/class-nba.php';
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

\add_action('admin_enqueue_scripts', function ($hook) {
  if (ADMIN_PAGE_HOOK !== $hook) {
    return;
  }
  wp_enqueue_script(
    'ionos-essentials-dashboard-js',
    plugins_url('ionos-essentials/inc/dashboard/dashboard.js', PLUGIN_DIR),
    [],
    filemtime(PLUGIN_DIR . '/inc/dashboard/dashboard.js'),
    true
  );

  wp_localize_script('ionos-essentials-dashboard-js', 'wpData', [
    'nonce'              => wp_create_nonce('wp_rest'),
    'restUrl'            => esc_url_raw(rest_url()),
    'securityOptionName' => IONOS_SECURITY_FEATURE_OPTION,
    'i18n'               => [
      'installing'  => esc_html__('Installing...', 'ionos-essentials'),
      'activated'   => esc_html__('activated.', 'ionos-essentials'),
      'deactivated' => esc_html__('deactivated.', 'ionos-essentials'),
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

require_once __DIR__ . '/blocks/quick-links/index.php';
