<?php

namespace ionos\essentials\dashboard\blocks\whatsnew;

function render_callback()
{

  ?>

<div class="card">
    <div class="card__content">
      <section class="card__section">
        <img src="<?php echo \esc_url(\plugins_url('', __FILE__) . '/whats-new.png'); ?>" style="float: right; height: 120px;">

        <h2 class="card__headline"><?php echo \esc_html__('What’s New', 'ionos-essentials'); ?></h2>
        <p class="paragraph"><?php echo \esc_html__('Get an overview of the latest updates and enhancements.', 'ionos-essentials'); ?></p>

        <ul class="check-list">
            <li>
              <?php echo \esc_html__('New User Interface', 'ionos-essentials'); ?>
              <?php echo \esc_html__(
                'Featuring a completely new design and an improved user experience, making managing your website easier than ever.',
                'ionos-essentials'
              ); ?>
            </li>
            <li>
              <?php echo \esc_html__('Vulnerability Scan Results', 'ionos-essentials'); ?>
              <?php echo \esc_html__(
                "We've added scan results directly to your dashboard, giving you instant visibility into potential security risks.",
                'ionos-essentials'
              ); ?>
            </li>
          </ul>
      </section>

    </div>
  </div>
<?php
}
