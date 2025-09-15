<?php

namespace ionos\essentials\dashboard\blocks\next_best_actions;

defined('ABSPATH') || exit();
use const ionos\essentials\PLUGIN_DIR;

require_once PLUGIN_DIR . '/ionos-essentials/inc/dashboard/blocks/next-best-actions/views/main.php';
require_once PLUGIN_DIR . '/ionos-essentials/inc/dashboard/blocks/next-best-actions/views/single.php';
require_once PLUGIN_DIR . '/ionos-essentials/inc/dashboard/blocks/next-best-actions/views/buttons.php';
require_once PLUGIN_DIR . '/ionos-essentials/inc/dashboard/blocks/next-best-actions/views/setup_header.php';
require_once PLUGIN_DIR . '/ionos-essentials/inc/dashboard/blocks/next-best-actions/views/setup_footer.php';
require_once PLUGIN_DIR . '/ionos-essentials/inc/dashboard/blocks/next-best-actions/views/setup.php';
require_once PLUGIN_DIR . '/ionos-essentials/inc/dashboard/blocks/next-best-actions/views/after_setup.php';

function render(): void
{
  require_once PLUGIN_DIR . '/ionos-essentials/inc/dashboard/blocks/next-best-actions/class-nba.php';

  $args = [];

  $args['show_setup_actions'] = false;

  // Get all actions depending on the setup status
  // If setup is not completed, show setup actions and always actions
  // If setup is completed, show after-setup actions and always actions
  if (! get_option('ionos_essentials_nba_setup_completed', false)) {
    $args['category_to_show'] = (\get_option('extendify_onboarding_completed', false) ? 'setup-noai' : 'setup-noai');
    $args['actions']          = array_filter(
      NBA::get_actions(),
      fn (NBA $action) => in_array($args['category_to_show'], $action->categories, true)
    );
    $args['show_setup_actions'] = true;
    $args['always_actions']     = array_filter(
      NBA::get_actions(),
      fn (NBA $action) => in_array('always', $action->categories, true) && $action->active
    );
  } else {
    $args['category_to_show'] = 'after-setup';
    $args['actions']          = array_filter(
      NBA::get_actions(),
      fn (NBA $action) => (in_array('after-setup', $action->categories, true) || in_array(
        'always',
        $action->categories,
        true
      ))  && $action->active
    );
  }

  $args['completed_actions'] = count(array_filter($args['actions'], fn ($action) => $action->active === false));
  $args['total_actions']     = count($args['actions']);

  if (empty($args['actions'])) {
    render_empty_element();
    return;
  }

  main_view($args);
}

function render_empty_element(): void
{
  echo 'all done for now!';
}

\add_action('post_updated', function ($post_id, $post_after, $post_before) {
  if ('publish' !== $post_before->post_status || ('publish' !== $post_after->post_status && 'draft' !== $post_after->post_status)) {
    return;
  }

  require_once __DIR__ . '/class-nba.php';
  switch ($post_after->post_type) {
    case 'post':
      $nba = NBA::get_nba('edit-post');
      break;
    case 'page':
      $nba = NBA::get_nba('edit-page');
      break;
    default:
      return;
  }

  if ($nba) {
    $nba->set_status('completed', true);
  }
}, 10, 3);
