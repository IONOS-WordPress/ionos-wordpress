<?php

// phpcs:disable WordPress.WP.GlobalVariablesOverride.Prohibited

namespace ionos_wordpress\essentials\dashboard\blocks\deep_links;

$tenant = strtolower(\get_option('ionos_group_brand', false));

$config_file = __DIR__ . '/config/' . $tenant . '.php';

if ($tenant && file_exists($config_file)) {
  require $config_file;

  $market = strToLower(\get_option($tenant . '_market', 'de'));
  $domain = $market_domains[$market] ?? reset($market_domains);

  printf('<h3>%s</h3>', \esc_html__('Deep-Links', 'ionos-essentials'));

  echo '<ul class="wp-block-list">';
  foreach ($links as $link) {
    printf(
      '<li><a href="%s" target="_blank">%s</a></li>',
      \esc_url($domain . $link['url']),
      \esc_html($link['anchor'])
    );
  }
  echo '</ul>';
}
