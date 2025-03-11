<?php

namespace ionos_wordpress\essentials\dashboard\blocks\next_best_actions;
use const ionos_wordpress\essentials\PLUGIN_DIR;
use ionos_wordpress\essentials\dashboard\blocks\next_best_actions\NBA;
use ionos_wordpress\essentials\dashboard\blocks\next_best_actions\ActionStatus;

\add_action('init', function () {
  \register_block_type(
    PLUGIN_DIR . '/build/dashboard/blocks/next-best-actions',
    [
      'render_callback' => 'ionos_wordpress\essentials\dashboard\blocks\next_best_actions\render_callback'
    ]
  );
});

function render_callback () {
  require_once __DIR__ . '/class-nba.php';
  $actions = NBA::getActions();
  if (empty($actions)) {
    return;
  }

  $template = '
  <div id="ionos-dashboard__essentials_nba" class="wp-block-group alignwide">
      <div class="wp-block-group" style="margin-top:0px;margin-bottom:15px;padding-top:0;padding-right:0;padding-bottom:0;padding-left:0">
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
      <div class="wp-block-column is-style-default has-background" style="border-radius:24px;background-color:#f4f7fa">
          <h2 class="wp-block-heading">' . \esc_html($action->title, 'ionos-essentials') . '</h2>
          <p>' . \esc_html($action->description, 'ionos-essentials') . '</p>
          <div class="wp-block-buttons is-layout-flex wp-block-buttons-is-layout-flex">
              <div class="wp-block-button">
                  <a href="' . \esc_url($action->link) . '" class="wp-block-button__link wp-element-button" target="' . $target . '">' . \esc_html("Primary button", 'ionos-essentials') . '</a>
              </div>
              <div class="wp-block-button is-style-outline is-style-outline--1">
                  <a data-nba-id="' . $action->id . '" class="wp-block-button__link wp-element-button dismiss-nba" target="_top">' . \esc_html("Dismiss", 'ionos-essentials') . '</a>
              </div>
          </div>
      </div>';
  }

  if (empty($body)) {
    return;
  }

  return \sprintf($template, $header, $description, $body);

};

\add_action('admin_init', function () {
  if (isset($_GET['complete_nba'])) {
    require_once __DIR__ . '/class-nba.php';
    $nba_id = $_GET['complete_nba'];

    $nba = NBA::getNBA($nba_id);
    $nba->setStatus( ActionStatus::completed, true);
  }
});

\add_action('rest_api_init', function () {
  \register_rest_route('ionos/v1', '/dismiss_nba/(?P<id>[a-zA-Z0-9-]+)', [
    'methods' => 'GET',
    'callback' => function ($request) {
      require_once __DIR__ . '/class-nba.php';
			$params = $request->get_params();
			$nba_id = $params['id'];

      $nba = NBA::getNBA($nba_id);
      $res = $nba->setStatus( ActionStatus::dismissed, true);
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

\add_action('post_updated', function ($post_id, $post_after, $post_before) {
  if ($post_before->post_status !== 'publish' || ($post_after->post_status !== 'publish' && $post_after->post_status !== 'draft')) {
    return;
  }

  require_once __DIR__ . '/class-nba.php';
  switch ($post_after->post_type) {
    case 'post':
      $nba = NBA::getNBA('editPost');
      break;
    case 'page':
      $nba = NBA::getNBA('editPage');
      break;
    default:
      return;
  }

  if ($nba) {
    $nba->setStatus( ActionStatus::completed, true);
  }
}, 10, 3);
