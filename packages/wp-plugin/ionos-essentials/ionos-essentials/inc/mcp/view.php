<?php

namespace ionos\essentials\mcp;

defined('ABSPATH') || exit();

if (is_wp7_mcp_active()) : ?>
<section class="sheet__section">
  <div class="grid">
      <div class="grid-col grid-col--8 grid-col--small-12">
          <h2 class="headline headline--sub" style="display: flex; align-items: center;">
              <?php \esc_html_e('Allow AI to control your WordPress via MCP', 'ionos-essentials'); ?>
              <p class="paragraph" style="margin: 0 0 0 10px;">
                <span class="badge badge--neutral-solid"><?php \esc_html_e('Managed via WP7.0', 'ionos-essentials'); ?></span>
              </p>
          </h2>
          <p class="paragraph paragraph--neutral">
              <?php \esc_html_e(
                'Grant external AI Clients secure access to your content. This feature is now integrated natively into your WP7.0 Control Panel.',
                'ionos-essentials'
              ); ?>
          </p>

          <?php if (! is_legacy_plugin_installed()) : ?>
              <p class="paragraph" style="margin-top: 15px;">
                  <a href="<?php echo esc_url(
                    admin_url('options-connectors.php')
                  ); ?>" class="link" style="display: inline-flex; align-items: center; gap: 5px;">
                      <i class="exos-icon exos-icon-external-link-14"></i>
                      <?php \esc_html_e('Click here to use the native WP7.0 Connectors', 'ionos-essentials'); ?>
                  </a>
              </p>
          <?php else : ?>
              <p class="paragraph" style="margin-top: 15px;">
                  <a href="<?php echo esc_url(
                    admin_url('options-connectors.php')
                  ); ?>" class="link" style="display: inline-flex; align-items: center; gap: 5px; font-weight: bold;">
                      <i class="exos-icon exos-icon-external-link-14"></i>
                      <?php \esc_html_e('Go check out the native Connectors dashboard for better usage of MCP', 'ionos-essentials'); ?>
                  </a>
              </p>
          <?php endif; ?>
      </div>

      <div class="grid-col grid-col--4 grid-col--small-12 grid-col--align-right">
          <span class="input-switch">
              <input id="ionos-essentials-mcp" class="ionos-essentials-mcp-activate" type="checkbox" data-manual="true" data-description="<?php \esc_attr_e('Allow AI to control your WordPress via MCP', 'ionos-essentials'); ?>"
              <?php echo (IONOS_ESSENTIALS_MCP_SERVER_ACTIVE) ? 'checked' : ''; ?>
              data-track-link="mcp-toggle"
              >
              <label>
                  <span class="input-switch__on"></span>
                  <span class="input-switch__toggle"></span>
                  <span class="input-switch__off"></span>
              </label>
          </span>
      </div>

      <?php if (is_legacy_plugin_installed()) : ?>
          <div class="grid-col grid-col--12" id="ionos-essentials-mcp-info">
              <div class="loading hidden"></div>
              <div class="code snippet hidden">
                  <p class="paragraph paragraph--bold"><?php \esc_html_e('Legacy Configuration Snippet:', 'ionos-essentials'); ?></p>
                  <pre class="code mcp"><button class="copy-icon" title="<?php \esc_attr_e(
                    'Copy to clipboard',
                    'ionos-essentials'
                  ); ?>"><i class="exos-icon exos-icon-clipboard-copy-14"></i></button><code></code></pre>
              </div>

              <?php
              $label         = \esc_html('Generate application password for user', 'ionos-essentials');
        $revoke_app_password = 0;
        if (\WP_Application_Passwords::application_name_exists_for_user(
          wp_get_current_user()
            ->ID,
          APPLICATION_NAME
        )) {
          $label               = \esc_html('Regenerate application password for user', 'ionos-essentials');
          $revoke_app_password = 1;
        }

        printf(
          '<button class="button button--primary ionos-essentials-mcp-activate %s" data-revoke-app-password="%s" style="margin-top: 15px;">%s</button>',
          (IONOS_ESSENTIALS_MCP_SERVER_ACTIVE) ? '' : 'hidden',
          $revoke_app_password,
          $label
        );
        ?>
          </div>
      <?php else : ?>
          <div class="grid-col grid-col--12" id="ionos-essentials-mcp-info" style="display: none !important;">
            <div class="loading hidden"></div>
            <div class="code snippet hidden"><code></code></div>
            <button class="ionos-essentials-mcp-activate hidden" data-revoke-app-password="0" style="display: none !important;"></button>
          </div>
      <?php endif; ?>
  </div>
</section>

<?php else : ?>
<section class="sheet__section">
  <div class="grid">
      <div class="grid-col grid-col--8 grid-col--small-12">
          <h2 class="headline headline--sub" style="display: flex; align-items: center;">
            <?php \esc_html_e('Allow AI to control your WordPress via MCP', 'ionos-essentials') ?>
              <p class="paragraph" style="margin: 0 0 0 10px;">
                <span class="badge badge--neutral-solid"><?php \esc_html_e('For developers', 'ionos-essentials'); ?></span>
              </p>
          </h2>

          <p class="paragraph paragraph--neutral">
              <?php \esc_html_e(
                'Grant external AI Clients like Gemini & Claude secure access to your content, with the WordPress-MCP plugin.',
                'ionos-essentials');
  ?>
          </p>
      </div>
      <?php echo (IONOS_ESSENTIALS_MCP_SERVER_ACTIVE) ? 'checked' : ''; ?>
      <div class="grid-col grid-col--4 grid-col--small-12 grid-col--align-right">
          <span class="input-switch" >
              <input id="ionos-essentials-mcp" class="ionos-essentials-mcp-activate" type="checkbox" data-manual="true" data-description="<?php \esc_attr_e('Allow AI to control your WordPress via MCP', 'ionos-essentials'); ?>"
              <?php echo (IONOS_ESSENTIALS_MCP_SERVER_ACTIVE) ? 'checked' : ''; ?>
              data-track-link="mcp-toggle"
              >
              <label>
                  <span class="input-switch__on"></span>
                  <span class="input-switch__toggle"></span>
                  <span class="input-switch__off"></span>
              </label>
          </span>
      </div>
      <div class="grid-col grid-col--12" id="ionos-essentials-mcp-info">
        <div class="loading hidden">
          <ul class="stripe stripe--align-center">
            <li class="stripe__item">
              <div class="loading-spin loading-spin--bright loading-spin--small"></div>
            </li>
            <li class="stripe__item">
              <p class="paragraph paragraph--cropped paragraph--align-center"><?php \esc_html_e('Installing MCP capabilities', 'ionos-essentials'); ?></p>
            </li>
          </ul>
        </div>

        <div class="code snippet hidden">
          <p class="paragraph paragraph--bold">
            <?php \esc_html_e(
              'Enter the following JSON into your preferred AI Clients settings file (under \'mcpServers\' / \'servers\')',
              'ionos-essentials'
            ); ?>
          </p>
          <p class="paragraph paragraph--minor">
            <i class="exos-icon exos-icon-warningmessage-32"></i>
            <?php \esc_html_e('Please take note of this as this is not stored and can not be viewed again.', 'ionos-essentials'); ?>
          </p>
          <pre class="code mcp"><button class="copy-icon" title="<?php \esc_attr_e(
            'Copy to clipboard',
            'ionos-essentials'
          ); ?>"><i class="exos-icon exos-icon-clipboard-copy-14"></i></button><code></code></pre>
        </div>

        <?php
        $label        = \esc_html('Generate application password for user', 'ionos-essentials');
  $revoke_app_password= 0;
  if (\WP_Application_Passwords::application_name_exists_for_user(wp_get_current_user()->ID, APPLICATION_NAME)) {
    $label              = \esc_html('Regenerate application password for user', 'ionos-essentials');
    $revoke_app_password= 1;
  }

  printf(
    '<button class="button button--primary ionos-essentials-mcp-activate %s" data-revoke-app-password="%s">%s</button>',
    (IONOS_ESSENTIALS_MCP_SERVER_ACTIVE) ? '' : 'hidden',
    $revoke_app_password,
    $label
  );
  ?>
      </div>
  </div>
</section>
<?php endif; ?>
