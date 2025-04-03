<?php

namespace ionos\essentials\dashboard\blocks\quick_links;

$blog_url = \get_bloginfo('url');

$links = [
  [
    'url'  => $blog_url . '/wp-admin/post-new.php?post_type=page',
    'text' => __(
      'Add new page',
      'ionos-essentials'
    ),
    'icon' => 'file-text-16.svg',
  ],
  [
    'url'  => $blog_url . '/wp-admin/post-new.php',
    'text' => __('Add new post', 'ionos-essentials'),
    'icon' => 'helparticle-16.svg',
  ],
  [
    'url'  => $blog_url . '/wp-admin/site-editor.php?postType=wp_navigation',
    'text' => __(
      'Edit site navigation',
      'ionos-essentials'
    ),
    'icon' => 'digitalisation-48.svg',
  ],
  [
    'url'  => $blog_url . '/wp-admin/site-editor.php?path=%2Fwp_global_styles',
    'text' => __(
      'Change styles',
      'ionos-essentials'
    ),
    'icon' => 'favstar-16.svg',
  ],
  [
    'url'  => $blog_url . '/wp-admin/site-editor.php',
    'text' => __('Edit Header', 'ionos-essentials'),
    'icon' => 'website-48.svg',
  ],
  [
    'url'  => $blog_url . '/wp-admin/site-editor.php',
    'text' => __(
      'Edit Footer',
      'ionos-essentials'
    ),
    'icon' => 'download-document-14.svg',
  ],
  [
    'url'  => $blog_url . '/wp-admin/plugin-install.php',
    'text' => __(
      'Add plugins',
      'ionos-essentials'
    ),
    'icon' => 'plus-16.svg',
  ],
  [
    'url'  => $blog_url . '/wp-admin/upload.php',
    'text' => __('Upload media files', 'ionos-essentials'),
    'icon' => 'upload-16.svg',
  ],
];
