<?php

namespace ionos\essentials\dashboard\blocks\welcome;

function render_callback(): string
{
  $user_meta = \get_user_meta(user_id: \get_current_user_id(), key: 'ionos_essentials_welcome', single: true);

  if (! empty($user_meta)) {
    return '';
  }

  $brand_name         = \get_option('ionos_group_brand_menu', 'IONOS');
  $welcome_banner_url = \plugins_url('data/welcome-banner.png', dirname(__DIR__));

  return '
<dialog id="essentials-welcome_block" open>
  <div class="dialog__content">
    <div class="horizontal-card">
        <header class="horizontal-card__header">
            <img class="horizontal-card__visual" src="' . \esc_url($welcome_banner_url) . '" alt="Welcome Banner">
        </header>
        <div class="horizontal-card__content">
            <section class="horizontal-card__section">
                <h2 class="horizontal-card__headline">
                    ' .
                    // translators: %s: Brand name
                    sprintf(\esc_html__('Welcome to your %s Hub', 'ionos-essentials'), $brand_name) . '
                </h2>
                <p class="paragraph">
                    ' . \esc_html__(
                      'This overview is your gateway to unlocking the full potential of your WordPress website.',
                      'ionos-essentials'
                    ) . '
                </p>
            </section>
            <section class="horizontal-card__section">
                <ul class="check-list">
                    <li>' . \esc_html__('Recommendations for next steps', 'ionos-essentials') . '</li>
                    <li>' . \esc_html__('Helpful links and shortcuts', 'ionos-essentials') . '</li>
                    <li>' . \esc_html__(
                      'Comprehensive help section, including: AI chat support, Guided tours and an extensive knowledge database',
                      'ionos-essentials'
                    ) . '</li>
                </ul>
            </section>
            <footer class="horizontal-card__footer horizontal-card__footer--small-align-center">
                <button class="button button--primary">
                    ' . \esc_html__('Close', 'ionos-essentials') . '
                </button>
            </footer>
        </div>
    </div>
  </div>
</dialog>
';
}
