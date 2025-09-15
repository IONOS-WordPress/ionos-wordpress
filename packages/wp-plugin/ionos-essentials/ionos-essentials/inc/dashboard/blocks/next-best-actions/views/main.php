<?php

namespace ionos\essentials\dashboard\blocks\next_best_actions;

defined('ABSPATH') || exit();

function main_view($args): void
{
  $category_to_show   = $args['category_to_show']   ?? 'after-setup';
  $actions            = $args['actions']            ?? [];
  $always_actions     = $args['always_actions']     ?? [];
  $show_setup_actions = $args['show_setup_actions'] ?? false;
  $completed_actions  = $args['completed_actions']  ?? 0;
  $total_actions      = $args['total_actions']      ?? 0;

  ?>
  <h2 class="headline">
      <?php esc_html_e("What's important for today", 'ionos-essentials'); ?></h2>
      <div class="card ionos_next_best_actions">
        <div class="card__content">
          <section class="card__section ionos_next_best_actions__section">
            <?php if ($show_setup_actions) {
              render_setup_header($args);
            }
  ?>
            <div class="grid nba-category-<?php echo \esc_attr($category_to_show); ?> <?php echo ($show_setup_actions) ? 'nba-setup' : ''; ?>">
              <?php
      foreach ($actions as $action) {
        single_view($action);
      }
  ?>
            </div>

            <?php
  if ($show_setup_actions) {
    render_setup_footer($args);
  }
  ?>
          </section>

          <?php
  if ($show_setup_actions) {
    foreach ($always_actions as $action) {
      single_view($action);
    }
  }
  ?>
        </div>
      </div>
  <?php
}
