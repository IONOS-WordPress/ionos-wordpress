<?php

namespace ionos\essentials\dashboard\blocks\next_best_actions;

defined('ABSPATH') || exit();

function render_setup_footer($args): void
{
  $category_to_show   = $args['category_to_show']   ?? 'after-setup';
  $actions            = $args['actions']            ?? [];
  $always_actions     = $args['always_actions']     ?? [];
  $show_setup_actions = $args['show_setup_actions'] ?? false;
  $completed_actions  = $args['completed_actions']  ?? 0;
  $total_actions      = $args['total_actions']      ?? 0;
  ?>

<?php

      if ($completed_actions === $total_actions) {
        // all done, show dismiss button
        printf(
          '<button class="button button-primary ionos_finish_setup" data-status="finished">%s</button>',
          \esc_html__('Finish setup', 'ionos-essentials')
        );
      } else {
        printf(
          '<button class="button ghost-button ionos_finish_setup" data-status="dismissed">%s</button>',
          \esc_html__('Dismiss getting started guide', 'ionos-essentials')
        );
      }
}
