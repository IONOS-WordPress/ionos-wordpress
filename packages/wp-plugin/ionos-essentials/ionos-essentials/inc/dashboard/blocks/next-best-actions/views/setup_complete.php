<?php

namespace ionos\essentials\dashboard\blocks\next_best_actions;

defined('ABSPATH') || exit();
use const ionos\essentials\PLUGIN_FILE;

function setup_complete(): void
{
  ?>
  <div id="ionos_next_best_actions__setup_complete" style="display:none;">
   <div class="container">
    <img src="<?php echo \plugins_url('/ionos-essentials/inc/dashboard/assets/website-tick.svg', PLUGIN_FILE)?>" alt="All Done Thumb" width="100" height="100">
    <h3 class="headline"><?php echo esc_html__('Your Foundation is Built!', 'ionos-essentials'); ?></h3>
    <p class="paragraph">
      <span><?php echo esc_html__('Congratulations! You\'ve successfully set up your WordPress site.','ionos-essentials'); ?></span>
      <span class="paragraph"><?php echo esc_html__('Now for the fun part, exploring ways to grow and enhance it.','ionos-essentials'); ?></span>
    </p>
    <a href="" class="button button--secondary"><?php echo esc_html__('Explore What\'s Next', 'ionos-essentials')?></a>
   </div>
  </div>
  <?php
}
