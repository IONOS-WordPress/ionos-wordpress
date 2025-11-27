<?php

namespace ionos\essentials\dashboard\blocks\next_best_actions;

defined('ABSPATH') || exit();
use const ionos\essentials\PLUGIN_FILE;

function render_setup_footer($args): void
{
  // all done, show button
  if ($args['completed_actions'] === $args['total_actions']) {
    printf(
      '<button class="button button--primary ionos_finish_setup" data-status="finished"><img src="%s" alt="Check Icon" width="12" height="12"></span>%s</button>',
      \esc_html(\plugins_url('/ionos-essentials/inc/dashboard/assets/check-16.svg', PLUGIN_FILE)),
      \esc_html__('Finish setup', 'ionos-essentials')
    );
    return;
  }

  printf(
    '<button class="ghost-button ionos_finish_setup" data-status="dismissed">%s</button>',
    \esc_html__('Dismiss getting started guide', 'ionos-essentials')
  );
}
