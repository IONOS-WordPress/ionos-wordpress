<?php

namespace ionos\essentials\dashboard;

require_once __DIR__ . '/blocks/banner/index.php';
require_once __DIR__ . '/blocks/welcome/index.php';
require_once __DIR__ . '/blocks/vulnerability/index.php';
require_once __DIR__ . '/blocks/next-best-actions/index.php';
require_once __DIR__ . '/blocks/deep-links/index.php';
require_once __DIR__ . '/blocks/whats-new/index.php';
require_once __DIR__ . '/blocks/quick-links/index.php';

?>

<template shadowrootmode="open">

<script src="https://ce1.uicdn.net/exos/framework/3.0/exos.min.js" async="async" defer="defer"></script>
<link rel="stylesheet" href="https://ce1.uicdn.net/exos/framework/3.0/exos.min.css" />

<main id="content">
  <div class="page-section">
  <?php
    blocks\banner\render_callback();
// echo blocks\welcome\render_callback();

// echo blocks\next_best_actions\render_callback();
?>
  </div>

<div class="page-section">
  <div class="grid">
    <div class="grid-col grid-col--12 grid-col--small-12">
      <?php blocks\quick_links\render_callback(); ?>
    </div>
    <div class="grid-col grid-col--8 grid-col--small-12">
      <?php blocks\deep_links\render_callback(); ?>
    </div>
     <div class="grid-col grid-col--4 grid-col--small-12">
      <?php blocks\whatsnew\render_callback(); ?>
    </div>
  </div>
</div>

<style>
  .button{
    white-space: nowrap;
  }
  .mb-1{
    margin-bottom: 5px;
  }

  .button--with-icon svg {
    width: 1em;
    height: 1em;
    margin-right: 0.5em;
  }
</main>
</template>




