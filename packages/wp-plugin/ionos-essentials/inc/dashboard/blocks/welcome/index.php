<?php

namespace ionos\essentials\dashboard\blocks\welcome;

function render_callback()
{
  $user_meta = \get_user_meta(user_id: \get_current_user_id(), key: 'ionos_essentials_welcome', single: true);

  if (! empty($user_meta)) {
    return;
  }

  $brand_name         = \get_option('ionos_group_brand_menu', 'IONOS');
  $welcome_banner_url = \plugins_url('data/welcome-banner.png', dirname(__DIR__));

  ?>
<dialog id="essentials-welcome_block" open>
  <div class="dialog__content">
    <div class="horizontal-card">
        <header class="horizontal-card__header">
            <img class="horizontal-card__visual" src="<?php echo \esc_url($welcome_banner_url); ?>" alt="Welcome Banner">
        </header>
        <div class="horizontal-card__content">
            <section class="horizontal-card__section">
                <h2 class="horizontal-card__headline">
                    <?php
                      // translators: %s: Brand name
                      printf(\esc_html__('Welcome to your %s Hub', 'ionos-essentials'), $brand_name);
  ?>
                </h2>
                <p class="paragraph">
                    <?php
    \esc_html_e(
      'This overview is your gateway to unlocking the full potential of your WordPress website.',
      'ionos-essentials'
    );
  ?>
                </p>
            </section>
            <section class="horizontal-card__section">
                <ul class="check-list">
                    <li><?php \esc_html_e('Recommendations for next steps', 'ionos-essentials'); ?></li>
                    <li><?php \esc_html_e('Helpful links and shortcuts', 'ionos-essentials'); ?></li>
                    <li><?php \esc_html_e(
                      'Comprehensive help section, including: AI chat support, Guided tours and an extensive knowledge database',
                      'ionos-essentials'
                    ); ?></li>
                </ul>
            </section>
            <footer class="horizontal-card__footer horizontal-card__content--vertical-align-center">
                <button class="button button--primary">
                    <?php \esc_html_e('Close', 'ionos-essentials'); ?>
                </button>
            </footer>
        </div>
    </div>
  </div>
</dialog>

<?php
}
