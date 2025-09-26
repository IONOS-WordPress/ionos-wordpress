<?php

namespace ionos\essentials\dashboard\blocks\next_best_actions;

defined('ABSPATH') || exit();

function after_setup_view($args): void
{
  foreach ($args['actions'] as $action) {
    single_accordion_view($action);
  }
}
