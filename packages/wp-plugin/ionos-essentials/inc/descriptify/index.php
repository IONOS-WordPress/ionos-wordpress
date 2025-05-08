<?php

namespace ionos\essentials\descriptify;

// exit if accessed directly
if (! defined('ABSPATH')) {
  exit();
}

add_action(
  'admin_print_footer_scripts',
  function () {
    global $pagenow;

    if (\is_plugin_active(
      'ionos-assistant/ionos-assistant.php'
    ) || ! is_admin() || 'options-general.php' !== $pagenow) {
      return;
    }

    $config_file = __DIR__ . '/config.php';

    if (! file_exists($config_file)) {
      return;
    }

    require $config_file;
    $tenant  = strToLower(\get_option('ionos_group_brand'));
    $market  = strtolower(\get_option($tenant . '_market', ''));

    if (! isset($control_panel_links[$tenant][$market])) {
      return;
    }

    $control_panel_link = $control_panel_links[$tenant][$market];

    $website_url_description = sprintf( /* translators: s=link to control panel */
      __('You can customize and manage your URL (domain) easily at <a href="%1$s" target="_blank">%2$s App Center</a>.', 'ionos-essentials'),
      $control_panel_link,
      \get_option('ionos_group_brand_menu', 'your')
    );
    ?>
      <style>
        #home-description {
          display: none;
        }
      </style>
      <script>
        ( function () {
            const description = document.createElement('p');
            description.className = 'description';
            description.innerHTML = '<?php echo addslashes($website_url_description); ?>';

            document.getElementById( 'siteurl' )?.parentNode.appendChild( description.cloneNode( true ) );
            document.getElementById( 'home' )?.parentNode.appendChild( description.cloneNode( true ) );
        } )();
      </script>
        <?php
  },
  10,
  1
);
