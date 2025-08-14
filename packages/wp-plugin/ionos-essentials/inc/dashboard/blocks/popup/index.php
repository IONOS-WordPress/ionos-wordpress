<?php

namespace ionos\essentials\dashboard\blocks\popup;

defined('ABSPATH') || exit();

function render_callback(): void
{
  // If the popup was maybe dismissed or already shown, or if it's not time yet, exit early.
  $ionos_popup_after_timestamp = (int) \get_user_meta(\get_current_user_id(), 'ionos_popup_after_timestamp', true);
  if (empty($ionos_popup_after_timestamp) || $ionos_popup_after_timestamp > time()) {
    return;
  }

  // If the welcome message is being shown, exit early.
  if (empty(\get_user_meta(\get_current_user_id(), 'ionos_essentials_welcome', true))) {
    return;
  }
  ?>
<dialog id="ionos-essentials-popup" class="ionos-essentials-popup" open>
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
            <button class="button button--secondary ionos-popup-dismiss">
                <?php \esc_html_e('Close', 'ionos-essentials'); ?>
            </button>
            <a href="<?php echo \esc_attr(
              \ionos\essentials\dashboard\blocks\whatsnew\get_survey_url()
            ); ?>" class="button button--primary ionos-popup-dismiss" target="_blank">
              <?php \esc_html_e('Take the survey', 'ionos-essentials'); ?>
            </a>
        </footer>
    </div>
  </div>
</dialog>

<?php
}
