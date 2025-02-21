<?php

namespace ionos_wordpress\essentials\dashboard\blocks\next_best_actions;

use ionos_wordpress\essentials\dashboard\blocks\next_best_actions\model\NBA;

require_once __DIR__ . '/model.php';

printf('<h3>%s</h3>', \esc_html__('Next best actions ⚡', 'ionos-essentials'));
echo '<div class="wp-block-list">';
foreach (NBA::getActions() as $action) {
  $completed = $action->completed;
  printf(
    '<p>%s<a href="%s" target="_top">%s</a>%s</p>%s',
    $completed ? '<s>' : '',
    \esc_url($action->link),
    \esc_html($action->title),
    $completed ? '</s>' : '',
    $completed ? '' : "<button id='{$action->id}' class='dismiss-nba'>dismiss</button>",
  );
}
echo '</div>';

