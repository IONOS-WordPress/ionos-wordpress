<?php

namespace ionos_wordpress\essentials\dashboard\blocks\welcome;

use const ionos_wordpress\essentials\PLUGIN_DIR;

\add_action('init', function () {
  \register_block_type(
    PLUGIN_DIR . '/build/dashboard/blocks/welcome',
    [
      'render_callback' => __NAMESPACE__ . '\\render_callback',
    ]
  );
});

function render_callback(): string
{
  $message_version = '1.0.0';
  return "<dialog id='essentials-welcome_block'>
    <div class='horizontal-card'>
      <header class='horizontal-card__header'>
      <img class='horizontal-card__visual' src='" . \plugins_url(
    'data/tenant-logos/welcome-banner.png',
    \dirname(__DIR__, 1)
  ) . "' alt='Welcome Banner'>      </header>
      <div class='horizontal-card__content'>
        <section class='horizontal-card__section'>
          <h2 class='horizontal-card__headline'>" . sprintf(
    \__('Welcome to your %s Hub', 'ionos-essentials'),
    \get_option('ionos_group_brand_menu', 'IONOS')
  ) . "</h2>
          <p class='paragraph'>" . \__('This overview is your gateway to unlocking the full potential of your WordPress website.', 'ionos-essentials') . "</p>
        </section>
        <section class='horizontal-card__section'>
          <ul class='check-list'>
            <li>" . \__('Recommendations for next steps', 'ionos-essentials') . '</li>
            <li>' . \__('Helpful links and shortcuts', 'ionos-essentials') . '</li>
            <li>' . \__('Comprehensive help section, including: AI chat support, Guided tours and an extensive knowledge database', 'ionos-essentials') . "</li>
          </ul>
          <div class='paragraph'>" . \__('Stay tuned for exciting new features and updates to come!', 'ionos-essentials') . "</div>
        </section>
      </div>
    </div>
        <footer class='horizontal-card__footer horizontal-card__footer--small-align-center'>
          <button class='button button--primary' data-version='{$message_version}' autofocus >" . \__('Close', 'ionos-essentials') . '</button>
        </footer>
  </dialog>';
}
