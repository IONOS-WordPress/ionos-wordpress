<?php

namespace ionos\wpdev\caddy\notebook;

use const ionos\wpdev\caddy\MENU_PAGE_SLUG;
use const ionos\wpdev\caddy\PLUGIN_DIR;
use const ionos\wpdev\caddy\PLUGIN_FILE;
use WP_Admin_Bar;

defined('ABSPATH') || exit();

const NOTEBOOKS_DIR              = __DIR__ . '/notebooks';
const NOTEBOOKS_PAGE_SLUG_PREFIX = MENU_PAGE_SLUG . '-notebook-';

const ACTION_SAVE    = NOTEBOOKS_PAGE_SLUG_PREFIX . 'save-cell-value';
const ACTION_RENAME  = NOTEBOOKS_PAGE_SLUG_PREFIX . 'rename-cell';

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
    if (! is_dir($dir)) {
      continue;
    }

    $cells = [];
    foreach (scandir($dir) as $php_filename) {
      $php_file = $dir . '/' . $php_filename;
      if (is_dir($php_file) || ! str_ends_with($php_file, '.php')) {
        continue;
      }

      $cells[] = [
        'name'    => $php_filename,
        'value'   => file_get_contents($php_file),
      ];
    }

    usort($cells, function ($l, $r) {
      return $l['name'] <=> $r['name'];
    });

    $slug        = NOTEBOOKS_PAGE_SLUG_PREFIX . \sanitize_title($name);
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
    foreach (get_notebooks() as $notebook) {
      \add_submenu_page(
        parent_slug: MENU_PAGE_SLUG,
        page_title : $notebook['name'],
        menu_title : $notebook['name'],
        capability : 'manage_options',
        menu_slug  : $notebook['slug'],
        callback   : fn () => _render_notebook_page($notebook),
      );
    }
  }
);

\add_action('admin_enqueue_scripts', function (string $hook_suffix) {
  if (strpos($hook_suffix, NOTEBOOKS_PAGE_SLUG_PREFIX) === false) {
    return;
  }

  $notebook_slug = strstr($hook_suffix, MENU_PAGE_SLUG);

  foreach (get_notebooks() as $notebook) {
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
    deps    : ['code-editor'],
    ver     : filemtime(PLUGIN_DIR . '/ionos-wpdev-caddy/notebooks/index.css'),
  );

  \wp_add_inline_script(
    handle : NOTEBOOKS_PAGE_SLUG_PREFIX,
    data   : sprintf(
      "wp['ionos-wpdev-caddy-notebooks'] = %s;",
      \wp_json_encode([
        'current' => $notebook,
        'actions' => [
          'execute' => MENU_PAGE_SLUG,
          'save'    => ACTION_SAVE,
          'rename'  => ACTION_RENAME,
        ],
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
      <p>
        Notebooks helps IONOS WordPress development team finding issues and bugs.
      </p>
      <p>
        Cells are executed in the context of the currently loaded WordPress installation.
        Execute cells with care - they run with your full WordPress user privileges.
      </p>

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
            <button type="button" class="notebook-cell-execute button button-primary" title="Execute PHP cell on the server in WordPress context">
              Execute cell
            </button>
            <span>&nbsp;</span>
            <button type="button" class="notebook-cell-reset button button-secondary" title="Reset the editor content to the original cell content">
              Reset Editor
            </button>
            <button type="button" class="notebook-cell-save button button-secondary" title="Save the current editor content to the server">
              Save
            </button>
            <button type="button" class="notebook-cell-rename button button-secondary" title="Rename the current cell">
              Rename
            </button>
          </p>
          <div>
            <label class="notebook-cell-output-label">Output:</label>
            <textarea class="notebook-cell-output" rows="10" cols="50" readonly></textarea>
          </div>
          <hr>
        </dd>
      </template>

      <p>
        <em>
          Cells are ordered alphabetically by their file name.
        </em>
      </p>
    </div>
    HTML
      , [
        '{page_title}' => esc_html($notebook['name']),
      ])
  );
}

\add_action('wp_ajax_' . ACTION_SAVE, function () {
  // since we utilize the automatically available nonce from wp-api-fetch we need to use the expected key '_ajax_nonce' here
  check_ajax_referer('wp_rest', '_ajax_nonce');

  $notebook = $_POST['notebook'];
  $cell     = $_POST['cell'];
  $value    = \wp_unslash($_POST['value']);

  $notebook_dir = NOTEBOOKS_DIR . '/' . $notebook;
  if (! is_dir($notebook_dir)) {
    \wp_send_json_error(sprintf("Notebook '%s' does not exist.", $notebook));
  }

  $php_file = $notebook_dir . '/' . $cell;
  if (! is_file($php_file)) {
    \wp_send_json_error(sprintf("Cell '%s' does not exist in notebook '%s'.", $cell, $notebook));
  }

  file_put_contents($php_file, $value);

  \wp_send_json_success(sprintf("Successfully saved value of cell '%s' in notebook '%s'.", $cell, $notebook));
});

\add_action('wp_ajax_' . ACTION_RENAME, function () {
  // since we utilize the automatically available nonce from wp-api-fetch we need to use the expected key '_ajax_nonce' here
  check_ajax_referer('wp_rest', '_ajax_nonce');

  $notebook       = $_POST['notebook'];
  $from_cell_name = $_POST['from_cell_name'];
  $to_cell_name   = $_POST['to_cell_name'];

  $notebook_dir = NOTEBOOKS_DIR . '/' . $notebook;
  if (! is_dir($notebook_dir)) {
    \wp_send_json_error(sprintf("Notebook '%s' does not exist.", $notebook));
  }

  $php_file = $notebook_dir . '/' . $from_cell_name;
  if (! is_file($php_file)) {
    \wp_send_json_error(sprintf("Cell '%s' does not exist in notebook '%s'.", $from_cell_name, $notebook));
  }

  $to_php_file = $notebook_dir . '/' . $to_cell_name;
  if (is_file($to_php_file)) {
    \wp_send_json_error(sprintf("Cell '%s' already exists in notebook '%s'.", $to_cell_name, $notebook));
  }

  if (! rename($php_file, $to_php_file)) {
    \wp_send_json_error(
      sprintf("Could not rename cell '%s' to '%s' in notebook '%s'.", $from_cell_name, $to_cell_name, $notebook)
    );
  }

  \wp_send_json_success(sprintf("Successfully saved value of cell '%s' in notebook '%s'.", $from_cell_name, $notebook));
});

// ui sugar - not really needed
\add_action(
  hook_name: 'admin_bar_menu',
  callback: function (WP_Admin_Bar $wp_admin_bar) {
    foreach (get_notebooks() as $notebook) {
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
