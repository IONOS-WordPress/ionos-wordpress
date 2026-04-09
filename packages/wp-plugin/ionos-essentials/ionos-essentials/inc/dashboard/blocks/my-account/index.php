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

\add_action('admin_bar_menu', function ($wp_admin_bar) {
    $data = \ionos\essentials\tenant\get_tenant_config();

    if (empty($data) || empty($data['links'])) {
        return;
    }

    // Parent menu: [Tenant] Links
    $tenant_name = ! empty($data['tenant']) ? $data['tenant'] : __('Tenant', 'ionos-essentials');

    $wp_admin_bar->add_node([
        'id'    => 'ionos_tenant_links',
        'title' => sprintf('%s Links', $tenant_name),
        'href'  => false, // parent item itself doesn’t need a link
    ]);

    // Add tenant links as children
    foreach ($data['links'] as $link) {
        $wp_admin_bar->add_node([
            'id'     => 'ionos_link_' . sanitize_title($link['anchor']),
            'title'  => $link['anchor'],
            'parent' => 'ionos_tenant_links',
            'href'   => esc_url($data['domain'] . $link['url']),
            'meta'   => ['target' => '_blank'], // open in new tab
        ]);
    }

    // Add webmail if present
    if (! empty($data['webmail'])) {
        $wp_admin_bar->add_node([
            'id'     => 'ionos_webmail',
            'title'  => __('Webmail Login', 'ionos-essentials'),
            'parent' => 'ionos_tenant_links',
            'href'   => esc_url($data['webmail']),
            'meta'   => ['target' => '_blank'],
        ]);
    }

}, 100);