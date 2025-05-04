<?php

namespace ionos\essentials\dashboard\blocks\quick_links;

use const ionos\essentials\PLUGIN_DIR;

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
    if (is_array($link['url'])) {
      $url = handle_link($link['url']);
    } else {
      $url = $link['url'];
    }

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

\add_action('enqueue_block_editor_assets', function () {
  if (! isset($_GET['ionos-deep-link'])) {
    return;
  }

  if (isset($_GET['ionos-deep-link']) && 'extendable' === $_GET['ionos-deep-link']) {
    \add_action('admin_footer', function () {
      echo "<script>
        document.addEventListener('DOMContentLoaded', function() {
          const observer = new MutationObserver((mutations, obs) => {
            const iframe = document.querySelector('iframe');
            if (iframe) {
              document.querySelector('.editor-document-tools__document-overview-toggle')?.click();
              iframe.addEventListener('load', function() {
              const iframeDocument = iframe.contentDocument || iframe.contentWindow.document;
              if ( iframeDocument ) {
                document.querySelector('tr[aria-level=\"1\"] td a')?.click();
              }
              });
              obs.disconnect();
            }
          });

          observer.observe(document, {
            childList: true,
            subtree: true
          });
        });
      </script>";
    });
  }
  if (isset($_GET['ionos-deep-link']) && in_array(
    $_GET['ionos-deep-link'],
    ['block_header', 'block_footer'],
    true
  )) {
    \add_action('admin_footer', function () {
      echo "<script>
      document.addEventListener('DOMContentLoaded', function() {
        const observer = new MutationObserver((mutations, obs) => {
          const buttons = document.querySelectorAll('.components-panel__body div button');
          if (buttons.length === 2) {
            const buttonIndex = 'block_footer' === '" . $_GET['ionos-deep-link'] . "' ? 1 : 0;
            buttons[buttonIndex].click();
            obs.disconnect();
          }
        });

        observer.observe(document, {
          childList: true,
          subtree: true
        });
      });
    </script>";
    });
  }
});

function handle_link($link)
{
  if (! \is_array($link)) {
    return '';
  }

  $theme = wp_get_theme();
  if (isset($link['extendable']) && 'Extendable' === $theme->get('Name')) {
    return $link['extendable'];
  }

  if (isset($link['block']) && true === $theme->is_block_theme()) {
    $template_id = wp_get_theme()
      ->get_stylesheet() . '//' . get_post(get_option('page_on_front'))->post_type;
    return sprintf($link['block'], $template_id, '');
  }

  if (isset($link['classic']) && false === $theme->is_block_theme()) {
    return \sprintf($link['classic'], $theme->get_stylesheet(), '');
  }

  return '';
}
