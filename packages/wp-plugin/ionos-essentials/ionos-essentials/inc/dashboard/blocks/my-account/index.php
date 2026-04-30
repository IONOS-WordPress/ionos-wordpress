<?php

namespace ionos\essentials\dashboard\blocks\my_account;

defined('ABSPATH') || exit();

use ionos\essentials\Tenant;
use function ionos\essentials\tenant\get_tenant_config;

/**
 * Render the My Account block in dashboard (Original Function)
 */
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

/**
 * Register the "Manage Hosting" button in the dashboard banner (Original Function)
 */
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

/**
 * Add Tenant links to the WordPress Admin Bar (New Addition)
 */
function add_tenant_admin_bar($wp_admin_bar)
{
  if (! is_admin_bar_showing() || ! current_user_can('read')) {
    return;
  }

  $data = get_tenant_config();
  if (empty($data) || empty($data['links'])) {
    return;
  }

  // 1. Fetch the Tenant Icon (Favicon)
  $tenant_icon_html = '';
  $tenant_slug      = Tenant::get_slug();
  $base_path        = dirname(__DIR__, 2);
  $file_path        = $base_path . '/data/tenant-icons/' . $tenant_slug . '.svg';

  if (file_exists($file_path)) {
    $svg              = file_get_contents($file_path);
    $base64_svg       = 'data:image/svg+xml;base64,' . base64_encode($svg);

    $tenant_icon_html = sprintf(
      '<img src="%s" style="width: 18px; height: 18px; vertical-align: middle; margin-right: 8px; filter: brightness(0) invert(1);" aria-hidden="true">',
      $base64_svg
    );
  }

  // 2. Prepare the Title
  $tenant_name  = Tenant::get_label();
  $parent_title = $tenant_icon_html . sprintf('%s Links', \esc_html($tenant_name));

  // 3. Register the Parent Node
  $wp_admin_bar->add_node([
    'id'    => 'ionos_tenant_links',
    'title' => $parent_title,
    'href'  => false,
  ]);

  // 4. Add the individual links
  foreach ($data['links'] as $link) {
    $wp_admin_bar->add_node([
      'id'     => 'ionos_link_' . \sanitize_title($link['anchor']),
      'title'  => \esc_html($link['anchor']),
      'parent' => 'ionos_tenant_links',
      'href'   => \esc_url($data['domain'] . $link['url']),
      'meta'   => [
        'target' => '_blank',
      ],
    ]);
  }

  // 5. Add Webmail Login
  if (! empty($data['webmail'])) {
    $wp_admin_bar->add_node([
      'id'     => 'ionos_webmail',
      'title'  => \esc_html__('Webmail Login', 'ionos-essentials'),
      'parent' => 'ionos_tenant_links',
      'href'   => \esc_url($data['webmail']),
      'meta'   => [
        'target' => '_blank',
      ],
    ]);
  }
}

\add_action('admin_bar_menu', __NAMESPACE__ . '\add_tenant_admin_bar', 1000);
