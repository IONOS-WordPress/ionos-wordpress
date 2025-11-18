<?php

namespace ionos\wpdev\caddy\notebook;

use WP_Admin_Bar;

use const ionos\wpdev\caddy\MENU_PAGE_SLUG;
use const ionos\wpdev\caddy\PLUGIN_DIR;
use const ionos\wpdev\caddy\PLUGIN_FILE;

defined('ABSPATH') || exit();

const NOTEBOOKS_DIR = __DIR__ . '/notebooks';
const NOTEBOOKS_PAGE_SLUG_PREFIX = MENU_PAGE_SLUG . '-notebook-';

function get_notebooks(): array
{
  static $notebooks = null;

  if ($notebooks !== null) {
    return $notebooks;
  }

  $notebooks = [];
  foreach (scandir(NOTEBOOKS_DIR) as $name) {
    if (in_array($name, ['.', '..'], true)) {
      continue;
    }

    $dir = NOTEBOOKS_DIR . '/' . $name;
    if (!is_dir($dir)) {
      continue;
    }

    $cells = [];
    foreach (scandir($dir) as $php_filename) {
      $php_file = $dir . '/' . $php_filename;
      if (is_dir($php_file) || !str_ends_with($php_file, '.php')) {
        continue;
      }

      $cells[] = [
        'name'    => $php_filename,
        'value' => file_get_contents($php_file),
      ];
    }

    $slug = NOTEBOOKS_PAGE_SLUG_PREFIX . \sanitize_title($name);
    $notebooks[] = [
      'name'    => $name,
      'slug'    => $slug,
      'cells'   => $cells,
    ];
  }

  return $notebooks;
}

\add_action(
  hook_name: 'admin_menu',
  callback: function () {
    foreach(get_notebooks() as $notebook) {
      \add_submenu_page(
        parent_slug: MENU_PAGE_SLUG,
        page_title : $notebook['name'],
        menu_title : $notebook['name'],
        capability : 'manage_options',
        menu_slug  : $notebook['slug'],
        callback   : fn() => _render_notebook_page($notebook),
      );
    }
  }
);

\add_action( 'admin_enqueue_scripts', function(string $hook_suffix) {
  if ( strpos( $hook_suffix, NOTEBOOKS_PAGE_SLUG_PREFIX ) === false ) {
    return;
  }

  $notebook_slug = strstr($hook_suffix, MENU_PAGE_SLUG);

  foreach(get_notebooks() as $notebook) {
    if ($notebook['slug'] === $notebook_slug) {
      break;
    }
  }

  // enqueue our own script and styles
  \wp_enqueue_script(
    handle  : NOTEBOOKS_PAGE_SLUG_PREFIX,
    src     : \plugin_dir_url(PLUGIN_FILE) . 'ionos-wpdev-caddy/notebooks/index.js',
    deps    : ['wp-api-fetch', 'code-editor', 'csslint', 'jshint', 'htmlhint', 'wp-dom-ready'],
    ver     : filemtime(PLUGIN_DIR . '/ionos-wpdev-caddy/notebooks/index.js'),
  );
  \wp_enqueue_style(
    handle  : NOTEBOOKS_PAGE_SLUG_PREFIX,
    src     : \plugin_dir_url(PLUGIN_FILE) . 'ionos-wpdev-caddy/notebooks/index.css',
    deps    : ['code-editor',],
    ver     : filemtime(PLUGIN_DIR . '/ionos-wpdev-caddy/notebooks/index.css'),
  );

  // inject our array of snippet catalog urls to load on the frontend
  \wp_add_inline_script(
    handle : NOTEBOOKS_PAGE_SLUG_PREFIX,
    data   : sprintf(
      "wp['ionos-wpdev-caddy-notebooks'] = %s;",
      \wp_json_encode([
        'current' => $notebook,
      ])
    ),
  );
});

function _render_notebook_page(array $notebook): void
{
  printf(
    strtr(<<<HTML
    <div class="wrap">
      <h1>{$notebook['name']}</h1>
      <p>Notebooks helps IONOS WordPress development team finding issues and bugs.</p>

      <form>
        <dl id="ionos-wpdev-caddy-notebooks"></dl>
      </form>

      <template id="notebook-cell-template">
        <dt>
          <p>
            <label class="notebook-cell-name"></label>
          </p>
        </dt>
        <dd>
          <div class="notebook-cell-body">
            <textarea class="notebook-cell-editor"></textarea>
          </div>
          <p>
            <button type="button" class="action-execute button button-primary" title="Execute PHP Snippet on the server in WordPress context">
              Execute Snippet
            </button>
            <span>&nbsp;</span>
            <button type="button" class="action-reset button button-secondary" title="Reset the editor content to the original snippet content">
              Reset Editor
            </button>
            <button type="button" class="action-save button button-secondary" title="Save the current editor content back to the snippet catalog">
              Save Snippet
            </button>
          </p>
          <div>
            <label class="notebook-cell-output-label">Output:</label>
            <textarea class="notebook-cell-output" rows="10" cols="50" readonly></textarea>
          </div>
          <hr>
        </dd>
      </template>
    </div>
    HTML
      , [
        '{page_title}' => esc_html($notebook['name']),
      ])
  );

  /*
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
    */
}

// ui sugar - not really needed

\add_action(
  hook_name: 'admin_bar_menu',
  callback: function (WP_Admin_Bar $wp_admin_bar) {
    foreach(get_notebooks() as $notebook) {
      $wp_admin_bar->add_node([
        'id'    => $notebook['slug'],
        'title' => $notebook['name'],
        'href'  => \admin_url('admin.php?page=' . $notebook['slug']),
        'parent'=> MENU_PAGE_SLUG,
        // 'meta'  => [
        //   'class' => 'wp-menu-image dashicons-before dashicons-admin-generic',
        // ],
      ]);
    }
  },
  priority : 999,
);
