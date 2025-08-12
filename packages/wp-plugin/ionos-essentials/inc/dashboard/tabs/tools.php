<?php

namespace ionos\essentials\dashboard;

defined('ABSPATH') || exit();

use ionos\essentials\Tenant;
use function ionos\essentials\is_stretch;
use const ionos\essentials\PLUGIN_DIR;
use const ionos\essentials\security\IONOS_SECURITY_FEATURE_OPTION;
use const ionos\essentials\security\IONOS_SECURITY_FEATURE_OPTION_CREDENTIALS_CHECKING;
use const ionos\essentials\security\IONOS_SECURITY_FEATURE_OPTION_DEFAULT;
use const ionos\essentials\security\IONOS_SECURITY_FEATURE_OPTION_MAIL_NOTIFY;
use const ionos\essentials\security\IONOS_SECURITY_FEATURE_OPTION_PEL;
use const ionos\essentials\security\IONOS_SECURITY_FEATURE_OPTION_XMLRPC;

function render_section(array $args): void
{
  ?>
<section class="sheet__section">
      <div class="grid">
          <div class="grid-col grid-col--8 grid-col--small-12">
              <h2 class="headline headline--sub headline--cropped"><?php echo \esc_html($args['title']); ?></h2>
              <p class="paragraph paragraph--neutral" style="margin-bottom: 0;">
                  <?php echo \wp_kses($args['description'], 'post'); ?>
              </p>
          </div>
          <div class="grid-col grid-col--4 grid-col--small-12 grid-col--align-right">
              <span class="input-switch">
                  <input
                    id="<?php echo \esc_attr($args['id']); ?>"
                    data-option="<?php echo \esc_attr(IONOS_SECURITY_FEATURE_OPTION); ?>"
                    data-description="<?php echo \esc_attr($args['title']); ?>"
                    type="checkbox"
                    <?php printf($args['checked']); ?>
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
<?php
}

function get_settings_value($key)
{

  $options = \get_option(IONOS_SECURITY_FEATURE_OPTION, IONOS_SECURITY_FEATURE_OPTION_DEFAULT);

  return $options[$key] ?? false;
}

?>
 <div id="tools" class="page-section ionos-tab">
    <div class="grid">
      <div class="grid-col grid-col--12">
        <h3 class="headline headline--sub"><?php \esc_html_e('Tools', 'ionos-essentials'); ?></h3>

        <div class="sheet">
          <section class="sheet__section">
            <div class="grid">
                <div class="grid-col grid-col--8 grid-col--small-12">
                    <h2 class="headline headline--sub headline--cropped">
                      <?php \esc_html_e('Maintenance page', 'ionos-essentials'); ?>
                      <span class="badge badge--warning-solid ionos-maintenance-only"><?php \esc_html_e('Active', 'ionos-essentials'); ?></span>
                </h2>
                    <p class="paragraph paragraph--neutral" style="margin-bottom: 0;">
                        <?php \esc_html_e('Temporarily block public access to your site with a maintenance page.', 'ionos-essentials'); ?>
                    </p>
                    <a href="<?php echo \esc_url(
                      \plugins_url('ionos-essentials/inc/maintenance_mode/assets/maintenance.html', PLUGIN_DIR)
                    ); ?>" target="_blank"
                       class="link link--action"><?php \esc_html_e('Preview maintenance page', 'ionos-essentials'); ?></a>
                </div>
                <div class="grid-col grid-col--4 grid-col--small-12 grid-col--align-right">
                    <span class="input-switch">
                        <input id="ionos_essentials_maintenance_mode" type="checkbox" data-description="<?php \esc_attr_e('Maintenance mode', 'ionos-essentials'); ?>"
                        <?php
                        if (\ionos\essentials\maintenance_mode\is_maintenance_mode()) {
                          echo 'checked';
                        }
?>
                        >
                        <label>
                            <span class="input-switch__on"></span>
                            <span class="input-switch__toggle"></span>
                            <span class="input-switch__off"></span>
                        </label>
                    </span>
                </div>
            </div>
        </div>

        <h3 class="headline headline--sub may-have-issue-dot"><?php \esc_html_e('Website security', 'ionos-essentials'); ?></h3>

        <?php if (empty(\ionos\essentials\wpscan\get_wpscan()->get_error())) { ?>
          <div class="sheet">
              <div class="grid">
                <div class="grid-col grid-col--6 grid-col--small-12">
                  <section class="sheet__section">
                    <?php \ionos\essentials\wpscan\views\summary(); ?>
                  </section>
                </div>
                <div class="grid-col grid-col--6 grid-col--small-12">
                <?php
                $description=  sprintf('
                      <p class="paragraph paragraph--small paragraph--minor">%s</p>
                      <p class="paragraph paragraph--large paragraph-bold">%s</p>
                      <a href="%s" class="link link--action">%s</a>',
                  \esc_html__('Vulnerabilities detected are being emailed to', 'ionos-essentials'),
                  \get_option('admin_email'),
                  admin_url('options-general.php'),
                  \esc_html__('Change email address', 'ionos-essentials')
                );

          render_section([
            'title'        => \esc_html__('Vulnerability alerting', 'ionos-essentials'),
            'id'           => IONOS_SECURITY_FEATURE_OPTION_MAIL_NOTIFY,
            'description'  => $description,
            'checked'      => get_settings_value(IONOS_SECURITY_FEATURE_OPTION_MAIL_NOTIFY) ? 'checked' : '',
          ]);
          ?>
                </div>
                   <?php
          \ionos\essentials\wpscan\views\issues([
            'type'      => 'critical',
          ]);
          \ionos\essentials\wpscan\views\issues([
            'type'      => 'warning',
          ]);
          ?>
              </div>
            </div>
        <?php } ?>

        <div class="sheet">
            <div>
              <?php
          render_section([
            'title'       => \esc_html__('Password monitoring', 'ionos-essentials'),
            'id'          => IONOS_SECURITY_FEATURE_OPTION_CREDENTIALS_CHECKING,
            'description' => \esc_html__(
              'Monitor password leaks. If a password is suspected to be compromised, you will receive an email notification with instructions to change it. As a further precaution, the account will be locked until the password is updated.',
              'ionos-essentials',
            ),
            'checked'   => get_settings_value(IONOS_SECURITY_FEATURE_OPTION_CREDENTIALS_CHECKING) ? 'checked' : '',
          ]);

if (! is_stretch()) {
  render_section([
    'title'       => \esc_html__('Block XML-RPC access', 'ionos-essentials'),
    'id'          => IONOS_SECURITY_FEATURE_OPTION_XMLRPC,
    'description' => \esc_html__(
      'Block access to XML-RPC, for security purposes we recommend keeping this blocked if not in use.',
      'ionos-essentials'
    ),
    'checked'   => get_settings_value(IONOS_SECURITY_FEATURE_OPTION_XMLRPC) ? 'checked' : '',
  ]);
}

render_section([
  'title'       => \esc_html__('Prohibit email login', 'ionos-essentials'),
  'id'          => IONOS_SECURITY_FEATURE_OPTION_PEL,
  'description' => \esc_html__(
    'Disable login with email addresses. This improves security by reducing the potential attack surface.',
    'ionos-essentials'
  ),
  'checked'   => get_settings_value(IONOS_SECURITY_FEATURE_OPTION_PEL) ? 'checked' : '',
]);
?>

          </div>
        </div>

        <h3 class="headline headline--sub"><?php \esc_html_e('Advanced', 'ionos-essentials'); ?></h3>
                <div class="sheet">
          <section class="sheet__section">
            <div class="grid">
                <div class="grid-col grid-col--8 grid-col--small-12">
                    <h2 class="headline headline--sub headline--cropped">
                      <?php
                        printf(
                          // translators: %s is placeholder for the tenant name
                          \esc_html__('%s Hub as WordPress Admin start page', 'ionos-essentials'),
                          Tenant::get_label()
                        );
?>
                </h2>
                    <p class="paragraph paragraph--neutral" style="margin-bottom: 0;">
                        <?php
  // translators: %s is placeholder for the tenant name
  printf(\esc_html__(
    'Enable the %s Hub as a start page in your WordPress admin panel for a more personalised and efficient experience.',
    'ionos-essentials',
  ), Tenant::get_label());
?>
                    </p>
                </div>
                <div class="grid-col grid-col--4 grid-col--small-12 grid-col--align-right">
                    <span class="input-switch">
                        <input id="ionos_essentials_dashboard_mode" type="checkbox" data-description="<?php printf(\esc_html__('%s Hub as WordPress Admin start page', 'ionos-essentials'), Tenant::get_label()); ?>"
                        <?php if (\get_option('ionos_essentials_dashboard_mode', true)) {
                          echo 'checked';
                        } ?>
                        >
                        <label>
                            <span class="input-switch__on"></span>
                            <span class="input-switch__toggle"></span>
                            <span class="input-switch__off"></span>
                        </label>
                    </span>
                </div>
            </div>
        </div>
      </div>
    </div>
  </div>
