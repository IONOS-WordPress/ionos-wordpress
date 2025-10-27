<?php

namespace ionos\essentials\dashboard\blocks\my_account;

defined('ABSPATH') || exit();

use ionos\essentials\Tenant;
use const ionos\essentials\PLUGIN_DIR;

/**
 * Get the data for the current tenant.
 *
 * @return array|null
 */
function get_account_data()
{
  static $data = null;

  if (null !== $data) {
    return $data;
  }

  $tenant      = Tenant::get_slug();
  $config_file = PLUGIN_DIR . '/ionos-essentials/inc/tenants/config/' . $tenant . '.php';

  if (! $tenant || ! file_exists($config_file)) {
    return null;
  }

  require $config_file;

  $market        = strtolower(\get_option($tenant . '_market', 'de'));
  $webmail_links = '';
  if ('ionos' === $tenant) {
    $webmail_links = $webmailloginlinks[$market] ?? $webmailloginlinks['de'];
  } else if ('homepl' === $tenant ) {
    $webmail_links = $webmailloginlinks['pl'];
  }

  $nba_links    = $nba_links    ?? '';
  $banner_links = $banner_links ?? '';

  $domain   = $market_domains[$market] ?? reset($market_domains);

  $data = [
    'links'        => $links,
    'domain'       => $domain,
    'market'       => $market,
    'tenant'       => $tenant,
    'nba_links'    => $nba_links,
    'webmail'      => $webmail_links,
    'banner_links' => $banner_links,
  ];

  return $data;
}

function render_callback(): void
{
  $data = get_account_data();

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
  $data = get_account_data();

  if (empty($data)) {
    return $button_list;
  }

  $managehosting = $data['banner_links']['managehosting'] ?? '';

  $button_list[] = [
    'link'           => $data['domain'] . $managehosting,
    'target'         => '_blank',
    'text'           => \esc_html__('Manage Hosting', 'ionos-essentials'),
    'css-attributes' => 'deeplink',
  ];
  return $button_list;
});
