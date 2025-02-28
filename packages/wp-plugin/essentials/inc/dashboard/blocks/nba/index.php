<?php

namespace ionos_wordpress\essentials\dashboard\blocks\next_best_actions;
use const ionos_wordpress\essentials\PLUGIN_DIR;
use ionos_wordpress\essentials\dashboard\blocks\next_best_actions\model\NBA;

\add_action('init', function () {
  \register_block_type(
    PLUGIN_DIR . '/build/dashboard/blocks/next-best-actions',
    [
      'render_callback' => 'ionos_wordpress\essentials\dashboard\blocks\next_best_actions\render_callback'
    ]
  );
});

function render_callback () {
  require_once __DIR__ . '/model.php';
  $actions = NBA::getActions();
  if (empty($actions)) {
    return;
  }


$body = '';
foreach ($actions as $action) {
  $active = $action->active;
  if (! $active) {
    continue;
  }
  $body .= '<div class="wp-block-column is-style-default has-border-color is-layout-flow wp-block-column-is-layout-flow">'
  . sprintf('<h2 class="wp-block-heading">%s</h2>', \esc_html($action->title, 'ionos-essentials'))
  . sprintf('<p>%s</p>', \esc_html($action->description, 'ionos-essentials'))
  . '<div class="wp-block-buttons is-layout-flex wp-block-buttons-is-layout-flex">'
  . sprintf(
    '<div class="wp-block-button"><a href="%s" class="wp-block-button__link wp-element-button" target="_top">%s</a></div>',
    \esc_url($action->link),
    \esc_html("Primary button", 'ionos-essentials'),
  )
  . '<div class="wp-block-button is-style-outline is-style-outline--1">'
  . sprintf(
    '<div class="wp-block-button"><a id="%s" class="wp-block-button__link wp-element-button dismiss-nba" target="_top">%s</a></div>',
    $action->id,
    \esc_html("Dismiss", 'ionos-essentials')
  )
  . '</div></div></div>';
}

  if (empty($body)) {
    return;
  }

return '<div id="ionos-dashboard__essentials_nba" class="wp-block-group alignwide">'
. '<div class="wp-block-group is-vertical is-content-justification-left is-layout-flex wp-container-core-group-is-layout-2 wp-block-group-is-layout-flex">'
. sprintf('<h2 class="wp-block-heading">%s</h2>', \esc_html__('Next best actions ⚡', 'ionos-essentials'))
. sprintf('<p>%s</p>', \esc_html__('Description of this block', 'ionos-essentials'))
. '</div>'
. '<div class="wp-block-columns is-layout-flex wp-container-core-columns-is-layout-1 wp-block-columns-is-layout-flex">'
. $body
. '</div></div>';

};

\add_action('admin_init', function () {
  if (isset($_GET['complete_nba'])) {
    require_once __DIR__ . '/model.php';
    $nba_id = $_GET['complete_nba'];

    $nba = \ionos_wordpress\essentials\dashboard\blocks\next_best_actions\model\NBA::getNBA($nba_id);
    $nba->setStatus( "completed", true);
  }
});

\add_action('rest_api_init', function () {
  \register_rest_route('ionos/v1', '/dismiss_nba/(?P<id>[a-zA-Z0-9-]+)', [
    'methods' => 'GET',
    'callback' => function ($request) {
      require_once __DIR__ . '/model.php';
			$params = $request->get_params();
			$nba_id = $params['id'];

      $nba = \ionos_wordpress\essentials\dashboard\blocks\next_best_actions\model\NBA::getNBA($nba_id);
      $res = $nba->setStatus( "dismissed", true);
      if ($res) {
        return new \WP_REST_Response(['status' => 'success', 'res' => $res], 200);
      }
      return new \WP_REST_Response(['status' => 'error'], 500);
    },
    'permission_callback' => function () {
      return true || \current_user_can('manage_options');
    },
  ]);
});


