<?php

namespace ionos_wordpress\essentials\dashboard\blocks\deep_links;

use const ionos_wordpress\essentials\PLUGIN_DIR;

\add_action('init', function () {
  \register_block_type(
    PLUGIN_DIR . '/build/dashboard/blocks/deep-links',
    [
      'render_callback' => 'ionos_wordpress\essentials\dashboard\blocks\deep_links\render_callback',
    ]
  );
});

/**
 * Get the data for the current tenant.
 *
 * @return array|null
 */
function get_deep_links_data()
{
  static $data = null;

  if (null !== $data) {
    return $data;
  }

  $tenant      = strtolower(\get_option('ionos_group_brand', false));
  $config_file = PLUGIN_DIR . '/inc/tenants/config/' . $tenant . '.php';

  if (! $tenant || ! file_exists($config_file)) {
    return null;
  }

  require $config_file;

  $market = strtolower(\get_option($tenant . '_market', 'de'));
  $domain = $market_domains[$market] ?? reset($market_domains);

  $data = [
    'links'  => $links,
    'domain' => $domain,
  ];

  return $data;
}

function render_callback()
{
  $data = get_deep_links_data();

  if (null === $data) {
    return null;
  }

  $template = '
  <div class="wp-block-column deep-links">
      <h3 class="wp-block-heading">%s</h3>
      <p>%s</p>
    <div class="wp-block-group">
    %s
    </div>
  </div>';

  $headline    = \esc_html__('Account Management', 'ionos-essentials');
  $description = \esc_html__(
    'One-click access to your customer account, login security and subscriptions',
    'ionos-essentials'
  );

  $body = '';
  foreach ($data['links'] as $link) {
    $body .= sprintf(
      '<div class="wp-block-group has-background element">
        <a class="element-link" href="%s" target="_blank">
          <p class="has-text-align-center has-small-font-size">%s</p>
        </a>
      </div>',
      \esc_url($data['domain'] . $link['url']),
      \esc_html($link['anchor'])
    );
  }

  if (! empty($body)) {
    return sprintf($template, $headline, $description, $body);
  }
}

\add_filter('ionos_dashboard_banner__register_button', function ($button_list) {
  $data = get_deep_links_data();

  $button_list[] = [
    'link'           => $data['domain'],
    'target'         => '_blank',
    'text'           => \esc_html__('Manage Hosting', 'ionos-essentials'),
    'css-attributes' => 'deeplink',
  ];
  return $button_list;
});
