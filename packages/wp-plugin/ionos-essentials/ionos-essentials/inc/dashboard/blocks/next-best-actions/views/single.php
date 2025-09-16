<?php

namespace ionos\essentials\dashboard\blocks\next_best_actions;

defined('ABSPATH') || exit();

function single_view($action): void
{
  ?>

<li id="<?php echo \esc_attr(
  $action->id
); ?>" class="panel__item panel__item--closed <?php echo \esc_attr($action->active ? 'nba-active' : 'nba-inactive'); ?>" aria-expanded="false">
  <header class="panel__item-header">
    <div class="panel__icon">
      <?php if(! $action->active) { ?>
      <svg class="icon-done" xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24"><g fill="none" fill-rule="evenodd"><polygon points="0 0 24 0 24 24 0 24"></polygon>
        <path fill="#4caf50" d="M12,2 C17.52,2 22,6.48 22,12 C22,17.52 17.52,22 12,22 C6.48,22 2,17.52 2,12 C2,6.48 6.48,2 12,2 Z M15.6,8 L10.47,13.17 L8.4,11.09 L7,12.5 L10.47,16 L10.47,16 L17,9.41 L15.6,8 Z"></path></g>
      </svg>
      <?php } elseif ($action->active) { ?>
        <svg class="icon-todo" xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24">
          <g fill="none" fill-rule="evenodd">
            <polygon points="0 0 24 0 24 24 0 24"></polygon>
            <path fill="#b0bec5" d="M10.47,16 L7,12.5 L8.4,11.09 L10.47,13.17 L15.6,8 L17,9.41 L10.47,16 L10.47,16 Z M12,2 C6.48,2 2,6.48 2,12 C2,17.52 6.48,22 12,22 C17.52,22 22,17.52 22,12 C22,6.48 17.52,2 12,2 Z M12,20 C7.58,20 4,16.42 4,12 C4,7.58 7.58,4 12,4 C16.42,4 20,7.58 20,12 C20,16.42 16.42,20 12,20 Z"></path>
          </g>
        </svg>
      <?php } ?>
    </div>
    <div class="panel__headline__container">
      <h3 class="panel__headline">
        <?php echo \esc_html($action->title); ?>
      </h3>
    </div>
  </header>
  <section class="panel__item-section">
    <p class="paragraph"><?php echo \esc_html($action->description); ?></p>
    <div><?php echo \wp_kses(create_buttons($action, 'no-dismiss'), 'post'); ?></div>
  </section>
</li>

<?php
}
