<?php

namespace ionos\essentials\dashboard\blocks\whatsnew;

function render_callback()
{
  $brand_name         = \get_option('ionos_group_brand_menu', 'IONOS');

  ?>

<div class="card">
    <div class="card__content">
      <section class="card__section">
        <img src="<?php echo \esc_url(\plugins_url('', __FILE__) . '/whats-new.png'); ?>" style="float: right; height: 120px;">

        <h2 class="headline headline--sub"><?php echo \esc_html__('What’s New', 'ionos-essentials'); ?></h2>
        <p class="paragraph"><?php echo \esc_html__('Get an overview of the latest updates and enhancements.', 'ionos-essentials'); ?></p>

        <ul class="check-list">
            <li>
              <h3 class="headline headline--sub"><?php echo \esc_html__('Your Feedback is important to us', 'ionos-essentials'); ?></h3>
              <p><?php echo \esc_html__(
                'We\'re always looking for ways to make your WordPress hosting experience even better. Please take a few minutes to fill out a quick online survey.',
                'ionos-essentials'
              ); ?></p>
              <a href="https://ionos.typeform.com/to/oh2b0k" target="_blank" class="link link--action">
                <?php echo \esc_html__('Take the survey', 'ionos-essentials'); ?>
              </a>
            </li>
            <li>
              <h3 class="headline headline--sub"><?php printf(\esc_html__('Your %s Hub Just Got a Powerful Upgrade!', 'ionos-essentials'), $brand_name); ?></h3>
              <p><?php echo \esc_html__(
                "We've just rolled out a brand-new \"Tools & Security\" tab! All the features and user interfaces from your previous security plugin have now found their new home here, making everything more centralized and easier to manage. Plus, you'll find a new maintenance page function that you can switch on whenever you need it.",
                'ionos-essentials'
              ); ?></p>
            </li>
          </ul>
      </section>

    </div>
  </div>
<?php
}
