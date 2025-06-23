<?php

namespace ionos\essentials\dashboard;

require_once __DIR__ . '/blocks/banner/index.php';
require_once __DIR__ . '/blocks/welcome/index.php';
require_once __DIR__ . '/blocks/vulnerability/index.php';
require_once __DIR__ . '/blocks/next-best-actions/index.php';
require_once __DIR__ . '/blocks/my-account/index.php';
require_once __DIR__ . '/blocks/whats-new/index.php';
require_once __DIR__ . '/blocks/quick-links/index.php';

use const ionos\essentials\PLUGIN_DIR;

// Fontface must be loaded before the template is rendered
?>
<style>
@font-face {
    font-display: swap;
    font-family: OpenSansRegular;
    src: url(https://ce1.uicdn.net/exos/fonts/open-sans/opensans-regular.woff2) format("woff2"),url(https://ce1.uicdn.net/exos/fonts/open-sans/opensans-regular.woff) format("woff")
}

@font-face {
    font-display: swap;
    font-family: OpenSansSemibold;
    src: url(https://ce1.uicdn.net/exos/fonts/open-sans/opensans-semibold.woff2) format("woff2"),url(https://ce1.uicdn.net/exos/fonts/open-sans/opensans-semibold.woff) format("woff")
}

@font-face {
    font-display: swap;
    font-family: OverpassRegular;
    src: url(https://ce1.uicdn.net/exos/fonts/overpass/overpass-regular.woff2) format("woff2"),url(https://ce1.uicdn.net/exos/fonts/overpass/overpass-regular.woff) format("woff")
}

@font-face {
    font-display: swap;
    font-family: OverpassSemibold;
    src: url(https://ce1.uicdn.net/exos/fonts/overpass/overpass-semibold.woff2) format("woff2"),url(https://ce1.uicdn.net/exos/fonts/overpass/overpass-semibold.woff) format("woff")
}
@font-face {
    -webkit-font-smoothing:antialiased;-moz-osx-font-smoothing:grayscale;speak:none;font-family: exos-icon-font;
    font-style: normal;
    font-variant: normal;
    font-weight: 400;
    line-height:1;src: url(https://ce1.uicdn.net/exos/icons/exos-icon-font.woff2?v=23) format("woff2"),url(https://ce1.uicdn.net/exos/icons/exos-icon-font.woff?v=23) format("woff");
    text-transform:none;
}
</style>


<template id="ionos_dashboard" shadowrootmode="open">

<?php
wp_register_style(
  'ionos-essentials-dashboard',
  plugins_url('ionos-essentials/inc/dashboard/dashboard.css', PLUGIN_DIR)
);
wp_register_style('ionos-exos', 'https://ce1.uicdn.net/exos/framework/3.0/exos.min.css');

wp_print_styles(['ionos-exos', 'ionos-essentials-dashboard']);

wp_deregister_style('ionos-exos');
wp_deregister_style('ionos-essentials-dashboard');
?>

<?php blocks\welcome\render_callback(); ?>

<main id="content">
  <div class="page-section">
  <?php blocks\banner\render_callback(); ?>
  </div>

<div class="page-section">
  <div class="grid grid--full-height">
    <!--
      There must be no whitespace or newline within the .grid-col elements, as this would display even an empty cell,
      but empty cells should not be displayed (see)
    -->
    <div class="grid-col grid-col--12 ionos_next_best_actions"><?php blocks\next_best_actions\render_callback(); ?></div>
    <div class="grid-col grid-col--4 grid-col--small-12"><?php blocks\vulnerability\render_callback(); ?></div>
    <div class="grid-col grid-col--8 grid-col--small-12"><?php blocks\quick_links\render_callback(); ?></div>
    <div class="grid-col grid-col--5 grid-col--medium-6 grid-col--small-12"><?php blocks\my_account\render_callback(); ?></div>
    <div class="grid-col grid-col--7 grid-col--medium-6 grid-col--small-12"><?php blocks\whatsnew\render_callback(); ?></div>
  </div>
</div>

</main>
</template>
