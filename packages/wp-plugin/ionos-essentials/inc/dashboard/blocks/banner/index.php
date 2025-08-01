<?php

namespace ionos\essentials\dashboard\blocks\banner;

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

  $tenant_name = \get_option('ionos_group_brand_menu', 'IONOS');
  $tenant_logo = \plugins_url(
    'data/tenant-logos/' . \get_option('ionos_group_brand', 'ionos') . '.svg',
    dirname(__DIR__)
  );

  ?>
<div class="card">
    <div class="card__content">
      <section class="card__section">
        <div class="grid grid--vertical-align-center">
          <div class="grid-col grid-col--6  grid-col--medium-12 grid-col--small-12" style="display: flex; align-items: center; justify-content: center;">
            <img class=""
            src="<?php echo \esc_attr($tenant_logo); ?>"
            alt="<?php echo \esc_attr($tenant_name); ?> Logo"
            style="width: 150px; padding: 50px;"
          >

            <div class="page-tabbar__block">
              <ul class="page-tabbar__items" role="menu">
                <li class="page-tabbar__item" role="presentation">
                  <a href="#" class="page-tabbar__link page-tabbar__link--active" data-tab="overview">
                    <div class="page-tabbar__label"><?php \esc_html_e('Overview', 'ionos-essentials'); ?></div>
                  </a>
                </li>
                <li class="page-tabbar__item" role="presentation">
                  <a href="#" class="page-tabbar__link" role="button" tabindex="0" data-tab="tools">
                    <div class="page-tabbar__label may-have-issue-dot"><?php \esc_html_e('Tools', 'ionos-essentials'); ?></div>
                  </a>
                </li>
              </ul>
            </div>


          </div>

          <div class="grid-col grid-col--6 grid-col--medium-12 grid-col--small-12 ionos_banner_buttons">
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
