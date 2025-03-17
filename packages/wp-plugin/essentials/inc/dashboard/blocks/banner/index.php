<?php

namespace ionos_wordpress\essentials\dashboard\blocks\banner;

use const ionos_wordpress\essentials\PLUGIN_DIR;

const BUTTON_TEMPLATE = '<div class="wp-block-button has-custom-width wp-block-button__width-75"><a href="%s" target="%s" class="wp-block-button__link has-text-align-center wp-element-button">%s</a></div>';
const MAIN_TEMPLATE = '<div class="wp-block-buttons has-custom-font-size has-large-font-size is-horizontal is-content-justification-right is-layout-flex wp-container-core-buttons-is-layout-1 wp-block-buttons-is-layout-flex">%s</div>';

\add_action('init', function () {
  \register_block_type(
    PLUGIN_DIR . '/build/dashboard/blocks/banner',
    [
      'render_callback' => __NAMESPACE__ . '\\render_callback',
    ]
  );
});

/**
 * Render callback for the banner block.
 *
 * @return string
 */
function render_callback()
{
  $button_list = [
    ['link' => '#', 'target' => '_top', 'text' => \esc_html__('Start AI Sitebuilder', 'ionos-essentials')],
    ['link' => '#', 'target' => '_top', 'text' => \esc_html__('Manage Hosting', 'ionos-essentials')],
  ];

  $button_list = \array_merge($button_list, get_ai_button());
  $button_html = \implode('', \array_map(__NAMESPACE__ . '\format_button', $button_list));

  return \sprintf(MAIN_TEMPLATE, $button_html);
}

/**
 * Format a button array into HTML.
 * Used in array_map.
 *
 * @param array $button
 * @return string
 */
function format_button($button)
{
  return \sprintf(
    BUTTON_TEMPLATE,
    \esc_url($button['link']),
    $button['target'],
    \esc_html($button['text'], 'ionos-essentials')
  );
}

/**
 * Get AI button based on conditions.
 *
 * @return array
 */
function get_ai_button()
{
  if (!is_extendable_theme() || !is_extendify_plugin_active()) {
    return [];
  }

  $launchCompleted = \get_option('extendify_onboarding_completed', false);
  if ($launchCompleted === false) {
    return [['link' => \admin_url('admin.php?page=extendify-launch'), 'target' => '_top', 'text' => \esc_html__('Start AI Sitebuilder', 'ionos-essentials')]];
  }

  try {
    $datetime1 = new \DateTime($launchCompleted);
    $interval = $datetime1->diff(new \DateTime());

    if ($interval->days <= 2) {
      return [['link' => \admin_url('admin.php?page=extendify-launch'), 'target' => '_top', 'text' => \esc_html__('Retry AI', 'ionos-essentials')]];
    }
  } catch (\Exception $exception) {
    // Handle exception if needed
  }

  return [];
}

/**
 * Check if the current theme is Extendable.
 *
 * @return bool
 */
function is_extendable_theme()
{
  return 'extendable' === \get_option('stylesheet');
}

/**
 * Check if the Extendify plugin is active.
 *
 * @return bool
 */
function is_extendify_plugin_active()
{
  return \is_plugin_active('extendify/extendify.php');
}
