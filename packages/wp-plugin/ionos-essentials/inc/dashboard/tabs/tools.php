<?php

namespace ionos\essentials\dashboard;

use function ionos\essentials\is_stretch;
use const ionos\essentials\PLUGIN_DIR;
use const ionos\essentials\security\IONOS_SECURITY_FEATURE_OPTION;
use const ionos\essentials\security\IONOS_SECURITY_FEATURE_OPTION_CREDENTIALS_CHECKING;
use const ionos\essentials\security\IONOS_SECURITY_FEATURE_OPTION_DEFAULT;
use const ionos\essentials\security\IONOS_SECURITY_FEATURE_OPTION_MAIL_NOTIFY;
use const ionos\essentials\security\IONOS_SECURITY_FEATURE_OPTION_PEL;
use const ionos\essentials\security\IONOS_SECURITY_FEATURE_OPTION_XMLRPC;

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
                        <?php \esc_html_e('Temporarily block public access to your site with a maintenance page.', 'post'); ?>
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
          </section>
        </div>

        <h3 class="headline headline--sub"><?php \esc_html_e('Website security', 'ionos-essentials'); ?></h3>
          <div class="sheet">
              <div class="grid">
                <div class="grid-col grid-col--4 grid-col--small-12">
                  <section class="sheet__section">
                    <?php blocks\vulnerability\render_callback(); ?>
                  </section>
                </div>
                <div class="grid-col grid-col--8 grid-col--small-12">
                <?php
                $description=  sprintf('<strong style="font-size: 1.2em">%s</strong>
                      <br><br>
                      <a href="%s" class="link link--action">%s</a>',
                  get_option('admin_email'),
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
              </div>
            </div>

        <div class="sheet">
            <div>
              <?php
render_section([
  'title'       => \esc_html__('Password monitoring', 'ionos-essentials'),
  'id'          => IONOS_SECURITY_FEATURE_OPTION_CREDENTIALS_CHECKING,
  'description' => \esc_html__(
    'Monitor password leaks. If a password is found in a data breach, you will be notified.',
    'ionos-essentials',
  ),
  'checked'   => get_settings_value(IONOS_SECURITY_FEATURE_OPTION_CREDENTIALS_CHECKING) ? 'checked' : '',
]);

if (! is_stretch()) {
  render_section([
    'title'       => \esc_html__('Enable XML-RPC Guard', 'ionos-essentials'),
    'id'          => IONOS_SECURITY_FEATURE_OPTION_XMLRPC,
    'description' => \esc_html__(
      'Security disables XML-RPC in WordPress. This improves security by reducing the potential attack surface. XML-RPC can be exploited to launch brute force attacks, DDoS attacks, or gain unauthorized access to a website.',
      'ionos-essentials'
    ),
    'checked'   => get_settings_value(IONOS_SECURITY_FEATURE_OPTION_XMLRPC) ? 'checked' : '',
  ]);
}

render_section([
  'title'       => \esc_html__('Prohibit Email Login', 'ionos-essentials'),
  'id'          => IONOS_SECURITY_FEATURE_OPTION_PEL,
  'description' => \esc_html__(
    'Security disables login with email addresses. This improves security by reducing the potential attack surface.',
    'ionos-essentials'
  ),
  'checked'   => get_settings_value(IONOS_SECURITY_FEATURE_OPTION_PEL) ? 'checked' : '',
]);
?>

          </div>
        </div>
      </div>
    </div>
  </div>


<?php
function render_section($args)
{
  ?>
<section class="sheet__section">
      <div class="grid">
          <div class="grid-col grid-col--8 grid-col--small-12">
              <h2 class="headline headline--sub headline--cropped"><?php echo esc_html($args['title']); ?></h2>
              <p class="paragraph paragraph--neutral" style="margin-bottom: 0;">
                  <?php echo \wp_kses($args['description'], 'post'); ?>
              </p>
          </div>
          <div class="grid-col grid-col--4 grid-col--small-12 grid-col--align-right">
              <span class="input-switch">
                  <input id="<?php echo esc_attr($args['id']); ?>" data-option="<?php echo \esc_attr(
                    IONOS_SECURITY_FEATURE_OPTION
                  ); ?>" data-description="<?php echo esc_attr($args['title']); ?>" type="checkbox" <?php echo esc_attr($args['checked']); ?>>
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
