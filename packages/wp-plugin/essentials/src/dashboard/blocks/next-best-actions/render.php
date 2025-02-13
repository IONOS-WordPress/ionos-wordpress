<?php

// phpcs:disable WordPress.WP.GlobalVariablesOverride.Prohibited

namespace ionos_wordpress\essentials\dashboard\blocks\next_best_actions;

use ionos_wordpress\essentials\dashboard\blocks\next_best_actions\model\NBA;

$model_path = __DIR__ . '/model/nba.php';

if (! file_exists($model_path)) {
  return;
}

require_once $model_path;
printf('<h3>%s</h3>', \esc_html__('Next best actions ⚡', 'ionos-essentials'));

$actions = array(
  new NBA(
    id: 'checkPluginsPage',
    title: 'Check Plugins Page',
    description: 'Check the plugins page for updates and security issues.',
    image: 'https://raw.githubusercontent.com/codeformm/mitaka-kichijoji-wapuu/main/mitaka-kichijoji-wapuu.png',
    link: admin_url('plugins.php'),
    callback: function() {
      echo 'this is a test!';
      return true;
    }
  )
);

echo '<ul class="wp-block-list">';
foreach ($actions as $action) {
  printf(
    '<li><a href="%s">%s</a></li>',
    \esc_url($action->get_link()),
    \esc_html($action->get_title())
  );
}
echo '</ul>';

// debug callback on dashboard
//echo '<pre>';
//$actions[0]->get_callback();
//wp_die();
