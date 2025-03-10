<?php

// phpcs:disable WordPress.WP.GlobalVariablesOverride.Prohibited

namespace ionos_wordpress\essentials\dashboard\blocks\deep_links;

$tenant = strtolower(\get_option('ionos_group_brand', false));

$config_file = __DIR__ . '/config/' . $tenant . '.php';

if ($tenant && file_exists($config_file)) {
  require_once $config_file;

  $market = strToLower(\get_option($tenant . '_market', 'de'));
  $domain = $market_domains[$market] ?? reset($market_domains);

  echo '
    <div class="wp-block-column deep-links" style="padding-top:0;padding-right:0;padding-bottom:0;padding-left:0">
      <div class="wp-block-group" style="padding-top:0;padding-right:0;padding-bottom:0;padding-left:0">';
        printf('<h3 class="wp-block-heading">%s</h3>', \esc_html__('Deep-Links', 'ionos-essentials'));
        printf('<p>%s</p>', \esc_html__('Description of this block which is two column wide. This block shows some deep links inside a box with soft borders and a background color.', 'ionos-essentials'));

        echo '
      </div>
      <div class="wp-block-group" style="display:grid;grid-template-columns:repeat(auto-fill,minmax(min(200px,100%),1fr));gap:0.8rem;min-height:0px;margin-top:var(--wp--preset--spacing--20);margin-bottom:0;padding-top:0;padding-right:0;padding-bottom:0;padding-left:0">
      ';

      foreach ($links as $link) {
        printf(
          '<div class="wp-block-group has-background element" style="display:flex;justify-content:center;align-items:center;min-height:100px;margin-top:0;margin-bottom:0;padding-top:0;padding-right:0;padding-bottom:0;padding-left:0;cursor:pointer">
            <a class="element-link" href="%s" target="_blank" style="display:flex;%sjustify-content:center;align-items:center;text-decoration:none">
              <p class="has-text-align-center has-small-font-size" style="width:120px;margin-top:0;font-style:normal;font-weight:500">%s</p>
            </a>
          </div>',
          \esc_url($domain . $link['url']),
          \esc_html('width:100%;height:100%;'),
          \esc_html($link['anchor'])
        );
      }
      echo '
      </div>
    </div>
  ';
}
