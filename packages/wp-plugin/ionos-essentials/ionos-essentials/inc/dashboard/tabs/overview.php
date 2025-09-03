<?php

namespace ionos\essentials\dashboard;

defined('ABSPATH') || exit();
?>

<div id="overview" class="page-section ionos-tab">
  <div class="grid grid--full-height">
    <!--
      There must be no whitespace or newline within the .grid-col elements, as this would display even an empty cell,
      but empty cells should not be displayed (see)
    -->
    <div class="grid-col grid-col--12"><?php blocks\site_health\render_callback(); ?></div>
    <div class="grid-col grid-col--12"><?php blocks\next_best_actions\render_callback(); ?></div>
    <div class="grid-col grid-col--4 grid-col--small-12"><?php blocks\vulnerability\render_callback(); ?></div>
    <div class="grid-col grid-col--8 grid-col--small-12"><?php blocks\quick_links\render_callback(); ?></div>
    <div class="grid-col grid-col--7 grid-col--medium-6 grid-col--small-12"><?php blocks\my_account\render_callback(); ?></div>
    <div class="grid-col grid-col--5 grid-col--medium-6 grid-col--small-12"><?php blocks\whatsnew\render_callback(); ?></div>
  </div>
</div>
