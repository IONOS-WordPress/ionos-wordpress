<?php

namespace ionos\essentials\dashboard\blocks\next_best_actions;

defined('ABSPATH') || exit();
use const ionos\essentials\PLUGIN_FILE;

function single_accordion_view($action): void
{
  $action_id = \esc_attr($action->id);
  $classes   = $action->expanded ? 'panel__item--expanded always-expanded ' : 'panel__item--closed expandable ';
  $classes .= $action->active ? 'nba-active' : 'nba-inactive';
  $aria_expanded = $action->expanded ? 'true' : 'false';
  $exos_icon     = \esc_attr(plugins_url(
    '/ionos-essentials/inc/dashboard/assets/' . $action->exos_icon . '.svg',
    PLUGIN_FILE
  ));
  $action_title       = \esc_html($action->title);
  $action_description = \esc_html($action->description);
  $buttons            = \wp_kses(create_buttons($action), 'post');
  printf(
    <<<EOF
  <div id="{$action_id}" class="panel__item {$classes}"
    aria-expanded="{$aria_expanded}">
    <header class="panel__item-header">
      <div class="panel__icon">
        <img src="{$exos_icon}" alt="Icon" width="30" height="30">
      </div>
      <div class="panel__headline__container">
        <h3 class="panel__headline">
          {$action_title}
        </h3>
      </div>
    </header>
    <section class="panel__item-section">
      <p class="paragraph">{$action_description}</p>
      <div>{$buttons}</div>
    </section>
  </div>
  EOF
  );
}
