<?php

namespace ionos\essentials\dashboard\blocks\my_account;

defined('ABSPATH') || exit();

use function ionos\essentials\tenant\get_tenant_config;

function render_callback(): void
{
  $data = get_tenant_config();

  if (null === $data) {
    return;
  }

  $links = '';
  if (empty(\get_option('ionos_sfs_website_id'))) {
    foreach ($data['links'] as $link) {
      $links .= sprintf(
        '<a href="%s" target="_blank">%s</a>',
        \esc_url($data['domain'] . $link['url']),
        \esc_html($link['anchor'])
      );
    }
    if (! empty($data['webmail'])) {
      $links .= sprintf(
        '<a href="%s" target="_blank">%s</a>',
        \esc_url($data['webmail']),
        \esc_html__('Webmail Login', 'ionos-essentials')
      );
    }
  } else {
    foreach ($data['sfs_links'] as $link) {
      $links .= sprintf(
        '<a href="%s" target="_blank">%s</a>',
        \esc_url($data['domain'] . \get_option('ionos_sfs_website_id') . '/' . $link['url']),
        \esc_html($link['anchor'])
      );
    }
  }

  ?>

  <div class="card ionos_my_account">
    <div class="card__content">
      <section class="card__section">
        <h2 class="headline headline--sub"><?php \esc_html_e('Account Management', 'ionos-essentials'); ?></h2>
      <div class="ionos_my_account_links ionos_buttons_same_width">
        <?php echo \wp_kses($links, 'post'); ?>
      </div>
      </section>

    </div>
  </div>

<?php
}

\add_filter('ionos_dashboard_banner__register_button', function ($button_list) {
  $data = get_tenant_config();

  if (empty($data)) {
    return $button_list;
  }

  $button_list[] = [
    'link'           => $data['domain'] . (\get_option(
      'ionos_sfs_website_id'
    ) ?: ($data['banner_links']['managehosting'] ?? '')),
    'target'         => '_blank',
    'text'           => esc_html__('Manage Hosting', 'ionos-essentials'),
    'css-attributes' => 'deeplink',
  ];

  return $button_list;
});
