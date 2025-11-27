<?php

namespace ionos\essentials\mcp;

defined('ABSPATH') || exit();

?>
<section class="sheet__section">
  <div class="grid">
      <div class="grid-col grid-col--8 grid-col--small-12">
          <h2 class="headline headline--sub" style="display: flex; align-items: center;">
            <?php
                \esc_html_e('Allow AI to control your WordPress via MCP', 'ionos-essentials')
?>
              <p class="paragraph" style="margin: 0 0 0 10px;">
                <span class="badge badge--neutral-solid"><?php \esc_html_e('For developers', 'ionos-essentials'); ?></span>
              </p>
          </h2>

          <p class="paragraph paragraph--neutral">
              <?php \esc_html_e(
                'Grant external LLMs like Gemini & Claude secure access to your content, with the WordPress-MCP plugin.',
                'ionos-essentials');
?>
          </p>
      </div>
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
            <?php \esc_html_e('Enter the following JSON into your preferred AI Clients settings file', 'ionos-essentials'); ?>
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
        $label = \esc_html('Generate application password for user', 'ionos-essentials');
        $revokeAppPassword = 0;
        if(user_has_application_password()){
          $label = \esc_html('Regenerate application password for user', 'ionos-essentials');
          $revokeAppPassword = 1;
        }

        printf(
          '<button class="button button--primary ionos-essentials-mcp-activate %s" data-revoke-app-password="%s">%s</button>',
          (IONOS_ESSENTIALS_MCP_SERVER_ACTIVE) ? '' : 'hidden',
          $revokeAppPassword,
          $label
        );
        ?>


      </div>
  </div>
</section>
