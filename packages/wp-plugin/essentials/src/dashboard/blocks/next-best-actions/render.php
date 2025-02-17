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
    callback: function($action) {
      $action->__set('completed', true);
      wp_safe_redirect(admin_url('plugins.php'));
      exit;
    },
  ),
  new NBA(
    id: 'checkThemesPage',
    title: 'Check Themes Page',
    description: 'Check the themes page for updates and security issues.',
    image: 'https://raw.githubusercontent.com/codeformm/mitaka-kichijoji-wapuu/main/mitaka-kichijoji-wapuu.png',
    callback: function($action) {
      $action->__set('completed', true);
      wp_safe_redirect(admin_url('themes.php'));
      exit;
    },
  ),
);

echo '<ul class="wp-block-list">';
foreach ($actions as $action) {
  printf(
    '<li><a href="#" target="_top">%s</a></li>',
    \esc_html($action->__get('title')) . ' -> ' . ($action->__get('completed') ? 'Completed' : 'Not completed')
  );
}
echo '</ul>';

//debug callback on dashboard
//echo '<pre>';
//$actions[0]->__set('completed', true);
//wp_die();
