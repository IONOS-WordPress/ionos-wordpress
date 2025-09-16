<?php

namespace ionos\essentials\dashboard\blocks\next_best_actions;

defined('ABSPATH') || exit();

function render_setup_footer($args): void
{
  // all done, show button
  if ($args['completed_actions'] === $args['total_actions']) {
    printf(
      '<button class="button button--primary ionos_finish_setup" data-status="finished"><i class="exos-icon exos-icon-check-16"></i>%s</button>',
      \esc_html__('Finish setup', 'ionos-essentials')
    );
    return;
  }

  printf(
    '<button class="ghost-button ionos_finish_setup" data-status="dismissed">%s</button>',
    \esc_html__('Dismiss getting started guide', 'ionos-essentials')
  );
}
