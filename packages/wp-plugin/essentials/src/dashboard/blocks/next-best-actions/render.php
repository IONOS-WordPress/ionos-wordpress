<?php

// phpcs:disable WordPress.WP.GlobalVariablesOverride.Prohibited

namespace ionos_wordpress\essentials\dashboard\blocks\next_best_actions;

use ionos_wordpress\essentials\dashboard\blocks\next_best_actions\model\NBA;

$model_path = __DIR__ . '/model.php';

if (! file_exists($model_path)) {
  return;
}

require_once $model_path;

printf('<h3>%s</h3>', \esc_html__('Next best actions ⚡', 'ionos-essentials'));

$actions = [
  new NBA(
    id: 'checkPluginsPage',
    title: 'Check Plugins Page',
    link: admin_url('plugins.php'),
    completed_callback: fn () => ! ! random_int(0, 1)
  ),
];
$actions = array_merge($actions, $actions, $actions, $actions, $actions);

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

// debug callback on dashboard
//echo '<pre>';
//$actions[0]->get_callback();
//wp_die();
