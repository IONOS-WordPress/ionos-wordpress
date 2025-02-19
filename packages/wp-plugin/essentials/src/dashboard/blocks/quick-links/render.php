<?php

namespace ionos_wordpress\essentials\dashboard\blocks\quick_links;

$config_file = __DIR__ . '/config.php';

if (file_exists($config_file)) {
  require $config_file;

  printf('<h3>%s</h3>', \esc_html__('Quick Links', 'ionos-essentials'));

  echo '<ul class="wp-block-list">';
  foreach ($links as $url => $anchor) {
    printf('<li><a href="%s" target="_blank">%s</a></li>', \esc_url($url), \esc_html($anchor));
  }
  echo '</ul>';
}
