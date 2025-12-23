<?php

namespace ionos\essentials\dashboard\blocks\site_health;

use const ionos\essentials\PLUGIN_DIR;

require_once PLUGIN_DIR . '/ionos-essentials/inc/dashboard/blocks/vulnerability/index.php';

defined('ABSPATH') || exit();

function render_callback(): void
{
  $iframe_src = \esc_url(\get_option('siteurl', '')) . '/?hidetoolbar=1';

  $maintenance_label = \esc_html__('Maintenance page active', 'ionos-essentials');

  $ssl_title  = \esc_attr__('Current SSL-Status', 'ionos-essentials');
  $ssl_status = \esc_attr__('Secure', 'ionos-essentials');
  $ssl_icon   = 'exos-icon exos-icon-nav-lock-close-16';
  $ssl_style  = '';
  if (! \is_ssl()) {
    $ssl_status = \esc_attr__('Insecure', 'ionos-essentials');
    $ssl_icon   = 'exos-icon exos-icon-nav-lock-16';
    $ssl_style  = 'color: #c90a00;';
  }

  $home_url = \esc_url(parse_url(\get_option('siteurl', ''), PHP_URL_HOST));

  $site_health_url   = \esc_attr(\admin_url('site-health.php'));
  $site_health_label = \esc_html__('Site health', 'ionos-essentials');
  $loading_message   = \esc_html__('Results are still loading&hellip;', 'ionos-essentials');

  $wp_version_label = \esc_html__('WordPress version', 'ionos-essentials');
  $wp_version       = \esc_attr(\get_bloginfo('version'));

  $php_version_label = \esc_html__('PHP version', 'ionos-essentials');
  $php_version       = PHP_VERSION;

  ob_start();
  \ionos\essentials\dashboard\blocks\vulnerability\render_callback();
  $vulnerability_content = ob_get_clean();

  printf(<<<EOF
  <div class="card">
    <div class="card__content">
      <section class="card__section ionos-site-health">
        <div class="ionos-site-health-overview">
          <div class="ionos-site-health-overview__iframe" style="width: 240px; height: 150px; overflow: hidden;">
            <iframe
              src="{$iframe_src}"
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
            <span class="badge badge--warning-solid ionos-maintenance-only" style="width: fit-content; margin-bottom: 10px;">{$maintenance_label}</span>
            <div class="ionos-site-health-overview__info-homeurl">
              <i class="{$ssl_icon}" style="{$ssl_style}" title="{$ssl_title}: {$ssl_status}"></i>
              <h2 class="headline headline--sub">{$home_url}</h2>
            </div>
            <div class="ionos-site-health-overview__info-items">
              <a href="{$site_health_url}" class="ionos-site-health-overview__info-item site-health-status" style="color: inherit; text-decoration: none;">
                <p>{$site_health_label}</p>
                <strong id="site-health-status-text">
                  <div class="site-health-status-circle">
                    <svg aria-hidden="true" focusable="false" width="100%" height="100%" viewBox="0 0 200 200" version="1.1" xmlns="http://www.w3.org/2000/svg">
                      <circle r="90" cx="100" cy="100" fill="transparent" stroke-dasharray="565.48" stroke-dashoffset="0"></circle>
                      <circle id="bar" r="90" cx="100" cy="100" fill="transparent" stroke-dasharray="565.48" stroke-dashoffset="0"></circle>
                    </svg>
                  </div>
                  <span id="site-health-status-message" class="site-health-color">{$loading_message}</span>
                </strong>
              </a>
              <div class="ionos-site-health-overview__info-item">
                <h3 class="ionos-site-health-overview__info-item-title">{$wp_version_label}</h3>
                <h4 class="headline headline--sub">{$wp_version}</h4>
              </div>
              <div class="ionos-site-health-overview__info-item">
                <h3 class="ionos-site-health-overview__info-item-title">{$php_version_label}</h3>
                <h4 class="headline headline--sub">{$php_version}</h4>
              </div>
            </div>
          </div>
        </div>
        <div class="ionos-site-health-vulnerability">
          {$vulnerability_content}
        </div>
      </section>
    </div>
  </div>
  EOF);
}
