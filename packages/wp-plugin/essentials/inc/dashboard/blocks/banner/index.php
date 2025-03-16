<?php

namespace ionos_wordpress\essentials\dashboard\blocks\banner;

use const ionos_wordpress\essentials\PLUGIN_DIR;

\add_action('init', function () {
  \register_block_type(
    PLUGIN_DIR . '/build/dashboard/blocks/banner',
    [
      'render_callback' => 'ionos_wordpress\essentials\dashboard\blocks\banner\render_callback',
    ]
  );
});

function render_callback()
{
  $template = '
  <div id="ionos-dashboard__essentials_banner" class="wp-block-group is-content-justification-space-between is-nowrap is-layout-flex wp-container-core-group-is-layout-2 wp-block-group-is-layout-flex">
    <div class="wp-block-group is-nowrap is-layout-flex wp-container-core-group-is-layout-1 wp-block-group-is-layout-flex">
    <figure class="wp-block-image size-large is-resized">
        <img src="http://localhost:8888/wp-content/plugins/essentials/inc/dashboard/data/tenant-logos/%s" alt="" style="width:237px;height:auto">
    </figure>
    </div>
    <div class="wp-block-buttons has-custom-font-size has-large-font-size is-horizontal is-content-justification-right is-layout-flex wp-container-core-buttons-is-layout-1 wp-block-buttons-is-layout-flex">
      %s
      <div class="wp-block-button has-custom-width wp-block-button__width-75">
        <a class="wp-block-button__link has-text-align-center wp-element-button">
          <img class="wp-image-308" style="width: 18px;" src="http://localhost:8888/wp-content/uploads/2025/03/icons8-wordpress-48.png" alt="">
          View Site
        </a>
      </div>
      <div class="wp-block-button has-custom-width wp-block-button__width-75">
        <a class="wp-block-button__link has-text-align-center wp-element-button">
          <img class="wp-image-308" style="width: 18px;" src="http://localhost:8888/wp-content/uploads/2025/03/icons8-wordpress-48.png" alt="">
          Start AI Setup
        </a>
      </div>
    </div>
  </div>';

  $re_launch = canRunLaunchAgain() ? '
    <div class="wp-block-button has-custom-width wp-block-button__width-75">
      <a href="' . \admin_url( "admin.php?page=extendify-launch" ) . '" class="wp-block-button__link has-text-align-center wp-element-button">
        <img class="wp-image-308" style="width: 18px;" src="http://localhost:8888/wp-content/uploads/2025/03/icons8-wordpress-48.png" alt="">
        Launch Again
      </a>
    </div>' : '';

  return \sprintf($template, 'ionos.svg', $re_launch);
}

function canRunLaunchAgain()
{
  if (\get_option('stylesheet') !== 'extendable') {
    return false;
  }

  $launchCompleted = \get_option('extendify_onboarding_completed', false);
  if (!$launchCompleted) {
    return false;
  }

  try {
    $datetime1 = new \DateTime($launchCompleted);
    $interval = $datetime1->diff(new \DateTime());
    return $interval->format('%d') <= 2;
  } catch (\Exception $exception) {
    return false;
  }
}
