<?php

namespace ionos\essentials\dashboard\blocks\banner;

defined('ABSPATH') || exit();

use ionos\essentials\Tenant;

const BUTTON_TEMPLATE = '<a href="%s" class="button %s" title="%s">%s</a>';
function render_callback(): void
{
  $button_list = [];

  $view_site = [
    [
      'link'           => \home_url(),
      'text'           => \__('View Site', 'ionos-essentials'),
      'css-class'      => 'button--primary',
    ],
  ];

  $button_list = array_merge($button_list, get_ai_button());
  $button_list = apply_filters('ionos_dashboard_banner__register_button', $button_list);
  $button_list = array_merge($button_list, $view_site);

  $buttons = implode('', array_map(fn (array $button): string => sprintf(
    BUTTON_TEMPLATE,
    \esc_url($button['link'] ?? '#'),
    $button['css-class'] ?? 'button--secondary',
    $button['title']     ?? '',
    \esc_html($button['text'] ?? '')
  ), $button_list));

  $tenant_slug = Tenant::get_slug();
  $tenant_logo = \plugins_url('data/tenant-logos/' . $tenant_slug . '.svg', dirname(__DIR__));

  ?>
<div class="card">
  <div class="card__content">
    <section class="card__section">
      <div class="banner_wrapper">
        <div class="content">
          <img class="banner-logo banner-logo--<?php echo \esc_attr($tenant_slug); ?>"
          src="<?php echo \esc_attr($tenant_logo); ?>"
          alt="<?php echo \esc_attr(Tenant::get_label()); ?> Logo"
          >
          <ul class="page-tabbar__items" role="menu">
            <li>
              <a href="#" class="page-tabbar__link page-tabbar__link--active" data-tab="overview">
                <div class="page-tabbar__label"><?php \esc_html_e('Overview', 'ionos-essentials'); ?></div>
              </a>
            </li>
            <li>
              <a href="#" class="page-tabbar__link" role="button" tabindex="0" data-tab="tools">
                <div class="page-tabbar__label may-have-issue-dot"><?php \esc_html_e('Tools & Security', 'ionos-essentials'); ?></div>
              </a>
            </li>
          </ul>
        </div>
        <div class="ionos_banner_buttons">
          <?php echo \wp_kses($buttons, 'post'); ?>
        </div>
      </div>
    </section>
  </div>
</div>


  <?php
}

function get_ai_button(): array
{
  if ('extendable' !== \get_option('stylesheet') || ! \is_plugin_active('extendify/extendify.php')) {
    return [];
  }

  $launch_completed = \get_option('extendify_onboarding_completed', false);
  if (false === $launch_completed) {
    return [
      [
        'link'           => \admin_url('admin.php?page=extendify-launch'),
        'text'           => \__('Start AI Sitebuilder', 'ionos-essentials'),
        'css-class'      => 'button--promoting',
        'target'         => '_blank',
      ], ];
  }

  if (strtotime($launch_completed) > time() - (3 * 24 * 60 * 60)) {
    return [
      [
        'link'           => \admin_url('admin.php?page=extendify-launch'),
        'text'           => \__('Rebuild Website', 'ionos-essentials'),
        'css-class'      => 'button--promoting',
        'title'          => \__('It is possible to rebuild your AI-created website within 72 hours', 'ionos-essentials'),
        'target'         => '_blank',
      ], ];
  }

  return [];
}
