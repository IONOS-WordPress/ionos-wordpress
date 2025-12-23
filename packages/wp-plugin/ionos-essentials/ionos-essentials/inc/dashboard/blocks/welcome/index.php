<?php

namespace ionos\essentials\dashboard\blocks\welcome;

defined('ABSPATH') || exit();

use ionos\essentials\Tenant;

function render_callback(): void
{
  $user_meta = \get_user_meta(user_id: \get_current_user_id(), key: 'ionos_essentials_welcome', single: true);

  if (! empty($user_meta)) {
    return;
  }

  $brand_label        = Tenant::get_label();
  $welcome_banner_url = \esc_url(\plugins_url('data/welcome-banner.png', dirname(__DIR__)));

  if (\get_option('ionos_essentials_bk_rollout', false)) {
    // translators: %s: Brand name
    $headline        = sprintf(\esc_html__('Welcome to your new %s Hub', 'ionos-essentials'), $brand_label);
    $paragraph       = \esc_html__(
      'You\'re now experiencing the latest evolution of our dashboard, designed to streamline your workflow and enhance your online management. This new hub replaces your previous dashboard, bringing you a fresher interface, improved tools, and exciting new features.',
      'ionos-essentials'
    );
    $checklist_item1 = \esc_html__('Get familiar with the new layout', 'ionos-essentials');
    $checklist_item2 = \esc_html__('Explore redesigned sections and discover new functionalities', 'ionos-essentials');
    $checklist_item3 = \esc_html__('Stay tuned for upcoming feature releases and enhancements', 'ionos-essentials');
  } else {
    // translators: %s: Brand name
    $headline        = sprintf(\esc_html__('Welcome to your %s Hub', 'ionos-essentials'), $brand_label);
    $paragraph       = \esc_html__(
      'This overview is your gateway to unlocking the full potential of your WordPress website.',
      'ionos-essentials'
    );
    $checklist_item1 = \esc_html__('Recommendations for next steps', 'ionos-essentials');
    $checklist_item2 = \esc_html__('Helpful links and shortcuts', 'ionos-essentials');
    $checklist_item3 = \esc_html__(
      'Comprehensive help section, including: AI chat support, Guided tours and an extensive knowledge database',
      'ionos-essentials'
    );
  }

  $close_button_label = \esc_html__('Close', 'ionos-essentials');

  printf(
    <<<EOF
<dialog id="essentials-welcome_block" class="ionos-essentials-popup" open>
  <div class="dialog__content">
    <div class="horizontal-card popup-card">
        <header class="horizontal-card__header" style="justify-content: center;">
            <img src="{$welcome_banner_url}" alt="Welcome Banner">
        </header>
        <div class="horizontal-card__content">
            <section class="horizontal-card__section" style="flex-grow: 0;">
                <h2 class="headline">{$headline}</h2>
                <p class="paragraph">{$paragraph}</p>
                <ul class="check-list">
                    <li>{$checklist_item1}</li>
                    <li>{$checklist_item2}</li>
                    <li>{$checklist_item3}</li>
                </ul>
             </section>
        </div>
        <footer class="horizontal-card__footer horizontal-card__content--vertical-align-center" style="width: 100%%;display: flex; justify-content: center;">
            <button class="button button--primary" id="ionos-welcome-close">{$close_button_label}</button>
        </footer>
    </div>
  </div>
</dialog>
EOF
  );
}
