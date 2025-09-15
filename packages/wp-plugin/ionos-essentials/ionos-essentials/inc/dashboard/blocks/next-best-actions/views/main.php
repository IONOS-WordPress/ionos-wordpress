<?php

namespace ionos\essentials\dashboard\blocks\next_best_actions;

defined('ABSPATH') || exit();

function main_view($args): void
{
  ?>
  <h2 class="headline">
      <?php esc_html_e("What's important for today", 'ionos-essentials'); ?></h2>
      <div class="card ionos_next_best_actions">
        <div class="card__content">
          <section class="card__section ionos_next_best_actions__section">
            <?php if ($args['show_setup_actions']) {
              setup_view($args);
            } else {
              after_setup_view($args);
            }
            ?>
          </section>
        </div>
      </div>
  <?php
}
