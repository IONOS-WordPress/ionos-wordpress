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
    <div class="horizontal-card welcome-card">
        <header class="horizontal-card__header" style="justify-content: center;">
            <img src="<?php echo \esc_url($welcome_banner_url); ?>" alt="Welcome Banner">
        </header>
        <div class="horizontal-card__content">
            <section class="horizontal-card__section" style="flex-grow: 0;">
                <h2 class="headline">
                    <?php
                      // translators: %s: Brand name
                      printf(\esc_html__('Welcome to your new %s Hub', 'ionos-essentials'), $brand_name);
  ?>
                </h2>
                <p class="paragraph">
                    <?php
    \esc_html_e(
      'You\'re now experiencing the latest evolution of our dashboard, designed to streamline your workflow and enhance your online management. This new hub replaces your previous dashboard, bringing you a fresher interface, improved tools, and exciting new features.',
      'ionos-essentials'
    );
  ?>
                </p>

                <ul class="check-list">
                    <li><?php \esc_html_e('Get familiar with the new layout', 'ionos-essentials'); ?></li>
                    <li><?php \esc_html_e('Explore redesigned sections and discover new functionalities', 'ionos-essentials'); ?></li>
                    <li><?php \esc_html_e('Stay tuned for upcoming feature releases and enhancements','ionos-essentials'); ?></li>
                </ul>
             </section>
        </div>
        <footer class="horizontal-card__footer horizontal-card__content--vertical-align-center" style="width: 100%;display: flex; justify-content: center;">
            <button class="button button--primary">
                <?php \esc_html_e('Close', 'ionos-essentials'); ?>
            </button>
        </footer>
    </div>
  </div>
</dialog>

<?php
}
