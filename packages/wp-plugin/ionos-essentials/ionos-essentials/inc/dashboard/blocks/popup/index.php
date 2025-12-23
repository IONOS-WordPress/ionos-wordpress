<?php

namespace ionos\essentials\dashboard\blocks\popup;

use ionos\essentials\Tenant;

defined('ABSPATH') || exit();

function render_callback(): void
{
  if ('ionos' !== Tenant::get_slug()) {
    return;
  }

  // If the popup was maybe dismissed or already shown, or if it's not time yet, exit early.
  $ionos_popup_after_timestamp = (int) \get_user_meta(\get_current_user_id(), 'ionos_popup_after_timestamp', true);
  if (empty($ionos_popup_after_timestamp) || $ionos_popup_after_timestamp > time()) {
    return;
  }

  // If the welcome message is being shown, exit early.
  if (empty(\get_user_meta(\get_current_user_id(), 'ionos_essentials_welcome', true))) {
    return;
  }

  $headline  = \esc_html__('Your Feedback is important to us', 'ionos-essentials');
  $paragraph = \esc_html__(
    'We\'re always looking for ways to make your WordPress hosting experience even better. Please take a few minutes to fill out a quick online survey.',
    'ionos-essentials'
  );
  $close_button_text  = \esc_html__('Close', 'ionos-essentials');
  $survey_button_text = \esc_html__('Take the survey', 'ionos-essentials');
  $survey_url         = \esc_attr(\ionos\essentials\dashboard\blocks\whatsnew\get_survey_url());

  printf(
    <<<EOF
    <dialog id="ionos-essentials-popup" class="ionos-essentials-popup" open>
      <div class="dialog__content">
        <div class="horizontal-card popup-card">

            <div class="horizontal-card__content">
                <section class="horizontal-card__section" style="flex-grow: 0;">

                    <h2 class="headline">
                        {$headline}
                    </h2>
                    <p class="paragraph">
                      {$paragraph}
                    </p>

                </section>
            </div>
            <footer class="horizontal-card__footer horizontal-card__content--vertical-align-center" style="width: 100%;display: flex; justify-content: center;">
                <button class="button button--secondary ionos-popup-dismiss">
                    {$close_button_text}
                </button>
                <a href="{$survey_url}" class="button button--primary ionos-popup-dismiss" target="_blank">
                  {$survey_button_text}
                </a>
            </footer>
        </div>
      </div>
    </dialog>
    EOF
  );
}
