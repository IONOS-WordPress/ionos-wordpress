<?php

namespace ionos\essentials\dashboard\blocks\quick_links;

use const ionos\essentials\PLUGIN_DIR;
use const ionos\essentials\PLUGIN_FILE;

\add_action('init', function () {
  \register_block_type(
    PLUGIN_DIR . '/build/dashboard/blocks/quick-links',
    [
      'render_callback' => 'ionos\essentials\dashboard\blocks\quick_links\render_callback',
    ]
  );
});

function render_callback()
{
  $config_file = __DIR__ . '/config.php';
  if (file_exists($config_file)) {
    require $config_file;
  }

  $template = '
  <div class="wp-block-column quick-links">
  <div class="wp-block-group">
  <h3>' . \esc_html__('Quick Links', 'ionos-essentials') . '</h3>
  <p>' . \esc_html__('Easily navigate to frequently used features and tools.', 'ionos-essentials') . '</p>
  </div><div class="wp-block-group elements">%s</div></div>';

  $body = '';
  foreach ($links as $link) {
    $url = is_array($link['url']) && isset($link['url']['extendable']) && 'Extendable' === wp_get_theme()->get('Name')
      ? $link['url']['extendable']
      : (is_array($link['url']) ? '' : $link['url']);

    if (empty($url)) {
      continue;
    }

    $body .= sprintf(
      '<div class="wp-block-group element">
        <a href="%s" target="_top">
          <img class="wp-block-image size-large is-resized icon" src="%s" alt=""/>
          <p>%s</p>
        </a></div>',
      \esc_url($url),
      \esc_url(\plugins_url('assets/img/' . $link['icon'], dirname(__DIR__, 3))),
      \esc_html($link['text'])
    );
  }

  if (empty($body)) {
    return '';
  }

  return sprintf($template, $body);
}

//mach mal n admin_enqueue_scripts und lade meine focus.js
add_action('admin_enqueue_scripts', function () {
  $script  = \plugins_url('/build/dashboard/blocks/quick-links/focus.js', PLUGIN_FILE);
  $version = filemtime(PLUGIN_DIR . '/build/dashboard/blocks/quick-links/focus.js');
  \wp_enqueue_script('ionos-essentials-focus', $script, [], $version, true);
});
