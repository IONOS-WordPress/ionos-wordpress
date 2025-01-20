<div class="ionos-block-deep-links">
<?php

printf('<h3>%s</h3>', __('Deep-Links', 'ionos-essentials'));
printf('<p>%s</p>', __('Use these links to get to your control panel.', 'ionos-essentials'));

echo '<ul class="wp-block-list">';
foreach ($links as $url => $anchor) {
  printf('<li><a href="%s" target="_blank">%s</a></li>', $url, $anchor);
}
echo '</ul>';
?>
</div>
