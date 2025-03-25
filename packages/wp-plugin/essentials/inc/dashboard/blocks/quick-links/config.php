<?php

namespace ionos_wordpress\essentials\dashboard\blocks\quick_links;

$blog_url = \get_bloginfo('url');

$links = [
  $blog_url . '/wp-admin/post-new.php?post_type=page' => [
    'text' => __('Add new page', 'essentials'),
    'icon' => 'file-text-16.svg',
  ],
  $blog_url . '/wp-admin/post-new.php' => [
    'text' => __('Add new post', 'essentials'),
    'icon' => 'helparticle-16.svg',
  ],
  $blog_url . '/wp-admin/site-editor.php?postType=wp_navigation' => [
    'text' => __('Edit site navigation', 'essentials'),
    'icon' => 'digitalisation-48.svg',
  ],
  $blog_url . '/wp-admin/site-editor.php?path=%2Fwp_global_styles' => [
    'text' => __('Change styles, colours and font', 'essentials'),
    'icon' => 'favstar-16.svg',
  ],
  $blog_url . '/wp-admin/site-editor.php' => [
    'text' => __('Edit Header', 'essentials'),
    'icon' => 'website-48.svg',
  ],
  $blog_url . '/wp-admin/site-editor.php' => [
    'text' => __('Edit Footer', 'essentials'),
    'icon' => 'download-document-14.svg',
  ],
  $blog_url . '/wp-admin/plugin-install.php' => [
    'text' => __('Add plugins', 'essentials'),
    'icon' => 'plus-16.svg',
  ],
  $blog_url . '/wp-admin/upload.php' => [
    'text' => __('Upload media files', 'essentials'),
    'icon' => 'upload-16.svg',
  ],
];
