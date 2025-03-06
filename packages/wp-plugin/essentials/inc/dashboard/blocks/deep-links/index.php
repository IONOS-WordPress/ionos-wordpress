<?php

namespace ionos_wordpress\essentials\dashboard\blocks\deep_links;

use const ionos_wordpress\essentials\PLUGIN_DIR;

\add_action('init', function () {
  \register_block_type(
    PLUGIN_DIR . '/build/dashboard/blocks/deep-links',
    [
      'render_callback' => 'ionos_wordpress\essentials\dashboard\blocks\deep_links\render_callback'
    ]
  );
});

function get_deep_links_data() {
  $tenant = strtolower(\get_option('ionos_group_brand', false));
  $config_file = __DIR__ . '/config/' . $tenant . '.php';

  if (! $tenant || ! file_exists($config_file)) {
    return null;
  }

  require $config_file;

  $market = strtolower(\get_option($tenant . '_market', 'de'));
  $domain = $market_domains[$market] ?? reset($market_domains);

  return [
    'links' => $links,
    'domain' => $domain,
  ];
}

function render_callback() {
  $data = get_deep_links_data();

  if ($data) {
    $template = '
        <h3>%s</h3>
        <ul class="wp-block-list">%s</ul>';

    $headline = \esc_html__('Deep-Links', 'ionos-essentials');

    $body = '';
    foreach ($data['links'] as $link) {
      $body .= sprintf(
        '<li><a href="%s" target="_blank">%s</a></li>',
        \esc_url($data['domain'] . $link['url']),
        \esc_html($link['anchor'])
      );
    }

    if (!empty($body)) {
      return sprintf($template, $headline, $body);
    }
  }
}

\add_action('ionos_dashboard__register_nba_element', function () {
  $data = get_deep_links_data();

  if (! $data) {
    return null;
  }
  \ionos_wordpress\essentials\dashboard\blocks\next_best_actions\model\NBA::register(
    id: 'connectYourDomain',
    title: \esc_html__('Connect your domain', 'ionos-essentials'),
    description: \esc_html__('Connect your domain to your website to make it accessible to your visitors.', 'ionos-essentials'),
    link: \esc_url($data['domain']),
    completed: strpos(home_url(), 'live-website.com') === false && strpos(home_url(), 'localhost') === false,
  );
});
