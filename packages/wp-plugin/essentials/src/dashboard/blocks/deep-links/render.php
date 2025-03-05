<?php

// phpcs:disable WordPress.WP.GlobalVariablesOverride.Prohibited

namespace ionos_wordpress\essentials\dashboard\blocks\deep_links;

$tenant = strtolower(\get_option('ionos_group_brand', false));

$config_file = __DIR__ . '/config/' . $tenant . '.php';

if ($tenant && file_exists($config_file)) {
  require_once $config_file;

  $market = strToLower(\get_option($tenant . '_market', 'de'));
  $domain = $market_domains[$market] ?? reset($market_domains);

  /*printf('<h3>%s</h3>', \esc_html__('Deep-Links', 'ionos-essentials'));

  echo '<ul class="wp-block-list">';
  foreach ($links as $link) {
    printf(
      '<li><a href="%s" target="_blank">%s</a></li>',
      \esc_url($domain . $link['url']),
      \esc_html($link['anchor'])
    );
  }
  echo '</ul>';*/


echo '
  <div class="wp-block-column">
    <div class="wp-block-group" style="padding-top:0;padding-right:0;padding-bottom:0;padding-left:0">';
      printf('<h3 class="wp-block-heading">%s</h3>', \esc_html__('Deep-Links', 'ionos-essentials'));
      echo '<p>Description of this block which is two column wide. This block shows some deep links inside a box with soft borders and a background color.</p>
    </div>
    <div class="wp-block-group" style="min-height:0px;margin-top:var(--wp--preset--spacing--20);margin-bottom:0;padding-top:0;padding-right:0;padding-bottom:0;padding-left:0">
    ';

    foreach ($links as $link) {
      printf(
        '<div class="wp-block-group has-background" style="border-radius:24px;background-color:#f4f7fa;min-height:100px;margin-top:0;margin-bottom:0;padding-top:0;padding-right:0;padding-bottom:0;padding-left:0">
          <a href="%s" target="_blank"><p class="has-text-align-center has-small-font-size" style="font-style:normal;font-weight:500">%s</p></a>
        </div>',
        \esc_url($domain . $link['url']),
        \esc_html($link['anchor'])
      );
    }
    echo '
    </div>
  </div>
';
}
