<?php

namespace ionos\essentials\dashboard\blocks\next_best_actions;

defined('ABSPATH') || exit();

function setup_view($args): void
{
  $category_to_show   = $args['category_to_show']   ?? 'after-setup';
  $actions            = $args['actions']            ?? [];
  $always_actions     = $args['always_actions']     ?? [];
  $show_setup_actions = $args['show_setup_actions'] ?? false;
  $completed_actions  = $args['completed_actions']  ?? 0;
  $total_actions      = $args['total_actions']      ?? 0;

  render_setup_header($args);
  ?>
   <div class="grid nba-setup">
    <?php
        foreach ($actions as $action) {
          single_view($action);
        }
    echo '</div>';
    render_setup_footer($args);

    echo '<div class="grid nba-setup">';
    // Always show always actions
    foreach ($always_actions as $action) {
      single_view($action);
    }
    echo '</div>';
}
