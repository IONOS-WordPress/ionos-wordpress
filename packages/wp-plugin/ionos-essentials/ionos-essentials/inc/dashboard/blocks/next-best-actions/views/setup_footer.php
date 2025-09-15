<?php

namespace ionos\essentials\dashboard\blocks\next_best_actions;

defined('ABSPATH') || exit();

function render_setup_footer($args): void
{
  // all done, show dismiss button
  if ($args['completed_actions'] === $args['total_actions']) {
    printf(
      '<button class="button button-primary ionos_finish_setup" data-status="finished">%s</button>',
      \esc_html__('Finish setup', 'ionos-essentials')
    );
    return;
  }

  printf(
    '<button class="button ghost-button ionos_finish_setup" data-status="dismissed">%s</button>',
    \esc_html__('Dismiss getting started guide', 'ionos-essentials')
  );
}
