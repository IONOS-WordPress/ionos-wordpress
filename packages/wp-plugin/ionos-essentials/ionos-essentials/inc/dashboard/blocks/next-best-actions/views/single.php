<?php

namespace ionos\essentials\dashboard\blocks\next_best_actions;

defined('ABSPATH') || exit();

function single_view($action): void
{
?>

<div id="<?php echo $action->id; ?>" class="grid-col grid-col--12 grid-col--medium-6 grid-col--small-12 <?php echo esc_attr($action->active ? 'nba-active' : 'nba-inactive'); ?>">
  <div class="card nba-card">
    <div class="card__content">
      <section class="card__section">
        <h2 class="headline headline--sub">
          <span class="nba-is-active">DONE </span>
          <?php echo $action->title; ?>
        </h2>
        <p class="paragraph"><?php echo $action->description; ?></p>
        <div><?php echo create_buttons($action); ?></div>
      </section>
    </div>
  </div>
</div>

<?php
}
