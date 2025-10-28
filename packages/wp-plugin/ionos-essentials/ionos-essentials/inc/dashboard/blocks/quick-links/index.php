<?php

namespace ionos\essentials\dashboard\blocks\quick_links;

defined('ABSPATH') || exit();

function render_callback(): void
{
  $config_file = __DIR__ . '/config.php';

  if (file_exists($config_file)) {
    require_once $config_file;

    ?>
<div class="card">
  <div class="card__content">
    <section class="card__section">
      <h2 class="headline headline--sub"><?php \esc_html_e('Quick Links', 'ionos-essentials'); ?></h2>
      <div class="ionos_quick_links_buttons ionos_buttons_same_width">
        <?php
              foreach (get_config() as $link) {
                printf(
                  '<a href="%s" data-track-link="%s"><i class="button__icon exos-icon exos-icon-%s"></i>%s</a>',
                  \esc_url($link['url']),
                  \esc_attr($link['id']),
                  $link['icon'],
                  \esc_html($link['text'])
                );
              }
    ?>
      </div>
    </section>

  </div>
</div>
    <?php
  }
}

/* handle header and footer quicklinks */
$siteeditor_quick_link = $_GET['ionos-siteeditor-quick-link'] ?? '';
if ('' !== $siteeditor_quick_link) {
  \add_action('enqueue_block_editor_assets', function () use ($siteeditor_quick_link) {
    \wp_add_inline_script(
      handle: 'wp-edit-site',
      data: "
        wp.domReady(async function() {
          // Wait for the editor to be ready
          await new Promise((resolve) => {
            const unsubscribe = wp.data.subscribe(() => {
              // This will trigger after the initial render blocking, before the window load event
              // This seems currently more reliable than using __unstableIsEditorReady
              if (wp.data.select('core/editor').isCleanNewPost() || wp.data.select('core/block-editor').getBlockCount() > 0) {
                  unsubscribe();
                  resolve();
              }
            })
          });

          // wait for the editor iframe to be ready
          const editorCanvasIframeElement = document.querySelector('[name=\"editor-canvas\"]');
          await new Promise((resolve) => {
            if(!editorCanvasIframeElement.loading) {
              // somehow the iframe has already loaded,
              // skip waiting for onload event (won't be triggered)
              resolve(editorCanvasIframeElement);
            }

            editorCanvasIframeElement.onload = resolve;
          });

          setTimeout(() => {
            // select the block with the specified tagName
            wp.data.dispatch('core/block-editor').selectBlock(
              wp.data.select('core/block-editor').getBlocks().find(block=>block.attributes.tagName==='{$siteeditor_quick_link}').clientId
            );
          }, 500);
        });
      ",
      position: 'after',
    );
  });
}
