<?php

printf('<h3>%s</h3>', \esc_html__('Deep-Links', 'ionos-essentials'));
printf('<p>%s</p>', \esc_html__('Use these links to get to your control panel.', 'ionos-essentials'));

echo '<ul class="wp-block-list">';
foreach ($links as $link) {
  printf('<li><a href="%s" target="_blank">%s</a></li>', \esc_url($domain . $link['url']), \esc_html($link['anchor']));
}
echo '</ul>';
