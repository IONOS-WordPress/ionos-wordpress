<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>
  <?php

  use ionos\essentials\Tenant;

  \esc_html_e('Password Reset Necessary', 'ionos-essentials'); ?></title>

  <?php
  use const ionos\essentials\PLUGIN_DIR;

  \wp_register_style(
    handle: 'ionos-exos',
    src: 'https://ce1.uicdn.net/exos/framework/3.0/exos.min.css',
    ver: '3.0',
  );

  \wp_print_styles(['ionos-exos']);

  ?>
</head>
<body>
<div class="page-content">
  <main id="id" style="align-items: center; justify-content: center;">
    <div class="sheet" style="width: 570px;">
      <section class="sheet__section">
        <?php

        printf(
          '<img src="%s" alt="%s" style="width: 118px; height: auto; margin-bottom: 16px;">',
          \esc_url(
            \plugins_url('ionos-essentials/inc/dashboard/data/tenant-logos/' . Tenant::get_slug() . '.svg', PLUGIN_DIR)
          ),
          Tenant::get_label()
        );

  ?>
        <h3 class="headline"><?php \esc_html_e('Security alert', 'ionos-essentials'); ?></h3>
        <p class="paragraph">
        <?php
    \esc_html_e(
      'We have detected that the password for this WordPress Admin user matches one found in an online database of known compromised passwords. As a precaution, we have disabled access to this account to keep it secure.',
      'ionos-essentials'
    );

  echo '</p><p class="paragraph">';
  \esc_html_e(
    'To re-enable access to this account, the password must be updated. We have sent an email with reset instructions to',
    'ionos-essentials'
  );

  printf(' <strong>%s</strong>', \esc_html($mail));
  ?>
        </p>
      </section>
    </div>
  </main>
</div>
</body>
</html>
