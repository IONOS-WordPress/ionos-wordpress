<?php
namespace ionos\essentials\dashboard;

use ionos\essentials\Tenant;
use function ionos\essentials\_is_plugin_active;

require_once  '../../../../../../wp-load.php';

do_action('admin_init');

defined('ABSPATH') || exit();


require_once __DIR__ . '/blocks/vulnerability/index.php';
require_once __DIR__ . '/blocks/next-best-actions/index.php';
require_once __DIR__ . '/blocks/my-account/index.php';
require_once __DIR__ . '/blocks/whats-new/index.php';
require_once __DIR__ . '/blocks/quick-links/index.php';
require_once __DIR__ . '/blocks/popup/index.php';
require_once __DIR__ . '/blocks/site-health/index.php';
require_once __DIR__ . '/blocks/adserver/index.php';

// Fontface must be loaded before the template is rendered
?>
<!DOCTYPE html>
<html <?php \language_attributes(); ?>>
<head>
  <meta charset="<?php \bloginfo('charset'); ?>">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title><?php \esc_html_e('Dashboard', 'ionos-essentials'); ?></title>
</head>
<body id="wpbody-content">
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

<div id="ionos_dashboard">

<?php
$tenant     =  Tenant::get_slug();
$theme_file = __DIR__ . '/exos-themes/' . $tenant . '.css';
if (file_exists($theme_file)) {
  \wp_register_style(
    handle: 'exos-theme',
    src: \plugins_url($tenant . '.css', $theme_file),
    ver: filemtime($theme_file)
  );
  \wp_print_styles(['exos-theme']);
}

\wp_register_style(
  handle: 'ionos-essentials-dashboard',
  src: \plugin_dir_url(__FILE__) . 'dashboard.css',
  ver: filemtime(\plugin_dir_path(__FILE__) . 'dashboard.css')
);
\wp_print_styles(['ionos-essentials-dashboard', 'ionos-wpscan']);

\wp_register_script('ionos-exos-js', 'https://ce1.uicdn.net/exos/framework/3.0/exos.min.js', [], true);
\wp_print_scripts('ionos-exos-js');

?>

<div class="static-overlay__blocker"></div>
<div class="static-overlay__container dialog-closer" id="learn-more-overlay">
  <div class="sheet static-overlay--closable static-overlay__content sheet--micro-effect" data-static-overlay-id="demo-overlay1" style="margin-top: inherit;">
    <section class="sheet__section">
      <div style="display: flex; justify-content: right;">
        <i class="exos-icon exos-icon-deleteinput-16 dialog-closer"></i>
      </div>
      <h3 class="headline headline--sub"><?php \esc_html_e('Vulnerability scan information', 'ionos-essentials'); ?></h3>
      <ul class="bullet-list">
        <li><?php \esc_html_e(
          'We use the WPScan database to provide security risk scores for plugins and themes.',
          'ionos-essentials'
        ); ?></li>
        <li><?php \esc_html_e(
          'The scores are on a scale of 1 to 10, where a higher value indicates greater security risk.',
          'ionos-essentials'
        ); ?></li>
        <li><?php \esc_html_e(
          'Installations of plugins and themes are prohibited if their score exceeds 7.0.',
          'ionos-essentials'
        ); ?></li>
      </ul>
    </section>
  </div>
</div>


<div class="static-overlay__container dialog-closer" id="restart-ai-sitebuilder-overlay">
  <div class="sheet static-overlay--closable static-overlay__content sheet--micro-effect" data-static-overlay-id="demo-overlay1" style="margin-top: inherit;">
    <section class="sheet__section">
      <div style="display: flex; justify-content: right;">
        <i class="exos-icon exos-icon-deleteinput-16 dialog-closer"></i>
      </div>
      <h3 class="headline headline--sub"><?php \esc_html_e('Restart AI Sitebuilder', 'ionos-essentials'); ?></h3>
      <p class="paragraph"><?php \esc_html_e(
        'This will permanently reset all content and data created by the AI Sitebuilder on this WordPress website.',
        'ionos-essentials'
      ) ?></p>
      <p class="paragraph"><?php \esc_html_e(
        'Pages initially created without the use of the AI Sitebuilder will be kept.',
        'ionos-essentials'
      ) ?></p>

      <?php
      include_once ABSPATH . 'wp-admin/includes/plugin.php';

// Check theme and plugins
$theme     = (get_stylesheet() === 'extendable');
$extendify = _is_plugin_active('extendify/extendify.php');

// Detect ANY plugin that starts with "01-ext-"
$match = array_filter(array_keys(\get_plugins()), fn ($k) => str_starts_with($k, '01-ext-'));

$ext01_plugin_path = reset($match);
$ext01             = $ext01_plugin_path && _is_plugin_active($ext01_plugin_path);

// Build array of missing items
$missing_items = [];

if (! $theme) {
  $missing_items[] = [
    'title'     => __('The Extendable theme is not installed or set as the active theme. You need to do this before you can continue.', 'ionos-essentials'),
    'url'       => \admin_url('theme-install.php?theme=extendable'),
    'link_text' => __('Manage themes', 'ionos-essentials'),
  ];
}

if (! $extendify) {
  $missing_items[] = [
    'title'     => __('The Extendify plugin is not installed or activated. You need to do this before you can continue.', 'ionos-essentials'),
    'url'       => \admin_url('plugin-install.php?s=extendify&tab=search&type=term'),
    'link_text' => __('Manage plugins', 'ionos-essentials'),
  ];
}

if (! $ext01) {
  $missing_items[] = [
    'title'     => __('The Site-Assistant plugin is not activated. You need to do this before you can continue.', 'ionos-essentials'),
    'url'       => \admin_url('plugins.php?s=site%20assistant&plugin_status=inactive'),
    'link_text' => __('Manage plugins', 'ionos-essentials'),
  ];
}

// Display output
if ($missing_items) { ?>
      <ul class="bullet-list">
        <?php foreach ($missing_items as $item) { ?>
            <li>
                <p><strong><?php echo \esc_html($item['title']); ?></strong></p>
                <a href="<?php echo \esc_url($item['url']); ?>" class="link">
                    <?php echo \esc_html($item['link_text']); ?>
                </a>
            </li>
        <?php } ?>
          </ul>
      <?php } else { ?>
        <div>
          <a href="<?php echo \admin_url(
            'admin.php?page=extendify-launch'
          ); ?>" class="button button--primary dialog-closer" title="<?php \esc_html_e(
            'Restart AI Sitebuilder',
            'ionos-essentials'
          ); ?>"><?php \esc_html_e('Restart AI Sitebuilder', 'ionos-essentials'); ?></a>
          <button class="button button--secondary dialog-closer" title="<?php \esc_html_e('Cancel', 'ionos-essentials'); ?>"><?php \esc_html_e('Cancel', 'ionos-essentials'); ?></button>
        </div>
      <?php } ?>
    </section>
  </div>
</div>



<div class="static-overlay__container dialog-closer" id="plugin-install-overlay">
  Showing update information
</div>

<main id="content" class="page-section <?php
  \ionos\essentials\maintenance_mode\is_maintenance_mode()   && printf('ionos-maintenance-mode');
! empty(\ionos\essentials\wpscan\get_wpscan()->get_issues()) && printf(' issues-found');
?>">
<div class="page-section ionos-dashboard__blocks">
  <div class="page-section">
  </div>

  <?php
  require_once __DIR__ . '/tabs/overview.php';
require_once __DIR__ . '/tabs/tools.php';
?>
</div>

</main>
    </div>

  <script>
    document.addEventListener('DOMContentLoaded', function() {
      document.querySelector('.ionos-tab').style.display = 'block';
    });

     <?php

       $params = [
    'token'       => \get_transient('ionos_adserver_token') ?: 'adserver_default_token',
    'zoneid'      => \wp_get_environment_type() !== 'local' ? 'wp_admin_overview_card_left' : 'developers_docs_example',
    'visitorData' => [
      'beyondseo'            => \is_plugin_active('ionos-essentials/ionos-essentials.php') ? true : false,
      'market'               => \get_option('ionos_market', 'not set'),
      'ssl_type'             => \ionos\essentials\get_ssl_type(),
    ],
    'c' => [
      'language'  => \get_bloginfo('language') ? str_replace('-', '_', \get_bloginfo('language')) : 'de_DE',
    ],
    'nonce'       => \wp_create_nonce('wp_rest'),
    'proxyUrl'    => \rest_url('ionos/essentials/adzone/v1/proxy'),
  ];

    echo 'const params = ' . json_encode($params) . ';'
  ?>
  </script>

   <script>
      // Proxy fetch requests to the IONOS AdServer to avoid CORS issues
      const originalFetch = window.fetch;

      window.fetch = async (url, options) => {
        //return originalFetch(url, options);

        if (typeof url === 'string' && url.includes('/ias/zones/json')) {

          return originalFetch(params.proxyUrl, {
            method: 'POST',
            credentials: 'include',
            headers: { 'Content-Type': 'application/json', 'X-WP-Nonce': params.nonce || '' },
            body: options?.body,
          });
        }
        return originalFetch(url, options);
      };
    </script>

    <script type="text/javascript">
      var OAO = OAO || {}; // OAO refers to the global OAO variable.
      OAO.q = OAO.q || {};
      OAO.q.c = OAO.q.c || [];
      OAO.q.ias = OAO.q.ias || [];
      document.querySelector('.ias-zone').setAttribute('data-ias-zoneid', params.zoneid || '');
      OAO.q.c.push(['setFrontendToken', params.token || 'adserver_default_token']);
      OAO.q.c.push(['setPageName', 'Dashboard']); // please use: [a-zA-Z_]

      OAO.q.ias.push(['addVisitorData', 'beyondseo', params.visitorData.beyondseo || false]);
      OAO.q.ias.push(['addVisitorData', 'market', params.visitorData.market || 'not set']);
      OAO.q.ias.push(['addVisitorData', 'ssl_type', params.visitorData.ssl_type || '']);

      OAO.q.c.push(['setLanguage', params.c.language || 'de_DE']);


    </script>
    <script
      id="oaotag"
      type="text/javascript"
      src="https://frontend-services.ionos.com/t/tag/IONOS/wp-admin.js"
      async="async"
      defer="defer"></script>
</body>
</html>
