<?php

namespace ionos\essentials\dashboard\blocks\next_best_actions;

defined('ABSPATH') || exit();

function setup_view($args): void
{
  // Setup specific actions
  echo '<div id="ionos_nba_setup_container">';
  render_setup_header($args);
    echo '<div class="grid nba-setup">';
      foreach ($args['actions'] as $action) {
        single_view($action);
      }
    echo '</div>';
  render_setup_footer($args);
  echo '</div>';
  setup_complete();

  // Actions that should always be shown
  echo '<div class="grid">';
    foreach ($args['always_actions'] as $action) {
      single_view($action);
    }
  echo '</div>';
}
