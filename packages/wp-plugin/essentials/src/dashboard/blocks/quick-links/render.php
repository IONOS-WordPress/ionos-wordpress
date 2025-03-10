<?php

namespace ionos_wordpress\essentials\dashboard\blocks\quick_links;

$config_file = __DIR__ . '/config.php';

if (file_exists($config_file)) {
  require $config_file;

  echo '
    <div class="wp-block-column quick-links" style="padding-top:0;padding-right:0;padding-bottom:0;padding-left:0">
      <div class="wp-block-group" style="padding-top:0;padding-right:0;padding-bottom:0;padding-left:0">';
        printf('<h3>%s</h3>', \esc_html__('Quick Links', 'ionos-essentials'));
        printf('<p>%s</p>', \esc_html__('Description of this block which is two column wide. This block shows some quick links with a great icon before the text. This text gives information about the links purpose.', 'ionos-essentials'));
      echo
      '</div>
      <div class="wp-block-group" style="display:grid;grid-template-columns:repeat(3,minmax(0,1fr));gap:0.8rem;min-height:0px;margin-top:var(--wp--preset--spacing--40);margin-bottom:0;padding-top:0;padding-right:0;padding-bottom:0;padding-left:0">
      ';
      foreach ($links as $url => $anchor) {
        printf('<div class="wp-block-group element" style="display:flex;min-height:50px;margin-top:0;margin-bottom:0;padding-top:0;padding-right:0;padding-bottom:0;padding-left:20px;border-radius:24px;background-color:#f4f7fa">
        <a href="%s" target="_blank" style="display:flex;align-items:center;text-decoration:none"><figure class="wp-block-image size-large is-resized"><img src="https://img.icons8.com/?size=100&amp;id=HBR0c_yZeXyu&amp;format=png&amp;color=000000" alt="" style="aspect-ratio:1;width:20px;height:auto"/></figure><p style="margin-top:0;margin-left:10px">%s</p></a></div>',
        \esc_url($url),
        \esc_html($anchor));
      }
      echo '
      </div>
    </div>
  ';


}
