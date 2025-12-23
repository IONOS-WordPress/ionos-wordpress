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

  $links    = \wp_kses($links, 'post');
  $headline = \esc_html__('Account Management', 'ionos-essentials');
  printf(<<<EOL
    <div class="card ionos_my_account">
      <div class="card__content">
        <section class="card__section">
          <h2 class="headline headline--sub">{$headline}</h2>
        <div class="ionos_my_account_links ionos_buttons_same_width">
          {$links}
        </div>
        </section>

      </div>
    </div>
    EOL);
}

\add_filter('ionos_dashboard_banner__register_button', function ($button_list) {
  $data = get_tenant_config();

  if (empty($data)) {
    return $button_list;
  }

  $managehosting = $data['banner_links']['managehosting'] ?? '';

  $button_list[] = [
    'link'           => $data['domain'] . $managehosting,
    'target'         => '_blank',
    'text'           => \esc_html__('Manage Hosting', 'ionos-essentials'),
    'title'          => \esc_html__('Manage Hosting', 'ionos-essentials'),
    'css-attributes' => 'deeplink',
  ];
  return $button_list;
});
