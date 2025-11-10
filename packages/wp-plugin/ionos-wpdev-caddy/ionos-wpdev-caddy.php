<?php

/**
 * Plugin Name:       WPDev Caddy
 * Description:       Plugin helps IONOS WordPress development team finding issues and bugs.
 * Requires at least: 6.8
 * Requires Plugins:
 * Requires PHP:      8.3
 * Version:           1.0.0
 * Update URI:        https://github.com/IONOS-WordPress/ionos-wordpress/releases/download/%40ionos-wordpress%2Flatest/ionos-support-info.json
 * Plugin URI:        https://github.com/IONOS-WordPress/ionos-wordpress/tree/main/packages/wp-plugin/ionos-support
 * License:           GPL-2.0-or-later
 * Author:            IONOS Group
 * Author URI:        https://www.ionos-group.com/brands.html
 * Domain Path:       /ionos-wpdev-caddy/languages
 * Text Domain:       ionos-wpdev-caddy
 */

namespace ionos\wpdev\caddy;

use WP_Admin_Bar;

defined('ABSPATH') || exit();

const PLUGIN_DIR      = __DIR__;
const PLUGIN_FILE     = __FILE__;

const MENU_PAGE_SLUG  = 'ionos-wpdev-caddy';
const MENU_PAGE_TITLE = 'WPDev Caddy';
const MENU_PAGE_URI   = 'admin.php?page=' . MENU_PAGE_SLUG;

\add_action(
  hook_name: 'admin_menu',
  callback: function () {
    $page_hook_suffix = \add_menu_page(
      page_title : MENU_PAGE_TITLE,
      menu_title : MENU_PAGE_TITLE,
      capability : 'manage_options',
      menu_slug  : MENU_PAGE_SLUG,
      callback   : __NAMESPACE__ . '\_render_admin_page',
    );

    // enqueue assets only on our plugin page
    \add_action(
      hook_name: "load-{$page_hook_suffix}",
      callback: function () {
        \wp_enqueue_code_editor([
          'type' => 'application/x-httpd-php', // Specify the language mode
          // Set the CodeMirror theme (e.g., 'default', 'ambiance', 'monokai', etc.)
          'theme'      => 'monokai',
          'codemirror' => [
            'lineNumbers'    => true,
            'tabSize'        => 2,
            'indentUnit'     => 2,
            'indentWithTabs' => false,
            'lineWrapping'   => false,
          ],
        ]);

        // enqueue our own script and styles
        \wp_enqueue_script_module(
          id      : MENU_PAGE_SLUG,
          src     : \plugin_dir_url(PLUGIN_FILE) . '/ionos-wpdev-caddy/ionos-wpdev-caddy.js',
          version : filemtime(PLUGIN_DIR . '/ionos-wpdev-caddy/ionos-wpdev-caddy.js'),
        );
        \wp_enqueue_style(
          handle  : MENU_PAGE_SLUG,
          src     : \plugin_dir_url(PLUGIN_FILE) . '/ionos-wpdev-caddy/ionos-wpdev-caddy.css',
          ver     : filemtime(PLUGIN_DIR . '/ionos-wpdev-caddy/ionos-wpdev-caddy.css'),
        );

        // needed for doing the ajax call to our custom wp_ajax action
        // @TODO: we cannot add it to the deps of our enqueued script since it's a module
        \wp_enqueue_script('wp-api-fetch');

        // inject our array of snippet catalog urls to load on the frontend
        \wp_add_inline_script(
          handle : 'wp-api-fetch', // @TODO: using our js module handle does not work here (probably since it's a module) - 'wp-api-fetch' is a workaround
          data   : strtr(
            <<<JS
            wp['{MENU_PAGE_SLUG}'] = {
              catalogs    : {catalogs},
              ajax_url    : '{ajax_url}',
              ajax_action : '{ajax_action}',
            };
            JS
            ,
            [
              '{MENU_PAGE_SLUG}' => MENU_PAGE_SLUG,
              // ajax_url is only here to be super self contained -> admin pages always contain the line "var ajaxurl = '/wp-admin/admin-ajax.php'"
              '{ajax_url}'    => \admin_url('admin-ajax.php'),
              '{ajax_action}' => MENU_PAGE_SLUG,
              '{catalogs}'    => \wp_json_encode(
                array_values(array_map(
                  fn (string $file): string => \plugin_dir_url(
                    PLUGIN_FILE
                  ) . '/ionos-wpdev-caddy/catalogs/' . \sanitize_file_name($file),
                  array_filter(
                    scandir(PLUGIN_DIR . '/ionos-wpdev-caddy/catalogs'),
                    fn (string $file): bool => str_ends_with($file, '.json') && $file !== 'schema.json'
                  )
                ))
              ),
            ]
          )
        );
      },
    );
  }
);

function _render_admin_page(): void
{
  printf(
    strtr(<<<HTML
    <div class="wrap">
      <h1>{page_title}</h1>
      <p>{page_title} helps IONOS WordPress development team finding issues and bugs.</p>

      <form>
        <dl>
          <dt>
            <label for="catalogs">Snippet catalog</label>
          </dt>
          <dd>
          <select id="catalogs"></select>
          </dd>

          <dt>
            <label for="catalog_snippet">Snippet</label>
          </dt>
          <dd>
          <select id="catalog_snippet"></select>
          </dd>

          <dt>
            <label data-editor_label="1">PHP Code</label>
          </dt>
          <dd>
            <textarea
              id="php_editor"
              rows="10"
              cols="50"><?php
        // Your PHP code goes here
            </textarea>
          </dd>
          <dt></dt>
          <dd>
            <p>
              <button type="button" id="execute" class="button button-primary" title="Execute PHP Snippet on the server in WordPress context">
                Execute Snippet
              </button>
              <span>&nbsp;</span>
              <button type="button" id="reset" class="button button-secondary" title="Reset the editor content to the original snippet content">
                Reset Editor
              </button>
              <button type="button" id="save" class="button button-secondary" title="Save the current editor content back to the snippet catalog">
                Save Snippet
              </button>
              <span>&nbsp;</span>
              <button type="button" id="export" class="button button-secondary" title="Export the current snippet catalog to the javascript console">
                Export catalog
              </button>
              <button type="button" id="import" class="button button-secondary" title="Import snippet catalog">
                Import catalog
              </button>
            </p>
          </dd>
          <dt>
            <label for="output">Output:</label>
          </dt>
          <dd>
            <textarea id="output" rows="10" cols="50" readonly></textarea>
          </dd>
        </dl>
      </form>


      <dialog id="import_dialog" class="wp-dialog" closedby="any">
        <h3>Import Catalog</h3>
        <p>Use the textarea below to paste a snippet catalog in JSON format.</p>
        <textarea
          autocomplete="off"
          autocorrect="off"
          autocapitalize="off"
          spellcheck="false"
          is="syntax-highlight"
          language="json"
        ></textarea>
        <p>
          <button type="button" id="import_catalog" class="button button-primary" title="Import snippet catalog into browser memory">
            Import catalog
          </button>
        </p>
        <p>
          <em>(Click on backdrop to close dialog.)</em>
        </p>
      </dialog>

      <dialog id="export_dialog" class="wp-dialog" closedby="any">
        <h3>Export Catalog</h3>
        <p>You can copy the snippet catalog from textarea below to the clipboard</p>
        <textarea
          autocomplete="off"
          autocorrect="off"
          autocapitalize="off"
          spellcheck="false"
          is="syntax-highlight"
          language="json"
        ></textarea>
        <!-- <syntax-highlight language="js"></syntax-highlight> -->
        <p>
          <em>(Click on backdrop to close dialog.)</em>
        </p>
      </dialog>
    </div>
    HTML
      , [
        '{page_title}' => esc_html(MENU_PAGE_TITLE),
      ])
  );
}

\add_action('wp_ajax_' . MENU_PAGE_SLUG, function () {
  // since we utilize the automatically available nonce from wp-api-fetch we need to use the expected key '_ajax_nonce' here
  \check_ajax_referer('wp_rest', '_ajax_nonce');

  $php_code = wp_unslash($_POST['php_code']) ?? '<?php';

  $temp_file = sys_get_temp_dir() . '/' . MENU_PAGE_SLUG . '.php';
  if ($temp_file === false) {
    \wp_send_json_error([
      'message' => 'Could not create temporary file.',
    ]);
  }

  file_put_contents($temp_file, $php_code);
  ob_start();
  try {
    require_once $temp_file;
    $output = ob_get_clean();
    \wp_send_json_success($output);
  } catch (\Throwable $e) {
    $output = ob_get_clean();
    \wp_send_json_error($e . PHP_EOL . $output);
  } finally {
    unlink($temp_file);
  }
});

// ui sugar - not really needed

\add_action(
  hook_name: 'admin_bar_menu',
  callback: function (WP_Admin_Bar $wp_admin_bar) {
    $wp_admin_bar->add_node([
      'id'    => MENU_PAGE_SLUG,
      'title' => MENU_PAGE_TITLE,
      'href'  => \admin_url(MENU_PAGE_URI),
      'meta'  => [
        'class' => MENU_PAGE_SLUG,
      ],
    ]);

    /*
    $wp_admin_bar->add_node([
      'id'    => 'unterseite-1',
      'title' => 'IONOS Stretch Support',
      'href'  => \admin_url(MENU_PAGE_URI),
      'parent'=> 'ionos-stretch-support'
    ]);
    $wp_admin_bar->add_node([
      'id'    => 'unterseite-2',
      'title' => 'Link 2',
      'href'  => 'https://wikipedia.org',
      'parent'=> 'ionos-stretch-support'
    ]);
    */
  },
  priority : 999,
);

// add a link to the plugin page to the plugin description in plugins.php
\add_filter(
  hook_name: 'plugin_row_meta',
  callback: function ($links, $file) {
    if ($file === \plugin_basename(PLUGIN_FILE)) {
      $settings_link = '<a href="' . \admin_url(MENU_PAGE_URI) . '">' . \esc_html('Settings') . '</a>';
      $links[]       = $settings_link;
    }
    return $links;
  },
  accepted_args: 2,
);

// // hide menu page from admin menu
// \add_action( 'admin_head', fn() => \remove_submenu_page( 'admin.php', MENU_PAGE_SLUG ));
