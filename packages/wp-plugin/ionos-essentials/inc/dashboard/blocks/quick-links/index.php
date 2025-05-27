<?php

namespace ionos\essentials\dashboard\blocks\quick_links;

use const ionos\essentials\PLUGIN_DIR;

function render_callback()
{
  $config_file = __DIR__ . '/config.php';

  if (file_exists($config_file)) {
    require $config_file;

    ?>
<div class="card" style="">
  <div class="card__content">
    <section class="card__section">
      <h2 class="card__headline"><?php echo \esc_html__('Quick Links', 'ionos-essentials'); ?></h2>
      <p class="paragraph"><?php echo \esc_html__(
        'Easily navigate to frequently used features and tools.',
        'ionos-essentials'
      ); ?></p>
      <div>
        <?php
              foreach ($links as $link) {
                printf(
                  '<a href="%s" class="button button--secondary button--with-icon"><i class="button__icon exos-icon exos-icon-%s"></i>%s</a>',
                  \esc_url($link['url']),
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
