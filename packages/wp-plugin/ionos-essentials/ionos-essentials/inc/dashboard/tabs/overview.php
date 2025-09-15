<?php

namespace ionos\essentials\dashboard;

defined('ABSPATH') || exit();
?>

<div id="overview" class="page-section ionos-tab">
  <div class="grid">
    <!--
      There must be no whitespace or newline within the .grid-col elements, as this would display even an empty cell,
      but empty cells should not be displayed (see)
    -->
    <div class="grid-col grid-col--12"><?php blocks\site_health\render_callback(); ?></div>

    <div class="grid-col grid-col--8"><?php blocks\next_best_actions\render_callback(); ?></div>
    <div class="grid-col grid-col--4">
      <?php blocks\quick_links\render_callback(); ?>
      <?php blocks\my_account\render_callback(); ?>
    </div>
  </div>
</div>
