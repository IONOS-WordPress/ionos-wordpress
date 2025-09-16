<?php

namespace ionos\essentials\dashboard\blocks\next_best_actions;

defined('ABSPATH') || exit();

function single_always_action_view($action): void
{
  ?>

<div id="<?php echo \esc_attr(
  $action->id
); ?>" class="panel__item  panel__item--closed <?php echo \esc_attr($action->active ? 'nba-active' : 'nba-inactive'); ?>" aria-expanded="false">
  <header class="panel__item-header">
    <div class="panel__icon">
     <i class="exos-icon exos-icon-<?php echo esc_html($action->icon); ?>"></i>
    </div>
    <div class="panel__headline__container">
      <h3 class="panel__headline">
        <?php echo \esc_html($action->title); ?>
      </h3>
    </div>
  </header>
  <section class="panel__item-section">
    <p class="paragraph"><?php echo \esc_html($action->description); ?></p>
    <div><?php echo \wp_kses(create_buttons($action), 'post'); ?></div>
  </section>
</div>

<?php
}
