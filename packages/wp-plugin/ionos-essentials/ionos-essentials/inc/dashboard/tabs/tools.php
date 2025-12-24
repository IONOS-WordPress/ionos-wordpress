<?php

namespace ionos\essentials\dashboard;

defined('ABSPATH') || exit();

use ionos\essentials\Tenant;
use function ionos\essentials\is_stretch;
use const ionos\essentials\security\IONOS_SECURITY_FEATURE_OPTION;
use const ionos\essentials\security\IONOS_SECURITY_FEATURE_OPTION_CREDENTIALS_CHECKING;
use const ionos\essentials\security\IONOS_SECURITY_FEATURE_OPTION_DEFAULT;
use const ionos\essentials\security\IONOS_SECURITY_FEATURE_OPTION_MAIL_NOTIFY;
use const ionos\essentials\security\IONOS_SECURITY_FEATURE_OPTION_PEL;
use const ionos\essentials\security\IONOS_SECURITY_FEATURE_OPTION_XMLRPC;

function render_section(array $args): void
{
  $title       = \esc_html($args['title']);
  $description = \wp_kses($args['description'], 'post');
  $id          = \esc_attr($args['id']);
  $option      = \esc_attr(IONOS_SECURITY_FEATURE_OPTION);
  $checked     = $args['checked'];

  printf(
    <<<EOF
    <section class="sheet__section">
      <div class="grid">
        <div class="grid-col grid-col--8 grid-col--small-12">
          <h2 class="headline headline--sub">{$title}</h2>
          <p class="paragraph paragraph--neutral">
            {$description}
          </p>
        </div>
        <div class="grid-col grid-col--4 grid-col--small-12 grid-col--align-right">
          <span class="input-switch">
            <input
              id="{$id}"
              data-option="{$option}"
              data-description="{$title}"
              type="checkbox"
              {$checked}
            >
            <label>
              <span class="input-switch__on"></span>
              <span class="input-switch__toggle"></span>
              <span class="input-switch__off"></span>
            </label>
          </span>
        </div>
      </div>
    </section>
    EOF
  );
}

function get_settings_value($key)
{
  $options = \get_option(IONOS_SECURITY_FEATURE_OPTION, IONOS_SECURITY_FEATURE_OPTION_DEFAULT);

  return $options[$key] ?? false;
}

function render_tools_tab(): void
{
  $tools_headline          = \esc_html__('Tools', 'ionos-essentials');
  $maintenance_headline    = \esc_html__('Maintenance page', 'ionos-essentials');
  $active_badge            = \esc_html__('Active', 'ionos-essentials');
  $maintenance_description = \esc_html__(
    'Temporarily block public access to your site with a maintenance page.',
    'ionos-essentials'
  );
  $preview_url                  = \esc_url(\plugin_dir_url(__FILE__) . '../../maintenance_mode/assets/maintenance.html');
  $preview_link_text            = \esc_html__('Preview maintenance page', 'ionos-essentials');
  $maintenance_mode_description = \esc_attr__('Maintenance mode', 'ionos-essentials');
  $maintenance_checked          = \ionos\essentials\maintenance_mode\is_maintenance_mode() ? 'checked' : '';

  $security_headline = \esc_html__('Website security', 'ionos-essentials');

  printf(
    <<<EOF
    <div id="tools" class="page-section ionos-tab">
      <div class="grid">
        <div class="grid-col grid-col--12">
          <h3 class="headline headline--sub">{$tools_headline}</h3>

          <div class="sheet">
            <section class="sheet__section">
              <div class="grid">
                <div class="grid-col grid-col--8 grid-col--small-12">
                  <h2 class="headline headline--sub">
                    {$maintenance_headline}
                    <span class="badge badge--warning-solid ionos-maintenance-only">{$active_badge}</span>
                  </h2>
                  <p class="paragraph paragraph--neutral">
                    {$maintenance_description}
                  </p>
                  <a href="{$preview_url}" target="_blank" class="link link--action">{$preview_link_text}</a>
                </div>
                <div class="grid-col grid-col--4 grid-col--small-12 grid-col--align-right">
                  <span class="input-switch">
                    <input id="ionos_essentials_maintenance_mode" type="checkbox" data-description="{$maintenance_mode_description}" {$maintenance_checked}>
                    <label>
                      <span class="input-switch__on"></span>
                      <span class="input-switch__toggle"></span>
                      <span class="input-switch__off"></span>
                    </label>
                  </span>
                </div>
              </div>
            </section>
          </div>

          <h3 class="headline headline--sub may-have-issue-dot">{$security_headline}</h3>
    EOF
  );

  if (empty(\ionos\essentials\wpscan\get_wpscan()->get_error())) {
    $vulnerabilities_label = \esc_html__('Vulnerabilities detected are being emailed to', 'ionos-essentials');
    $admin_email           = \get_option('admin_email');
    $options_url           = \admin_url('options-general.php');
    $change_email_link     = \esc_html__('Change email address', 'ionos-essentials');

    $description = sprintf(
      <<<EOF
      <p class="paragraph paragraph--small paragraph--minor">%s</p>
      <p class="paragraph paragraph--large paragraph-bold">%s</p>
      <a href="%s" class="link link--action">%s</a>
      EOF
      ,
      $vulnerabilities_label,
      $admin_email,
      $options_url,
      $change_email_link
    );

    echo '<div class="sheet"><div class="grid"><div class="grid-col grid-col--6 grid-col--small-12"><section class="sheet__section">';
    \ionos\essentials\wpscan\views\summary();
    echo '</section></div><div class="grid-col grid-col--6 grid-col--small-12">';

    render_section([
      'title'       => \esc_html__('Vulnerability alerting', 'ionos-essentials'),
      'id'          => IONOS_SECURITY_FEATURE_OPTION_MAIL_NOTIFY,
      'description' => $description,
      'checked'     => get_settings_value(IONOS_SECURITY_FEATURE_OPTION_MAIL_NOTIFY) ? 'checked' : '',
    ]);

    echo '</div>';

    \ionos\essentials\wpscan\views\issues([
      'type' => 'critical',
    ]);
    \ionos\essentials\wpscan\views\issues([
      'type' => 'warning',
    ]);

    echo '</div></div>';
  }

  echo '<div class="sheet"><div>';

  render_section([
    'title'       => \esc_html__('Password monitoring', 'ionos-essentials'),
    'id'          => IONOS_SECURITY_FEATURE_OPTION_CREDENTIALS_CHECKING,
    'description' => \esc_html__(
      'Monitor password leaks. If a password is suspected to be compromised, you will receive an email notification with instructions to change it. As a further precaution, the account will be locked until the password is updated.',
      'ionos-essentials',
    ),
    'checked'     => get_settings_value(IONOS_SECURITY_FEATURE_OPTION_CREDENTIALS_CHECKING) ? 'checked' : '',
  ]);

  if (! is_stretch()) {
    render_section([
      'title'       => \esc_html__('Block XML-RPC access', 'ionos-essentials'),
      'id'          => IONOS_SECURITY_FEATURE_OPTION_XMLRPC,
      'description' => \esc_html__(
        'Block access to XML-RPC, for security purposes we recommend keeping this blocked if not in use.',
        'ionos-essentials'
      ),
      'checked'     => get_settings_value(IONOS_SECURITY_FEATURE_OPTION_XMLRPC) ? 'checked' : '',
    ]);
  }

  render_section([
    'title'       => \esc_html__('Prohibit email login', 'ionos-essentials'),
    'id'          => IONOS_SECURITY_FEATURE_OPTION_PEL,
    'description' => \esc_html__(
      'Disable login with email addresses. This improves security by reducing the potential attack surface.',
      'ionos-essentials'
    ),
    'checked'     => get_settings_value(IONOS_SECURITY_FEATURE_OPTION_PEL) ? 'checked' : '',
  ]);

  echo '</div></div>';

  $advanced_headline = \esc_html__('Advanced', 'ionos-essentials');
  $tenant_label      = Tenant::get_label();
  // translators: %s is placeholder for the tenant name
  $hub_headline = sprintf(\esc_html__('%s Hub as WordPress Admin start page', 'ionos-essentials'), $tenant_label);
  // translators: %s is placeholder for the tenant name
  $hub_description = sprintf(\esc_html__(
    'Enable the %s Hub as a start page in your WordPress admin panel for a more personalised and efficient experience.',
    'ionos-essentials',
  ), $tenant_label);
  $dashboard_mode_checked = \get_option('ionos_essentials_dashboard_mode', true) ? 'checked' : '';

  printf(
    <<<EOF
    <h3 class="headline headline--sub">{$advanced_headline}</h3>
    <div class="sheet">
      <section class="sheet__section">
        <div class="grid">
          <div class="grid-col grid-col--8 grid-col--small-12">
            <h2 class="headline headline--sub">{$hub_headline}</h2>
            <p class="paragraph paragraph--neutral">{$hub_description}</p>
          </div>
          <div class="grid-col grid-col--4 grid-col--small-12 grid-col--align-right">
            <span class="input-switch">
              <input id="ionos_essentials_dashboard_mode" type="checkbox" data-description="{$hub_headline}" {$dashboard_mode_checked}>
              <label>
                <span class="input-switch__on"></span>
                <span class="input-switch__toggle"></span>
                <span class="input-switch__off"></span>
              </label>
            </span>
          </div>
        </div>
      </section>
    EOF
  );

  include_once(ABSPATH . 'wp-admin/includes/plugin.php');

  $match  = array_filter(array_keys(\get_plugins()), fn ($k) => str_starts_with($k, '01-ext-'));
  $plugin = reset($match); // first match or false

  // Check if Site-assistant plugin is installed
  if ($plugin && \get_option('extendify_onboarding_completed')) {
    $restart_headline    = \esc_html__('Restart AI Sitebuilder', 'ionos-essentials');
    $restart_description = \esc_html__(
      'Restart the AI Sitebuilder setup wizard to generate a new site from scratch. This will allow you to re-do the entire creation process.',
      'ionos-essentials',
    );
    $restart_link_text = \esc_html__('Restart AI Sitebuilder', 'ionos-essentials');

    printf(
      <<<EOF
      <section class="sheet__section">
        <div class="grid">
          <div class="grid-col grid-col--8 grid-col--small-12">
            <h2 class="headline headline--sub">{$restart_headline}</h2>
            <p class="paragraph paragraph--neutral">
              {$restart_description}
              <span class="link" id="restart-ai-sitebuilder">{$restart_link_text}</span>
            </p>
          </div>
        </div>
      </section>
      EOF
    );
  }

  if (defined('IONOS_ESSENTIALS_MCP_SERVER_ACTIVE')) {
    require_once(__DIR__ . '/../../mcp/view.php');
  }

  echo '</div></div></div></div>';
}

render_tools_tab();
