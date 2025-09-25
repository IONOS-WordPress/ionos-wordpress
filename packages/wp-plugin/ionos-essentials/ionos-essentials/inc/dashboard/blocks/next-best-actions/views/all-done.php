<?php

namespace ionos\essentials\dashboard\blocks\next_best_actions;

defined('ABSPATH') || exit();
use ionos\essentials\Tenant;
use const ionos\essentials\PLUGIN_FILE;

function all_done_view(): void
{
  ?>
  <div id="ionos_next_best_actions__all_done">
   <div class="container">
    <img src="<?php echo esc_html(\plugins_url(
      '/ionos-essentials/inc/dashboard/assets/thumbs-up.svg',
      PLUGIN_FILE
    )); ?>" alt="All Done Thumb" width="100" height="100">
    <h3 class="headline"><?php echo esc_html__('You\'re all caught up!', 'ionos-essentials'); ?></h3>
    <p class="paragraph">
      <span><?php echo esc_html__(
        'You\'ve completed all of our current recommendations. This list will update automatically with new tips, so check in again soon to see what\'s next.',
        'ionos-essentials'
      ); ?></span>
    </p>
    <?php if ('ionos' === Tenant::get_slug()) { ?>
      <div class="buttons">
        <a href="#" class="button button--secondary"><?php echo esc_html__('View IONOS Help Center', 'ionos-essentials')?></a>
        <a href="#" class="button button--secondary"><?php echo esc_html__('Leave feedback', 'ionos-essentials')?></a>
      </div>
    <?php } ?>
   </div>
  </div>
  <?php
}
