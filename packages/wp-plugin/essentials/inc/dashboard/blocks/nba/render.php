<?php

namespace ionos_wordpress\essentials\dashboard\blocks\next_best_actions;

$actions = getNbaAll();
if (empty($actions)) {
  return;
}

$template = '
<div id="ionos-dashboard__essentials_nba" class="wp-block-group alignwide">
    <div class="wp-block-group is-vertical is-content-justification-left is-layout-flex wp-container-core-group-is-layout-2 wp-block-group-is-layout-flex">
        <h2 class="wp-block-heading">%s</h2>
        <p>%s</p>
    </div>
    <div class="wp-block-columns is-layout-flex wp-container-core-columns-is-layout-1 wp-block-columns-is-layout-flex">
        %s
    </div>
</div>';

$header = \esc_html__('Next best actions ⚡', 'ionos-essentials');
$description = \esc_html__('Description of this block', 'ionos-essentials');

$body = '';
foreach ($actions as $action) {
  if (!$action->active) {
    continue;
  }

  $target = strpos(\esc_url($action->link), home_url()) === false ? '_blank' : '_top';
  $body .= '
    <div class="wp-block-column is-style-default has-border-color is-layout-flow wp-block-column-is-layout-flow">
        <h2 class="wp-block-heading">' . \esc_html($action->title, 'ionos-essentials') . '</h2>
        <p>' . \esc_html($action->description, 'ionos-essentials') . '</p>
        <div class="wp-block-buttons is-layout-flex wp-block-buttons-is-layout-flex">
            <div class="wp-block-button">
                <a href="' . \esc_url($action->link) . '" class="wp-block-button__link wp-element-button" target="' . $target . '">' . \esc_html("Primary button", 'ionos-essentials') . '</a>
            </div>
            <div class="wp-block-button is-style-outline is-style-outline--1">
                <a id="' . $action->id . '" class="wp-block-button__link wp-element-button dismiss-nba" target="_top">' . \esc_html("Dismiss", 'ionos-essentials') . '</a>
            </div>
        </div>
    </div>';
}

if (empty($body)) {
  return;
}

\printf($template, $header, $description, $body);
