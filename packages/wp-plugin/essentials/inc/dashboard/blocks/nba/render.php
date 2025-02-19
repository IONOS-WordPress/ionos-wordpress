<?php

namespace ionos_wordpress\essentials\dashboard\blocks\next_best_actions;

use ionos_wordpress\essentials\dashboard\blocks\next_best_actions\model\NBA;

$model_path = __DIR__ . '/model.php';

if (! file_exists($model_path)) {
  return;
}

require_once $model_path;

printf('<h3>%s</h3>', \esc_html__('Next best actions ⚡', 'ionos-essentials'));

$actions= [];
for ($i = 1; $i <= 20; $i++) {
  $actions[] =
    new NBA(
      id: 'checkPluginsPage' . $i,
      title: 'NBA' . $i,
      link: admin_url('plugins.php?complete_nba=checkPluginsPage' . $i),
      completed_callback: fn () => !!random_int(0, 1)
    );
}

echo '<ul class="wp-block-list">';
foreach ($actions as $action) {
  $completed = $action->completed;
  printf(
    '<li>%s<a href="%s" target="_top">%s</a>%s</li>',
    $completed ? '<s>' : '',
    \esc_url($action->link),
    \esc_html($action->title),
    $completed ? '</s>' : ''
  );
}
echo '</ul>';

