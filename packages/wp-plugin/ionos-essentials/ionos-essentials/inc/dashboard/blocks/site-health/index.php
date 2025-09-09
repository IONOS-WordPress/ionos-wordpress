<?php

namespace ionos\essentials\dashboard\blocks\site_health;

use WP_Site_Health;

use const ionos\essentials\PLUGIN_DIR;

require_once PLUGIN_DIR . '/ionos-essentials/inc/dashboard/blocks/vulnerability/index.php';

defined('ABSPATH') || exit();



function render_callback(): void
{


$status = get_option('custom_site_health_status', 'unknown');

switch ($status) {
    case 'good':
        $text  = 'Good';
        $class = 'green';
        break;
    case 'recommended':
        $text  = 'Should be improved';
        $class = 'orange';
        break;
    case 'critical':
        $text  = 'Needs urgent attention';
        $class = 'red';
        break;
    default:
        $text  = 'Unknown';
        $class = 'grey';
}

?>

  <div class="card">
    <div class="card__content">
      <section class="card__section ionos-site-health">
        <div class="ionos-site-health-overview">
          <div class="ionos-site-health-overview__iframe" style="width: 240px; height: 150px; overflow: hidden;">
            <iframe
              src="<?php echo \get_option('siteurl', ''); ?>"
              style="
                width: 1440px;
                height: 900px;
                transform: scale(0.166); /* 240 / 1440 = 0.166 */
                transform-origin: top left;
                border: none;
              "
              scrolling="no"
            ></iframe>
          </div>
          <div class="ionos-site-health-overview__info">
            <div class="ionos-site-health-overview__info-homeurl">
              <i class="exos-icon exos-icon-password-16"></i>
              <h2 class="headline headline--sub"><?php echo parse_url( \get_option('siteurl', ''), PHP_URL_HOST );?></h2>
            </div>
            <div class="ionos-site-health-overview__info-items">
            <div class="ionos-site-health-overview__info-item site-health-status">
              <p>Site health:</p>
              <strong id="site-health-status-text" class="<?php echo esc_attr($class); ?>">
                <span class="site-health-status-circle"></span>
                <?php echo esc_html($text); ?>
              </strong>
            </div>


              <div class="ionos-site-health-overview__info-item">
                <h3 class="ionos-site-health-overview__info-item-title">WordPress version</h3>
                <h4 class="headline headline--sub"><?php echo \get_bloginfo('version'); ?></h4>
              </div>
              <div class="ionos-site-health-overview__info-item">
                <h3 class="ionos-site-health-overview__info-item-title">PHP version</h3>
                <h4 class="headline headline--sub"><?php echo PHP_VERSION; ?></h4>
              </div>
            </div>
          </div>
          </div>
        <div class="ionos-site-health-vulnerability">
          <?php echo \ionos\essentials\dashboard\blocks\vulnerability\render_callback()?>
        </div>
      </section>
    </div>
  </div>

<?php
}
