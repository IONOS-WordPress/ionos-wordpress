<?php

namespace ionos\essentials\dashboard\blocks\site_health;

use const ionos\essentials\PLUGIN_DIR;

require_once PLUGIN_DIR . '/ionos-essentials/inc/dashboard/blocks/vulnerability/index.php';

defined('ABSPATH') || exit();

function render_callback(): void
{
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
              <h2 class="headline headline--sub"><?php echo parse_url(\get_option('siteurl', ''), PHP_URL_HOST); ?></h2>
            </div>
            <div class="ionos-site-health-overview__info-items">
            <div class="ionos-site-health-overview__info-item site-health-status">
              <p>Site health:</p>
              <strong id="site-health-status-text" >
                <span class="site-health-status-circle">
                  <svg aria-hidden="true" focusable="false" width="12" height="12" viewBox="0 0 200 200" version="1.1" xmlns="http://www.w3.org/2000/svg">
                    <circle r="90" cx="100" cy="100" fill="transparent" stroke-dasharray="565.48" stroke-dashoffset="0"></circle>
                    <circle id="bar" r="90" cx="100" cy="100" fill="transparent" stroke-dasharray="565.48" stroke-dashoffset="0"></circle>
                </svg>

                </span>
                <span id="site-health-status-message"><?php echo esc_html_e('Results are still loading&hellip;'); ?></span>
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
