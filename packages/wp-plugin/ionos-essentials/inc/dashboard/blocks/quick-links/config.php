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
    'url'  => [
      'extendable' => $blog_url . '/wp-admin/site-editor.php?p=/wp_navigation/9&ionos-deep-link=extendable',
      'block'      => $blog_url . '/wp-admin/site-editor.php?p=/wp_navigation/9&ionos-deep-link=extendable',
      'classic'    => $blog_url . '/wp-admin/nav-menus.php?action=edit&menu=0&theme=%s&ionos-deep-link=classic_navigation',
    ],
    'text' => __(
      'Edit site Navigation',
      'ionos-essentials'
    ),
    'icon' => 'digitalisation-48.svg',
  ],
  [
    'url'  => $blog_url . '/wp-admin/site-editor.php?path=%2Fwp_global_styles',
    'text' => __(
      'Change Styles',
      'ionos-essentials'
    ),
    'icon' => 'favstar-16.svg',
  ],
  [
    'url'  => [
      'extendable' => $blog_url . '/wp-admin/site-editor.php?postId=extendable%2F%2Fheader&postType=wp_template_part&canvas=edit&ionos-deep-link=extendable',
      'block'      => $blog_url . '/wp-admin/site-editor.php?postType=wp_template&postId=%s&canvas=edit&ionos-deep-link=block_header',
    ],
    'text' => __(
      'Edit Header',
      'ionos-essentials'
    ),
    'icon' => 'website-48.svg',
  ],
  [
    'url'  => [
      'extendable' => $blog_url . '/wp-admin/site-editor.php?canvas=edit&p=%2Fwp_template_part%2Fextendable%2F%2Ffooter&ionos-deep-link=extendable',
      'block'      => $blog_url . '/wp-admin/site-editor.php?postType=wp_template&postId=%s&canvas=edit&ionos-deep-link=block_footer',
    ],
    'text' => __(
      'Edit Footer',
      'ionos-essentials'
    ),
    'icon' => 'download-document-14.svg',
  ],
  [
    'url'  => $blog_url . '/wp-admin/plugin-install.php',
    'text' => __(
      'Add Plugins',
      'ionos-essentials'
    ),
    'icon' => 'plus-16.svg',
  ],
  [
    'url'  => $blog_url . '/wp-admin/upload.php',
    'text' => __('Upload Media files', 'ionos-essentials'),
    'icon' => 'upload-16.svg',
  ],
];
