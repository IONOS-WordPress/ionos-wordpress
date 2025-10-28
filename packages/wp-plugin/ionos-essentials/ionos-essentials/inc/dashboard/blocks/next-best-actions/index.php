<?php

namespace ionos\essentials\dashboard\blocks\next_best_actions;

defined('ABSPATH') || exit();

use const ionos\essentials\PLUGIN_DIR;

const OPTION_IONOS_ESSENTIALS_NBA_ACTIONS_SHOWN   = 'ionos_essentials_nba_actions_shown';
const OPTION_IONOS_ESSENTIALS_NBA_SETUP_COMPLETED = 'ionos_essentials_nba_setup_completed';

require_once PLUGIN_DIR . '/ionos-essentials/inc/dashboard/blocks/next-best-actions/views/main.php';
require_once PLUGIN_DIR . '/ionos-essentials/inc/dashboard/blocks/next-best-actions/views/single.php';
require_once PLUGIN_DIR . '/ionos-essentials/inc/dashboard/blocks/next-best-actions/views/single-accordion-action.php';
require_once PLUGIN_DIR . '/ionos-essentials/inc/dashboard/blocks/next-best-actions/views/buttons.php';
require_once PLUGIN_DIR . '/ionos-essentials/inc/dashboard/blocks/next-best-actions/views/setup-header.php';
require_once PLUGIN_DIR . '/ionos-essentials/inc/dashboard/blocks/next-best-actions/views/setup-footer.php';
require_once PLUGIN_DIR . '/ionos-essentials/inc/dashboard/blocks/next-best-actions/views/setup.php';
require_once PLUGIN_DIR . '/ionos-essentials/inc/dashboard/blocks/next-best-actions/views/after-setup.php';
require_once PLUGIN_DIR . '/ionos-essentials/inc/dashboard/blocks/next-best-actions/views/all-done.php';
require_once PLUGIN_DIR . '/ionos-essentials/inc/dashboard/blocks/next-best-actions/views/setup-complete.php';

function render(): void
{
  require_once PLUGIN_DIR . '/ionos-essentials/inc/dashboard/blocks/next-best-actions/class-nba.php';

  $args = [];

  $args['show_setup_actions'] = false;

  // Get all actions depending on the setup status
  // If setup is not completed, show setup actions and always actions
  // If setup is completed, show after-setup actions and always actions
  if (! \get_option(OPTION_IONOS_ESSENTIALS_NBA_SETUP_COMPLETED, false)) {
    $args['category_to_show'] = (\get_option('extendify_onboarding_completed', false) ? 'setup-ai' : 'setup-noai');
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
    all_done_view();
    return;
  }

  \update_option(
    OPTION_IONOS_ESSENTIALS_NBA_ACTIONS_SHOWN,
    array_merge(array_keys($args['actions']), array_keys($args['always_actions'] ?? []))
  );

  main_view($args);
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
