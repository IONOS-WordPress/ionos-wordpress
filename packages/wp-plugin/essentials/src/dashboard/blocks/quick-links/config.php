<?php

$blog_url = get_bloginfo('url');
$links = [
  $blog_url . '/wp-admin/post-new.php?post_type=page' => __('Add new page', 'ionos-essentials'),
  $blog_url . '/wp-admin/post-new.php' => __('Add new post', 'ionos-essentials'),
  $blog_url . '/wp-admin/plugin-install.php' => __('Discover plugins', 'ionos-essentials'),
  $blog_url . '/wp-admin/site-editor.php?postType=wp_template' => __('Edit template', 'ionos-essentials'),
  $blog_url . '/wp-admin/site-editor.php?postType=wp_navigation' => __('Edit navigation', 'ionos-essentials'),
];
