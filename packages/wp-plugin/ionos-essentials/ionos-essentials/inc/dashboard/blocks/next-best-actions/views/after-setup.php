<?php

namespace ionos\essentials\dashboard\blocks\next_best_actions;

defined('ABSPATH') || exit();

function after_setup_view($args): void
{
  echo '<div class="single-accordion-actions">';
  foreach ($args['actions'] as $action) {
    echo '<div class="card">';
    single_accordion_view($action);
    echo '</div>';
  }
  echo '</div>';
}
