<?php

namespace ionos\essentials\dashboard;

require_once __DIR__ . '/blocks/banner/index.php';
require_once __DIR__ . '/blocks/welcome/index.php';
require_once __DIR__ . '/blocks/vulnerability/index.php';
require_once __DIR__ . '/blocks/next-best-actions/index.php';
require_once __DIR__ . '/blocks/my-account/index.php';
require_once __DIR__ . '/blocks/whats-new/index.php';
require_once __DIR__ . '/blocks/quick-links/index.php';

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

<script src="https://ce1.uicdn.net/exos/framework/3.0/exos.min.js" async="async" defer="defer"></script>
<link rel="stylesheet" href="https://ce1.uicdn.net/exos/framework/3.0/exos.min.css" />

 <?php echo blocks\welcome\render_callback(); ?>

<main id="content">
  <div class="page-section">
  <?php blocks\banner\render_callback(); ?>
  </div>

<div class="page-section">
  <div class="grid grid--full-height">
    <div class="grid-col grid-col--12 ionos_next_best_actions">
      <?php blocks\next_best_actions\render_callback(); ?>
    </div>
    <div class="grid-col grid-col--6 grid-col--small-12">
      <?php blocks\vulnerability\render_callback(); ?>
    </div>
    <div class="grid-col grid-col--6 grid-col--small-12">
      <?php blocks\quick_links\render_callback(); ?>
    </div>
    <div class="grid-col grid-col--5 grid-col--medium-6 grid-col--small-12">
      <?php blocks\my_account\render_callback(); ?>
    </div>
     <div class="grid-col grid-col--7 grid-col--medium-6 grid-col--small-12">
      <?php blocks\whatsnew\render_callback(); ?>
    </div>
  </div>
</div>


<style>
  .button{
    white-space: nowrap;
    margin-bottom: 5px;
  }

  .ionos_my_account_links{
    display: flex;
    flex-wrap: wrap;

    .button{
      flex: 0 0 31%;
    }
  }

  #essentials-welcome_block{
    width: 100%;
    height: 100%;
    z-index: 1;
    background: rgba(255, 255, 255, 0.95);
    border: 0;

    .dialog__content{
      display: flex;
      justify-content: center;
      margin-top: 10%;
      height: 100%;
    }

    .horizontal-card{
      width: 90%;
      max-width: 800px;
      height: 400px;
      display: flex;
      border: 2px solid black;
    }
  }


  .ionos_next_best_actions{
    background: white;
    margin-bottom: 2em;
    border-radius: 16px;
    padding-top: 2em;
    margin-left: 15px;

    .card{
      background: rgb(244, 247, 250);
    }
    & > *:nth-child(n+8) {
      display: none;
    }

    .nba-card .card__section{
      height: 100%;
      display: flex;
      flex-direction: column;
      align-items: flex-start;
      justify-content: space-between;
    }

    .ionos_nba_dismissed{
      transform: scale(0.7);
      opacity: 0;
      transition: all 0.75s ease-in-out;
    }
  }

  .ionos_vulnerability{
    background-color: #edfaef;

    .ionos_vulnerability__content{
      display: flex;
      flex-direction: column;
      justify-content: space-between;
      background-image: url(<?php echo plugins_url('/ionos-security/assets/img/none.svg'); ?>);
      background-repeat: no-repeat;
      background-position: top right;
      min-height: 150px;
    }

    .bubble{
      background-color: #4ab866;
      color: #000;
      border-radius: 50%;
      padding: 0.2em 0.5em;
      font-size: 0.8em;
      margin-right: 0.5em;
    }

    /* Respect the order of this definition, as high overwrites medium */
    &.medium{
      background-color: #fcf9e8;
      .ionos_vulnerability__content{
        background-image: url(<?php echo plugins_url('/ionos-security/assets/img/medium.svg'); ?>);
      }
    }
    &.high{
      background-color: #fcf0f1;
      .ionos_vulnerability__content{
        background-image: url(<?php echo plugins_url('/ionos-security/assets/img/high.svg'); ?>);
      }
    }

    .issue-row.high .bubble{
      background-color: #cc1818;
      color: white;
    }
    .issue-row.medium .bubble{
      background-color: #f0b849;
    }
  }


</style>
</main>
</template>
