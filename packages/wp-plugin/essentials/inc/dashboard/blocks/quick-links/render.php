<?php

namespace ionos_wordpress\essentials\dashboard\blocks\quick_links;

$config_file = __DIR__ . '/config.php';

if (file_exists($config_file)) {
  require $config_file;

  echo '
    <div class="wp-block-column quick-links">
      <div class="wp-block-group">';
  printf('<h3>%s</h3>', \esc_html__('Quick Links', 'ionos-essentials'));
  printf('<p>%s</p>', \esc_html__('Easily navigate to frequently used features and tools', 'ionos-essentials'));
  echo '</div>
      <div class="wp-block-group elements">
      ';
  foreach ($links as $url => $anchor) {
    printf(
      '<div class="wp-block-group element">
        <a href="%s" target="_blank">
          <img class="wp-block-image size-large is-resized icon" src="%s" alt=""/>
          <p>%s</p>
        </a></div>',
      \esc_url($url),
      \esc_url(\plugins_url('assets/img/' . $anchor['icon'], \dirname(__DIR__, 3))),
      \esc_html($anchor['text'])
    );
  }
  echo '
      </div>
    </div>
  ';

}
