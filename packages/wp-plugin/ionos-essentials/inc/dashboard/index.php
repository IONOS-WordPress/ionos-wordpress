<?php

namespace ionos\essentials\dashboard;

use const ionos\essentials\PLUGIN_DIR;

/*
   this file features an admin page that renders a dashboard.
   this files renders a custom admin page with an iframe that displays a custom dashboard page.
   the custom dashboard page is a prerendered WordPress post that is displayed in an iframe.
   we use a custom post type to store the dashboard page content and a custom block template to render the content.
   the matching editor for the dashboard page is in packages/wp-plugin/essentials/inc/dashboard/editor.php
 */

// exit if accessed directly
if (! defined('ABSPATH')) {
  exit();
}

const REQUIRED_USER_CAPABILITIES = 'read';

const HIDDEN_ADMIN_PAGE_IFRAME_SLUG = 'ionos-essentials-dashboard-hidden-admin-page-iframe';
const POST_TYPE_SLUG                = 'ionos_dashboard';

const POST_TYPE_TEMPLATE_CONTENT_START_MARKER = '<!-- ionos-essentials-dashboard-start-content -->';
const POST_TYPE_TEMPLATE_CONTENT_END_MARKER   = '<!-- ionos-essentials-dashboard-end-content -->';

// if editor feature is available, include the editor file
if (is_file(__DIR__ . '/editor.php')) {
  require_once __DIR__ . '/editor.php';
}

require_once __DIR__ . '/blocks/welcome/index.php';
require_once __DIR__ . '/blocks/vulnerability/index.php';
require_once __DIR__ . '/blocks/next-best-actions/index.php';
require_once __DIR__ . '/blocks/deep-links/index.php';
require_once __DIR__ . '/blocks/banner/index.php';
require_once __DIR__ . '/blocks/whats-new/index.php';

\add_action('init', function () {
  define('IONOS_ESSENTIALS_DASHBOARD_ADMIN_PAGE_TITLE', \get_option('ionos_group_brand_menu', 'IONOS'));
  define('ADMIN_PAGE_SLUG', strtolower(\get_option('ionos_group_brand_menu', 'IONOS')));
  define('ADMIN_PAGE_HOOK', 'toplevel_page_' . ADMIN_PAGE_SLUG);

  \add_action('load-' . ADMIN_PAGE_HOOK, function () {
    // inject css into our admin page to make the iframe fullscreen
    \wp_add_inline_style(
      'admin-bar',
      <<<EOF
        #wpbody {
            height: calc(100vh - var(--wp-admin--admin-bar--height, '0'));
        }
        #wpbody-content {
          height: 100%
        }
        #wpfooter {
          display: none
        }
        #wpwrap #wpcontent {
          padding-left: 0;
          background-color: #f4f7fa;
        }
        #wpwrap ul#adminmenu a.wp-has-current-submenu:after, #wpwrap ul#adminmenu > li.current > a.current:after {
          border-right-color: #f4f7fa;
        }
        #wpbody-content .update-nag {
          margin: 5px 15px 2px;
        }
        EOF
    );
  });

  \wp_register_block_metadata_collection(
    PLUGIN_DIR . '/build/dashboard/blocks',
    PLUGIN_DIR . '/build/dashboard/blocks/blocks-manifest.php'
  );
  \register_block_type(PLUGIN_DIR . '/build/dashboard/blocks/quick-links');
  \register_block_type(PLUGIN_DIR . '/build/dashboard/blocks/vulnerability');
});

// remove our blocks from all other post types
\add_filter(
  hook_name: 'allowed_block_types_all',
  callback: function (bool|array $allowed_block_types, \WP_Block_Editor_Context $editor_context): bool|array {
    if (! $editor_context->post) {
      return $allowed_block_types;
    }
    if (POST_TYPE_SLUG !== $editor_context->post->post_type) {
      if (! is_array($allowed_block_types)) {
        $allowed_block_types = array_keys(\WP_Block_Type_Registry::get_instance()->get_all_registered());
      }
      // filter out blocks by namespace and reindex array
      $allowed_block_types = [
        ...array_filter(
          $allowed_block_types,
          fn ($block_name) => ! str_starts_with($block_name, 'ionos-dashboard-page/')
        ),
      ];
    }
    return $allowed_block_types;
  },
  accepted_args: 2,
);

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
      printf(
        '<iframe src="%s&noheader=1" style="width: 100%%; height: 100%%;"></iframe>',
        \esc_attr(\menu_page_url(HIDDEN_ADMIN_PAGE_IFRAME_SLUG, false))
      );
    },
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

  // create a sub page rendering the contents of the iframe
  \add_submenu_page(
    parent_slug: false,	// dont show page in wp-admin menu
    page_title : HIDDEN_ADMIN_PAGE_IFRAME_SLUG,
    menu_title : HIDDEN_ADMIN_PAGE_IFRAME_SLUG,
    capability : REQUIRED_USER_CAPABILITIES,
    menu_slug  : HIDDEN_ADMIN_PAGE_IFRAME_SLUG,
    callback   : function () {
      // the logic what dashboard is shown when (e.g. based on tenant) can be implemented here
      $dashboard_name = 'ionos';
      $html           = file_get_contents(__DIR__ . "/data/{$dashboard_name}/rendered-skeleton.html");

      $start_marker_pos = strpos($html, POST_TYPE_TEMPLATE_CONTENT_START_MARKER);
      $end_marker_pos   = strpos($html, POST_TYPE_TEMPLATE_CONTENT_END_MARKER, $start_marker_pos);

      $post_content = file_get_contents(__DIR__ . "/data/{$dashboard_name}/post_content.html");
      $post_content = \do_blocks($post_content);

      $html = substr_replace(
        $html,
        $post_content,
        $start_marker_pos,
        $end_marker_pos + strlen(POST_TYPE_TEMPLATE_CONTENT_END_MARKER) - $start_marker_pos
      );

      /*
        replace <script id="wp-api-fetch-js-after">...</script>
        with the installation configured settings for api fetch
        this configuration is used when dashboard blocks uses rest calls
       */
      global $wp_scripts;
      $wp_api_fetch_after = $wp_scripts->registered['wp-api-fetch']?->extra['after'] ?? [];
      $wp_api_fetch_after = implode("\n", $wp_api_fetch_after);
      $html               = preg_replace(
        '/(<script id="wp-api-fetch-js-after">).*?(<\/script>)/s',
        '$1' . $wp_api_fetch_after . '$2',
        $html
      );

      // replace our wp-env url with the actual host url
      $html = str_replace('http://localhost:8888', \get_site_url(), $html);

      printf('%s', $html);
      exit();
    }
  );
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

add_action('init', function () {
  register_block_bindings_source('ionos-essentials/tenant-logo-src', [
    'label'              => __('Brand Logo', 'ionos-essentials'),
    'get_value_callback' => function () {
      $tenant = \get_option('ionos_group_brand', 'ionos');
      return plugin_dir_url(__FILE__) . "data/tenant-logos/{$tenant}.svg";
    },
  ]);
});

\add_action('admin_enqueue_scripts', function ($hook) {
  \wp_add_inline_style('admin-bar', '[data-extendify-tour-id=site-assistant-tour] {
      display: none;
    }');
});
