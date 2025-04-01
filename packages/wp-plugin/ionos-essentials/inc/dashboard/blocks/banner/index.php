<?php

namespace ionos_wordpress\essentials\dashboard\blocks\banner;

use const ionos_wordpress\essentials\PLUGIN_DIR;

\add_action('init', function () {
  \register_block_type(
    PLUGIN_DIR . '/build/dashboard/blocks/banner',
    [
      'render_callback' => __NAMESPACE__ . '\\render_callback',
    ]
  );
});

const MAIN_TEMPLATE   = '<div class="wp-block-buttons banner-buttons">%s</div>';
const BUTTON_TEMPLATE = '<div class="wp-block-button button"><a href="%s" target="%s" class="wp-block-button__link has-text-align-center wp-element-button %s">%s</a></div>';
function render_callback(): string
{
  $button_list = [];

  $view_site = [
    [
      'link'           => \home_url(),
      'text'           => \__('View Site', 'ionos-essentials'),
      'css-attributes' => 'viewsite',
    ],
  ];

  $button_list = \array_merge($button_list, get_ai_button());
  $button_list = \apply_filters('ionos_dashboard_banner__register_button', $button_list);
  $button_list = \array_merge($button_list, $view_site);

  $button_html = \implode('', \array_map(fn (array $button): string => \sprintf(
    BUTTON_TEMPLATE,
    \esc_url($button['link'] ?? '#'),
    $button['target']         ?? '_top',
    $button['css-attributes'] ?? '',
    \esc_html($button['text'] ?? '')
  ), $button_list));

  return \sprintf(MAIN_TEMPLATE, $button_html);
}

function get_ai_button(): array
{
  if ('extendable' !== \get_option('stylesheet') || ! \is_plugin_active('extendify/extendify.php')) {
    return [];
  }

  $launchCompleted = \get_option('extendify_onboarding_completed', false);
  if (false === $launchCompleted) {
    return [
      [
        'link'           => \admin_url('admin.php?page=extendify-launch'),
        'text'           => \__('Start AI Sitebuilder', 'ionos-essentials'),
        'css-attributes' => 'startai',
      ], ];
  }

  if (strtotime($launchCompleted) > time() - (3 * 24 * 60 * 60)) {
    return [
      [
        'link'           => \admin_url('admin.php?page=extendify-launch'),
        'text'           => \__('Retry AI', 'ionos-essentials'),
        'css-attributes' => 'retryai',
      ], ];
  }

  return [];
}
