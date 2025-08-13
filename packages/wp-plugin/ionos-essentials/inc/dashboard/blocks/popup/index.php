<?php

namespace ionos\essentials\dashboard\blocks\popup;

defined('ABSPATH') || exit();

function render_callback(): void
{
  // $user_meta = \get_user_meta(user_id: \get_current_user_id(), key: 'ionos_essentials_popup', single: true);

  // if (! empty($user_meta)) {
  //   return;
  // }

  ?>
<dialog class="ionos-essentials-popup" open>
  <div class="dialog__content">
    <div class="horizontal-card popup-card">

        <div class="horizontal-card__content">
            <section class="horizontal-card__section" style="flex-grow: 0;">

                <h2 class="headline">
                    <?php \esc_html_e('Your Feedback is important to us', 'ionos-essentials'); ?>
                </h2>
                 <p><?php \esc_html_e(
                   'We\'re always looking for ways to make your WordPress hosting experience even better. Please take a few minutes to fill out a quick online survey.',
                   'ionos-essentials'
                 ); ?></p>

             </section>
        </div>
        <footer class="horizontal-card__footer horizontal-card__content--vertical-align-center" style="width: 100%;display: flex; justify-content: center;">
            <button class="button button--secondary">
                <?php \esc_html_e('Close', 'ionos-essentials'); ?>
            </button>
            <button class="button button--primary">
                <?php \esc_html_e('Take the survey', 'ionos-essentials'); ?>
            </button>
        </footer>
    </div>
  </div>
</dialog>

<?php
}
