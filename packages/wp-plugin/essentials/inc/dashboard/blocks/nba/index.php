<?php

namespace ionos_wordpress\essentials\dashboard\blocks\next_best_actions;
use const ionos_wordpress\essentials\PLUGIN_DIR;

$model_path = __DIR__ . '/model.php';
if (! file_exists($model_path)) {
  return;
}
require_once $model_path;


\add_action('init', function () {
  \register_block_type(
    PLUGIN_DIR . '/build/dashboard/blocks/next-best-actions',
  );
});

\add_action('admin_init', function () {
  if (isset($_GET['complete_nba'])) {
    $nba_id = $_GET['complete_nba'];
    $nba = getNbaById($nba_id);

    if ($nba) {
      $nba->setStatus( "completed", true);
    }
  }
});

\add_action('rest_api_init', function () {
  \register_rest_route('ionos/v1', '/dismiss_nba/(?P<id>[a-zA-Z0-9-]+)', [
    'methods' => 'GET',
    'callback' => function ($request) {

			$params = $request->get_params();
			$nba_id = $params['id'];

      $nba = getNbaById($nba_id);
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


function getNbaById($id) {
  $nbaList = getNbaAll();

  if (! isset($nbaList[$id])) {
    return null;
  }

  return $nbaList[$id];
}

function getNbaAll() {
  return \apply_filters( 'ionos_dashboard_filter__nba', array() );
}

\add_filter('ionos_dashboard_filter__nba', function ($actions) {
  $elements = array(
    array(
      'id' => 'addPage',
      'title' => \esc_html__('Add a page', 'ionos-essentials'),
      'description' => \esc_html__('Create some content for your website visitor.', 'ionos-essentials'),
      'link' => admin_url('post-new.php?post_type=page'),
      'completed' => wp_count_posts('page')->publish > 2
    ),
    array(
      'id' => 'addPost',
      'title' => \esc_html__('Add a post', 'ionos-essentials'),
      'description' => \esc_html__('Share your thoughts with your audience.', 'ionos-essentials'),
      'link' => admin_url('plugins.php?complete_nba=checkPluginsPage'),
      'completed' => wp_count_posts('post')->publish > 2
    ),
    array(
      'id' => 'editPost',
      'title' => \esc_html__('Edit a post', 'ionos-essentials'),
      'description' => \esc_html__('Update your content to keep it fresh.', 'ionos-essentials'),
      'link' => admin_url('options-general.php?complete_nba=checkSettingsPage'),
      'completed' => false
    ),
    array(
      'id' => 'addSiteDescription',
      'title' => \esc_html__('Add a site description', 'ionos-essentials'),
      'description' => \esc_html__('Tell your visitors what your website is about.', 'ionos-essentials'),
      'link' => admin_url('options-general.php?complete_nba=addSiteDescription'),
      'completed' => get_option('blogdescription') !== '' && __('Just another WordPress site') !== get_option('blogdescription')
    ),
    array(
      'id' => 'uploadALogo',
      'title' => \esc_html__('Upload a logo', 'ionos-essentials'),
      'description' => \esc_html__('Make your website more recognizable.', 'ionos-essentials'),
      'link' => admin_url('options-general.php?complete_nba=uploadALogo'),
      'completed' => \intval(get_option('site_icon', 0)) > 0
    ),
  );

  foreach ($elements as $element) {
    $actions[$element['id']] = new NBA(
      id: $element['id'],
      title: $element['title'],
      description: $element['description'],
      link: $element['link'],
      completed: $element['completed']
    );
  }

  return $actions;
}, 10, 1);
