<?php

namespace ionos\essentials\dashboard\blocks\next_best_actions;

defined('ABSPATH') || exit();

function setup_view($args): void
{
  // Setup specific actions
  echo '<div class="card"><div id="ionos_nba_setup_container" class="card__section">';
  render_setup_header($args);
  echo '<ul class="panel nba-setup">';
  foreach ($args['actions'] as $action) {
    single_view($action);
  }
  echo '</ul>';
  render_setup_footer($args);
  echo '</div></div>';
  setup_complete();

  // Actions that should always be shown
  echo '<div class="single-accordion-actions">';
  foreach ($args['always_actions'] as $action) {
    echo '<div class="card">';
    single_accordion_view($action);
    echo '</div>';
  }
  echo '</div>';
}
