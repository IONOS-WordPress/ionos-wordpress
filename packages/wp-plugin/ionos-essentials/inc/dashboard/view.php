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
/* Theses rules are copied from Exos, as they has to be present outside the shadowDOM*/
.snackbar-container {
    bottom: 20px;
    pointer-events: none;
    position: fixed;
    width: 100%;
    z-index: 81;
}
.snackbar {
    align-items: center;
    animation-fill-mode: both;
    border-radius: var(--small-border-radius, 8px);
    box-shadow: var(--primary-shadow, 0 2px 8px 0 #71809580);
    box-sizing: border-box;
    color: var(--default-text-color, #001b41);
    display: flex;
    line-height: 18px;
    margin: 0 auto;
    max-width: 320px;
    padding: 16px 14px;
    transition-timing-function: ease-in-out;
    visibility: hidden;
}
.snackbar--visible {
    animation-duration: .2s;
    animation-name: keyframes--snackbar-translate-in;
    visibility: visible
}

.snackbar--hidden {
    animation-duration: .2s;
    animation-name: keyframes--snackbar-translate-out
}

.snackbar--success {
    border: var(--semantic-container-border-width,0) solid var(--success-shape-color,#0fa954)
}
.snackbar--success-solid, .snackbar--warning-solid  {
    background-color: var(--solid-success-background-color,#12cf76)
}

.snackbar--warning-solid {
    background-color: var(--solid-warning-background-color,#fa0)
}

.snackbar--critical-solid {
    background-color: var(--solid-critical-background-color,#ff6159)
}

.snackbar--critical-solid,.snackbar--neutral-solid {
    border: none;
    color: var(--default-text-color,#001b41)
}
</style>

<template id="ionos_dashboard" shadowrootmode="open">

<?php
\wp_register_style(
  handle: 'ionos-essentials-dashboard',
  src: \plugins_url('ionos-essentials/inc/dashboard/dashboard.css', PLUGIN_DIR),
  ver: filemtime(PLUGIN_DIR . '/inc/dashboard/dashboard.css')
);
\wp_register_style(
  handle: 'ionos-wpscan',
  src: \plugins_url('ionos-essentials/inc/wpscan/wpscan.css', PLUGIN_DIR),
  ver: filemtime(PLUGIN_DIR . '/inc/wpscan/wpscan.css')
);
\wp_print_styles(['ionos-essentials-dashboard', 'ionos-wpscan']);

\wp_register_script('ionos-exos-js', 'https://ce1.uicdn.net/exos/framework/3.0/exos.min.js', [], true);
\wp_print_scripts('ionos-exos-js');

?>

<?php blocks\welcome\render_callback(); ?>

<main id="content" class="<?php \ionos\essentials\maintenance_mode\is_maintenance_mode() && printf('ionos-maintenance-mode'); ?> issues-found">
  <div class="page-section">
    <?php blocks\banner\render_callback(); ?>
  </div>

  <?php
    require_once __DIR__ . '/tabs/overview.php';
require_once __DIR__ . '/tabs/tools.php';
?>

</main>
</template>
