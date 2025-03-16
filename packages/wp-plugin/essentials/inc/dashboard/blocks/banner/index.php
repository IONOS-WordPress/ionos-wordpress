<?php

namespace ionos_wordpress\essentials\dashboard\blocks\banner;

use const ionos_wordpress\essentials\PLUGIN_DIR;

const LOGO_PATH      = '/essentials/inc/dashboard/data/tenant-logos/';
const DEFAULT_TENANT = 'ionos';
const IMAGE_WIDTH    = 237;
const ICON_SIZE      = 18;

\add_action('init', function () {
  \register_block_type(
    PLUGIN_DIR . '/build/dashboard/blocks/banner',
    [
      'render_callback' => __NAMESPACE__ . '\\render_callback',
    ]
  );
});

/**
 * Render callback for the banner block.
 *
 * @return string
 */
function render_callback()
{
  $template = <<<HTML
    <div id="ionos-dashboard__essentials_banner" class="wp-block-group is-content-justification-space-between is-nowrap is-layout-flex wp-container-core-group-is-layout-2 wp-block-group-is-layout-flex">
        <div class="wp-block-group is-nowrap is-layout-flex wp-container-core-group-is-layout-1 wp-block-group-is-layout-flex">
            <figure class="wp-block-image size-large is-resized">
                <img src="%s" alt="" style="width:%dpx;height:auto">
            </figure>
        </div>
        <div class="wp-block-buttons has-custom-font-size has-large-font-size is-horizontal is-content-justification-right is-layout-flex wp-container-core-buttons-is-layout-1 wp-block-buttons-is-layout-flex">
            %s
            <div class="wp-block-button has-custom-width wp-block-button__width-75">
                <a class="wp-block-button__link has-text-align-center wp-element-button">
                    <img class="wp-image-308" style="width: %dpx;" src="http://localhost:8888/wp-content/uploads/2025/03/icons8-wordpress-48.png" alt="">
                    View Site
                </a>
            </div>
            <div class="wp-block-button has-custom-width wp-block-button__width-75">
                <a class="wp-block-button__link has-text-align-center wp-element-button">
                    <img class="wp-image-308" style="width: %dpx;" src="http://localhost:8888/wp-content/uploads/2025/03/icons8-wordpress-48.png" alt="">
                    Start AI Setup
                </a>
            </div>
        </div>
    </div>
    HTML;

  $re_launch = canRunLaunchAgain() ? sprintf(<<<HTML
        <div class="wp-block-button has-custom-width wp-block-button__width-75">
            <a href="%s" class="wp-block-button__link has-text-align-center wp-element-button">
                <img class="wp-image-308" style="width: %dpx;" src="http://localhost:8888/wp-content/uploads/2025/03/icons8-wordpress-48.png" alt="">
                Launch Again
            </a>
        </div>
        HTML
    , \admin_url('admin.php?page=extendify-launch'), ICON_SIZE) : '';

  return sprintf($template, getLogo(), IMAGE_WIDTH, $re_launch, ICON_SIZE, ICON_SIZE);
}

/**
 * Get the logo URL based on the tenant.
 *
 * @return string
 */
function getLogo()
{
  $tenant = \get_option('ionos_group_brand', DEFAULT_TENANT);
  return \plugin_dir_url(PLUGIN_DIR) . LOGO_PATH . $tenant . '.svg';
}

/**
 * Check if the launch can be run again.
 *
 * @return bool
 */
function canRunLaunchAgain()
{
  if ('extendable' !== \get_option('stylesheet')) {
    return false;
  }

  $launchCompleted = \get_option('extendify_onboarding_completed', false);
  if (! $launchCompleted) {
    return false;
  }

  try {
    $datetime1 = new \DateTime($launchCompleted);
    $interval  = $datetime1->diff(new \DateTime());
    return 2 >= $interval->days;
  } catch (\Exception $exception) {
    return false;
  }
}
