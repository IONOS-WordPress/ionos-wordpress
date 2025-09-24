<?php

namespace ionos\essentials\dashboard\blocks\next_best_actions;

defined('ABSPATH') || exit();
use const ionos\essentials\PLUGIN_FILE;

function single_always_action_view($action): void
{
  $is_always_open = in_array($action->id, ['tools-and-security', 'survey']);
  ?>

<div id="<?php echo \esc_attr(
  $action->id
); ?>" class="panel__item  <?php echo $is_always_open ? 'panel__item--expanded' : 'panel__item--closed'; ?>"  <?php echo \esc_attr($action->active ? 'nba-active' : 'nba-inactive'); ?>" aria-expanded="<?php echo $is_always_open ? 'true' : 'false'; ?>">
  <header class="panel__item-header">
    <div class="panel__icon">
     <img src="<?php echo esc_url( plugins_url(
  '/ionos-essentials/inc/dashboard/assets/' . $action->exos_icon . '.svg',
  PLUGIN_FILE
)); ?>" alt="Icon" width="30" height="30">

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
