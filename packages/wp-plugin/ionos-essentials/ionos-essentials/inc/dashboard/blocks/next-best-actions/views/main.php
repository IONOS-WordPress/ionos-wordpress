<?php

namespace ionos\essentials\dashboard\blocks\next_best_actions;

defined('ABSPATH') || exit();
use const ionos\essentials\PLUGIN_DIR;

function main_view($args): void
{
  $category_to_show = $args['category_to_show'] ?? 'after-setup';
  $actions          = $args['actions'] ?? [];
  $always_actions   = $args['always_actions'] ?? [];
  $show_setup_actions = $args['show_setup_actions'] ?? false;
  $completed_actions = $args['completed_actions'] ?? 0;
  $total_actions     = $args['total_actions'] ?? 0;

  $cards         = '';
  $card_template = '<div id="%s" class="grid-col grid-col--12 grid-col--medium-6 grid-col--small-12 %s">
  <div class="card nba-card">
    <div class="card__content">
      <section class="card__section">
        <h2 class="headline headline--sub">
          <span class="nba-is-active">DONE </span>
          %s
        </h2>
        <p class="paragraph">%s</p>
        <div>%s</div>
      </section>
    </div>
  </div>
</div>';

  foreach ($actions as $action) {
    $target = false === strpos(\esc_url($action->link), home_url()) ? '_blank' : '_top';
    if ('#' === $action->link) {
      $target = '';
    }

    $buttons = sprintf(
      '<a data-nba-id="%s" href="%s" class="button button--secondary" target="%s" data-dismiss-on-click="%s">%s</a>',
      $action->id,
      \esc_url($action->link),
      $target,
      $action->dismiss_on_click ? 'true' : 'false',
      \esc_html($action->anchor)
    );

    // Overwrite cta button for GML installation
    if ('woocommerce-gml' === $action->id) {
      $buttons = '<a id="ionos_essentials_install_gml" class="button button--secondary">' . $action->anchor . '</a>';
    }

    $buttons .= '<a data-nba-id="' . $action->id . '" class="ghost-button ionos-dismiss-nba">' . \esc_html__(
      'Dismiss',
      'ionos-essentials'
    ) . '</a>';

    $cards .= \sprintf(
      $card_template,
      \esc_attr($action->id),
      \esc_attr($action->active ? 'nba-active' : 'nba-inactive'),
      \esc_html($action->title),
      \esc_html($action->description),
      $buttons
    );

  }

  printf('<h2 class="headline">%s</h2>', esc_html__("What's important for today", 'ionos-essentials'));
  ?>

      <div class="card ionos_next_best_actions">
        <div class="card__content">
          <section class="card__section ionos_next_best_actions__section">

            <?php if ($show_setup_actions): ?>
            <div class="headline"><?php \esc_html_e('🚀  Getting started with WordPress', 'ionos-essentials'); ?></div>
            <div class="paragraph"><?php \esc_html_e(
              'Ready to establish your online presence? Let\'s get the essentials sorted so your new site looks professional and is easy for people to find.',
              'ionos-essentials'
            ); ?></div>
            <?php endif; ?>
            <?php if ($show_setup_actions): ?>
              <div style="width: 200px">
              <div class="quotabar">
                <div class="quotabar__bar quotabar__bar--small">
                  <span class="quotabar__value" style="width: <?php \esc_attr($completed_actions / $total_actions * 100); ?>%;"></span>
                </div>
                  <p class="quotabar__text">
                    <?php
                      if ($completed_actions === $total_actions) {
                        \esc_html_e('All actions completed!', 'ionos-essentials');
                      } else {
                        // translators: 1: number of completed actions, 2: total number of actions
                        printf(__(' %1$d of %2$d completed', 'ionos-essentials'), $completed_actions, $total_actions);
                      }
  ?>
                </p>
              </div>
            </div>
            <?php endif; ?>


            <div class="grid nba-category-<?php echo \esc_attr($category_to_show);
  if ($show_setup_actions) {
    echo ' nba-setup';
  } ?>">
                <?php echo wp_kses($cards, 'post'); ?>
            </div>

            <?php
  if ($show_setup_actions) {
    if ($completed_actions === $total_actions) {
      // all done, show dismiss button
      printf(
        '<button class="button button-primary ionos_finish_setup" data-status="finished">%s</button>',
        \esc_html__('Finish setup', 'ionos-essentials')
      );
    } else {
      printf(
        '<button class="button ghost-button ionos_finish_setup" data-status="dismissed">%s</button>',
        \esc_html__('Dismiss getting started guide', 'ionos-essentials')
      );
    }
  }
  ?>
          </section>

  <?php

if ($show_setup_actions) {
  $always_cards = '';
  foreach ($always_actions as $action) {
    $target  ='_self';
    $buttons = sprintf(
      '<a data-nba-id="%s" href="%s" class="button button--secondary" target="%s" data-dismiss-on-click="%s">%s</a>',
      $action->id,
      \esc_url($action->link),
      $target,
      $action->dismiss_on_click ? 'true' : 'false',
      \esc_html($action->anchor)
    );
    $buttons .= '<a data-nba-id="' . $action->id . '" class="ghost-button ionos-dismiss-nba">' . \esc_html__(
      'Dismiss',
      'ionos-essentials'
    ) . '</a>';

    $always_cards .= \sprintf(
      $card_template,
      \esc_attr($action->id),
      \esc_attr($action->active ? 'nba-active' : 'nba-inactive'),
      \esc_html($action->title),
      \esc_html($action->description),
      $buttons
    );
  }
  echo $always_cards;
}

  echo '</div></div>';
}
