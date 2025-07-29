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

    $tenant  = strToLower(\get_option('ionos_group_brand', 'ionos'));
    $market  = strtolower(\get_option($tenant . '_market', ''));

    $control_panel_links = [
      'ionos'  => [
        'ca' => 'https://my.ionos.ca/projects-overview',
        'de' => 'https://mein.ionos.de/projects-overview',
        'fr' => 'https://my.ionos.fr/projects-overview',
        'gb' => 'https://my.ionos.co.uk/projects-overview',
        'it' => 'https://my.ionos.it/projects-overview',
        'mx' => 'https://my.ionos.mx/projects-overview',
        'uk' => 'https://my.ionos.co.uk/projects-overview',
        'us' => 'https://my.ionos.com/projects-overview',
      ],
      'piensa' => [
        'es' => 'https://www.piensasolutions.com/clientes',
        'us' => 'https://www.piensasolutions.com/clientes',
        'de' => 'https://www.piensasolutions.com/clientes',
      ],
    ];

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
            description.innerHTML = '<?php printf(addslashes($website_url_description)); ?>';

            document.getElementById( 'siteurl' )?.parentNode.appendChild( description.cloneNode( true ) );
            document.getElementById( 'home' )?.parentNode.appendChild( description.cloneNode( true ) );
        } )();
      </script>
        <?php
  },
  10,
  1
);
