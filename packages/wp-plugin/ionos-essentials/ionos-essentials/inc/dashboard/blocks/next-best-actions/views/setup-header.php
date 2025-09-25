<?php

namespace ionos\essentials\dashboard\blocks\next_best_actions;

defined('ABSPATH') || exit();

function render_setup_header($args): void
{
  $category_to_show   = $args['category_to_show']   ?? 'after-setup';
  $actions            = $args['actions']            ?? [];
  $always_actions     = $args['always_actions']     ?? [];
  $show_setup_actions = $args['show_setup_actions'] ?? false;
  $completed_actions  = $args['completed_actions']  ?? 0;
  $total_actions      = $args['total_actions']      ?? 0;
  ?>

 <div class="headline"><?php \esc_html_e('🚀  Getting started with WordPress', 'ionos-essentials'); ?></div>
 <div class="container">
    <div class="paragraph"><?php \esc_html_e(
      'Ready to establish your online presence? Let\'s get the essentials sorted so your new site looks professional and is easy for people to find.',
      'ionos-essentials'
    ); ?></div>

    <div style="width: 350px">
      <div class="quotabar">
        <div class="quotabar__bar quotabar__bar--small">
          <span class="quotabar__value" style="width: <?php echo \esc_attr($completed_actions / $total_actions * 100); ?>%;"></span>
        </div>
          <p class="quotabar__text">
            <?php
              if ($completed_actions === $total_actions) {
                \esc_html_e('All actions completed!', 'ionos-essentials');
              } else {
                // translators: 1: number of completed actions, 2: total number of actions
                printf(__(' %1$d of %2$d completed', 'ionos-essentials'), $completed_actions, $total_actions);
              }
  ?>
        </p>
      </div>
    </div>
  </div>

<?php
}
