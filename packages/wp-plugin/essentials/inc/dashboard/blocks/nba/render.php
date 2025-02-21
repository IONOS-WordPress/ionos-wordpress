<?php

namespace ionos_wordpress\essentials\dashboard\blocks\next_best_actions;

use ionos_wordpress\essentials\dashboard\blocks\next_best_actions\model\NBA;

require_once __DIR__ . '/model.php';
$actions = NBA::getActions();
if (empty($actions)) {
  return;
}

echo '<div class="wp-block-group alignwide">';
// headline
echo '<div class="wp-block-group">';
printf('<h2 class="wp-block-heading">%s</h2>', \esc_html__('Next best actions ⚡', 'ionos-essentials'));
printf('<p>%s</p>', \esc_html__('Description of this block', 'ionos-essentials'));
echo '</div>';
echo '<div class="wp-block-columns">';


foreach ($actions as $action) {
  $active = $action->active;
  if (! $active) {
    continue;
  }
  echo '<div class="wp-block-column is-style-default has-border-color">';
  printf('<h2 class="wp-block-heading">%s</h2>', \esc_html($action->title, 'ionos-essentials'));
  printf('<p>%s</p>', \esc_html($action->title, 'ionos-essentials'));

  printf(
    '<p>%s<a href="%s" target="_top">%s</a>%s</p>%s',
    $active ? '' : '<s>',
    \esc_url($action->link),
    \esc_html($action->title),
    $active ? '' : '</s>',
    $active ? "<button id='{$action->id}' class='dismiss-nba'>dismiss</button>" : "",
  );
  echo '</div>';
}

echo '</div>';
echo '</div>';
