<?php

namespace ionos\essentials\dashboard\blocks\quick_links;

defined('ABSPATH') || exit();

function get_config(): array
{
  $blog_url = \get_bloginfo('url');

  return [
    [
      'url'  => $blog_url . '/wp-admin/post-new.php?post_type=page',
      'id'  => 'add-new-page',
      'text' => __(
        'Add new page',
        'ionos-essentials'
      ),
      'icon' => 'file-text-16',
    ],
    [
      'url'  => $blog_url . '/wp-admin/post-new.php',
      'id'  => 'add-new-post',
      'text' => __('Add new post', 'ionos-essentials'),
      'icon' => 'helparticle-16',
    ],
    [
      'url'  => $blog_url . '/wp-admin/site-editor.php?p=%2Fnavigation',
      'id'  => 'edit-site-navigation',
      'text' => __(
        'Edit site Navigation',
        'ionos-essentials'
      ),
      'icon' => 'digitalisation-48',
    ],
    [
      'url'  => $blog_url . '/wp-admin/site-editor.php?path=%2Fwp_global_styles',
      'id'  => 'change-styles',
      'text' => __(
        'Change Styles',
        'ionos-essentials'
      ),
      'icon' => 'favstar-16',
    ],
    [
      'url'  => $blog_url . '/wp-admin/site-editor.php?p=%2Fpage&canvas=edit&ionos-siteeditor-quick-link=header',
      'id'  => 'edit-header',
      'text' => __('Edit Header', 'ionos-essentials'),
      'icon' => 'website-48',
    ],
    [
      'url'  => $blog_url . '/wp-admin/site-editor.php?p=%2Fpage&canvas=edit&ionos-siteeditor-quick-link=footer',
      'id'  => 'edit-footer',
      'text' => __(
        'Edit Footer',
        'ionos-essentials'
      ),
      'icon' => 'download-document-14',
    ],
    [
      'url'  => $blog_url . '/wp-admin/plugin-install.php',
      'id'  => 'add-plugins',
      'text' => __(
        'Add Plugins',
        'ionos-essentials'
      ),
      'icon' => 'plus-16',
    ],
    [
      'url'  => $blog_url . '/wp-admin/upload.php',
      'id'  => 'upload-media',
      'text' => __('Upload Media files', 'ionos-essentials'),
      'icon' => 'upload-16',
    ],
  ];
}
