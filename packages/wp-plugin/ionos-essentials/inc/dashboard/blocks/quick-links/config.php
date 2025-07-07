<?php

namespace ionos\essentials\dashboard\blocks\quick_links;

function get_config()
{
  $blog_url = \get_bloginfo('url');

  return [
    [
      'url'  => $blog_url . '/wp-admin/post-new.php?post_type=page',
      'text' => __(
        'Add new page',
        'ionos-essentials'
      ),
      'icon' => 'file-text-16',
    ],
    [
      'url'  => $blog_url . '/wp-admin/post-new.php',
      'text' => __('Add new post', 'ionos-essentials'),
      'icon' => 'helparticle-16',
    ],
    [
      'url'  => $blog_url . '/wp-admin/site-editor.php?p=%2Fnavigation',
      'text' => __(
        'Edit site Navigation',
        'ionos-essentials'
      ),
      'icon' => 'digitalisation-48',
    ],
    [
      'url'  => $blog_url . '/wp-admin/site-editor.php?path=%2Fwp_global_styles',
      'text' => __(
        'Change Styles',
        'ionos-essentials'
      ),
      'icon' => 'favstar-16',
    ],
    [
      'url'  => $blog_url . '/wp-admin/site-editor.php?p=%2Fpage&canvas=edit&ionos-siteeditor-quick-link=header',
      'text' => __('Edit Header', 'ionos-essentials'),
      'icon' => 'website-48',
    ],
    [
      'url'  => $blog_url . '/wp-admin/site-editor.php?p=%2Fpage&canvas=edit&ionos-siteeditor-quick-link=footer',
      'text' => __(
        'Edit Footer',
        'ionos-essentials'
      ),
      'icon' => 'download-document-14',
    ],
    [
      'url'  => $blog_url . '/wp-admin/plugin-install.php',
      'text' => __(
        'Add Plugins',
        'ionos-essentials'
      ),
      'icon' => 'plus-16',
    ],
    [
      'url'  => $blog_url . '/wp-admin/upload.php',
      'text' => __('Upload Media files', 'ionos-essentials'),
      'icon' => 'upload-16',
    ],
  ];
}
