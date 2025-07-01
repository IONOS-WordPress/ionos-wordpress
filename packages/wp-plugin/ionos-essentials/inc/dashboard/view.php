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
\wp_register_style(
  handle: 'ionos-essentials-dashboard',
  src: \plugins_url('ionos-essentials/inc/dashboard/dashboard.css', PLUGIN_DIR),
  ver: filemtime(PLUGIN_DIR . '/inc/dashboard/dashboard.css')
);
\wp_print_styles('ionos-essentials-dashboard');
?>

<?php blocks\welcome\render_callback(); ?>

<main id="content" class="<?php echo \ionos\essentials\maintenance_mode\is_maintenance_mode() ? 'ionos-maintenance-mode' : ''; ?>">
  <div class="page-section">
    <?php blocks\banner\render_callback(); ?>
  </div>

  <?php
    require_once __DIR__ . '/tabs/overview.php';
require_once __DIR__ . '/tabs/tools.php';
?>

</main>
</template>
