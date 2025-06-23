<?php

namespace ionos\essentials\dashboard\blocks\my_account;

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

  $tenant      = strtolower(\get_option('ionos_group_brand', 'ionos'));
  $config_file = PLUGIN_DIR . '/inc/tenants/config/' . $tenant . '.php';

  if (! $tenant || ! file_exists($config_file)) {
    return null;
  }

  require $config_file;

  $market        = strtolower(\get_option($tenant . '_market', 'de'));
  $webmail_links = '';
  if ('ionos' === $tenant) {
    $webmail_links = $webmailloginlinks[$market] ?? $webmailloginlinks['de'];
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

function render_callback()
{
  $data = get_account_data();

  if (null === $data) {
    return;
  }

  $links = '';
  foreach ($data['links'] as $link) {
    $links .= sprintf(
      '<a class="button button--secondary" href="%s" target="_blank">%s</a>',
      \esc_url($data['domain'] . $link['url']),
      \esc_html($link['anchor'])
    );
  }
  if (! empty($data['webmail'])) {
    $links .= sprintf(
      '<a class="button button--secondary" href="%s" target="_blank">%s</a>',
      \esc_url($data['webmail']),
      \esc_html__('Webmail Login', 'ionos-essentials')
    );
  }

  ?>

  <div class="card ionos_my_account">
    <div class="card__content">
      <section class="card__section">
        <h2 class="card__headline"><?php echo \esc_html__('Account Management', 'ionos-essentials'); ?></h2>
        <p class="paragraph"><?php echo \esc_html__(
          'One-click access to your customer account, login security and subscriptions.',
          'ionos-essentials'
        ); ?></p>
      <div class="ionos_my_account_links">
        <?php echo \esc_html($links); ?>
      </div>
      </section>

    </div>
  </div>

<?php
}

\add_filter('ionos_dashboard_banner__register_button', function ($button_list) {
  $data = get_account_data();

  $managehosting = $data['banner_links']['managehosting'] ?? '';

  $button_list[] = [
    'link'           => $data['domain'] . $managehosting,
    'target'         => '_blank',
    'text'           => \esc_html__('Manage Hosting', 'ionos-essentials'),
    'css-attributes' => 'deeplink',
  ];
  return $button_list;
});
