<?php

namespace ionos\essentials\dashboard\blocks\next_best_actions;

defined('ABSPATH') || exit();

function create_buttons($action){
  $target = false === strpos(\esc_url($action->link), home_url()) ? '_blank' : '_top';
  if ('#' === $action->link) {
    $target = '';
  }
 $buttons = sprintf(
  '<a data-nba-id="%s" href="%s" class="button button--secondary" target="%s" data-dismiss-on-click="%s">%s</a>',
  $action->id,
  \esc_url($action->link),
  $target,
    $action->dismiss_on_click ? 'true' : 'false',
    \esc_html($action->anchor)
  );

  // Overwrite cta button for GML installation
  if ('woocommerce-gml' === $action->id) {
    $buttons = '<a id="ionos_essentials_install_gml" class="button button--secondary">' . $action->anchor . '</a>';
  }

  $buttons .= '<a data-nba-id="' . $action->id . '" class="ghost-button ionos-dismiss-nba">' . \esc_html__(
    'Dismiss',
    'ionos-essentials'
  ) . '</a>';

  return $buttons;
}
