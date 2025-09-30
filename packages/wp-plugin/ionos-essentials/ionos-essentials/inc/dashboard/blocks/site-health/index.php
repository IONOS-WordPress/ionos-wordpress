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
              src="<?php echo \esc_url(\get_option('siteurl', '')); ?>/?hidetoolbar=1"
              style="
                width: 1440px;
                height: 900px;
                transform: scale(0.166); /* 240 / 1440 = 0.166 */
                transform-origin: top left;
                border: none;
                pointer-events: none;
              "
              scrolling="no"
            ></iframe>
          </div>
          <div class="ionos-site-health-overview__info">
            <?php if (\ionos\essentials\maintenance_mode\is_maintenance_mode()) { ?>
              <span class="badge badge--warning-solid ionos-maintenance-only" style="width: 180px; margin-bottom: 10px;"><?php \esc_html_e('Maintenance page active', 'ionos-essentials'); ?></span>
            <?php } ?>
            <div class="ionos-site-health-overview__info-homeurl">
              <?php if (\is_ssl()) { ?>
                <i class="exos-icon exos-icon-nav-lock-close-16"></i>
              <?php } else { ?>
                <i class="exos-icon exos-icon-nav-lock-16" style="color: #c90a00;"></i>
              <?php } ?>
              <h2 class="headline headline--sub"><?php echo \esc_url(parse_url(\get_option('siteurl', ''), PHP_URL_HOST)); ?></h2>
            </div>
            <div class="ionos-site-health-overview__info-items">
              <a href="<?php echo \admin_url('site-health.php'); ?>" class="ionos-site-health-overview__info-item site-health-status" style="color: inherit; text-decoration: none;">
                <p><?php \esc_html_e('Site health', 'ionos-essentials')?></p>
                <strong id="site-health-status-text">
                  <div class="site-health-status-circle">
                    <svg aria-hidden="true" focusable="false" width="100%" height="100%" viewBox="0 0 200 200" version="1.1" xmlns="http://www.w3.org/2000/svg">
                      <circle r="90" cx="100" cy="100" fill="transparent" stroke-dasharray="565.48" stroke-dashoffset="0"></circle>
                      <circle id="bar" r="90" cx="100" cy="100" fill="transparent" stroke-dasharray="565.48" stroke-dashoffset="0"></circle>
                    </svg>
                  </div>
                  <span id="site-health-status-message" class="site-health-color"><?php echo \esc_html_e('Results are still loading&hellip;'); ?></span>
                </strong>
              </a>
              <div class="ionos-site-health-overview__info-item">
                <h3 class="ionos-site-health-overview__info-item-title"><?php \esc_html_e('WordPress version', 'ionos-essentials')?></h3>
                <h4 class="headline headline--sub"><?php echo \esc_attr(\get_bloginfo('version')); ?></h4>
              </div>
              <div class="ionos-site-health-overview__info-item">
                <h3 class="ionos-site-health-overview__info-item-title"><?php \esc_html_e('PHP version', 'ionos-essentials')?></h3>
                <h4 class="headline headline--sub"><?php echo PHP_VERSION; ?></h4>
              </div>
            </div>
          </div>
        </div>
        <div class="ionos-site-health-vulnerability">
          <?php \ionos\essentials\dashboard\blocks\vulnerability\render_callback()?>
        </div>
      </section>
    </div>
  </div>
<?php
}
