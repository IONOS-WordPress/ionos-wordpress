<?php

namespace ionos\essentials\dashboard\blocks\quick_links;

function render_callback()
{
  $config_file = __DIR__ . '/config.php';

  if (file_exists($config_file)) {
    require $config_file;

    ?>
<div class="card" style="">
  <div class="card__content">
    <section class="card__section">
      <h2 class="headline headline--sub"><?php echo \esc_html__('Quick Links', 'ionos-essentials'); ?></h2>
      <div class="ionos_quick_links_buttons ionos_buttons_same_width">
        <?php
              foreach (get_config() as $link) {
                printf(
                  '<a href="%s" class="ghost-button button--with-icon"><i class="button__icon exos-icon exos-icon-%s"></i>%s</a>',
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
