<?php

namespace ionos\essentials\dashboard\blocks\next_best_actions;

defined('ABSPATH') || exit();

function render_callback(): void
{
  require_once __DIR__ . '/class-nba.php';

  if (! get_option('ionos_essentials_nba_setup_completed', false)) {
    $category_to_show = (\get_option('extendify_onboarding_completed', false) ? 'setup-ai' : 'setup-noai');
    $actions = array_filter(
      NBA::get_actions(),
      fn (NBA $action) => in_array($category_to_show, $action->categories, true)
    );
  }

  if (\get_option('ionos_essentials_nba_setup_completed', false)) {
    $category_to_show = 'misc';
    $actions          = array_filter(
      NBA::get_actions(),
      fn (NBA $action) => in_array($category_to_show, $action->categories, true) && $action->active
    );
  }

  if (empty($actions)) {
    render_empty_element();
    return;
  }

  $completed_actions = count(array_filter($actions, fn ($action) => $action->active === false));
  $total_actions     = count($actions);

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
            <div class="headline"><?php \esc_html_e('🚀  Getting started with WordPress', 'ionos-essentials'); ?></div>
            <div class="paragraph"><?php \esc_html_e(
              'Ready to establish your online presence? Let\'s get the essentials sorted so your new site looks professional and is easy for people to find.',
              'ionos-essentials'
            ); ?></div>

            <?php if (\str_starts_with($category_to_show, 'setup')): ?>
              <div style="width: 200px">
              <div class="quotabar">
                <div class="quotabar__bar quotabar__bar--small">
                  <span class="quotabar__value" style="width: <?php echo $completed_actions / $total_actions * 100; ?>%;"></span>
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
  if (\str_starts_with($category_to_show, 'setup')) {
    echo ' nba-setup';
  } ?>">
                <?php echo wp_kses( $cards, 'post' ); ?>
            </div>

            <?php
  if (\str_starts_with($category_to_show, 'setup')) {
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
        </div>
      </div>
  <?php
}

function render_empty_element(): void
{
  echo 'all done for now!';
}

\add_action('admin_init', function () {
  if (isset($_GET['complete_nba'])) {
    require_once __DIR__ . '/class-nba.php';
    $nba_id = $_GET['complete_nba'];

    $nba = NBA::get_nba($nba_id);
    $nba->set_status('completed', true);
  }
});

\add_action('post_updated', function ($post_id, $post_after, $post_before) {
  if ('publish' !== $post_before->post_status || ('publish' !== $post_after->post_status && 'draft' !== $post_after->post_status)) {
    return;
  }

  require_once __DIR__ . '/class-nba.php';
  switch ($post_after->post_type) {
    case 'post':
      $nba = NBA::get_nba('edit-post');
      break;
    case 'page':
      $nba = NBA::get_nba('edit-page');
      break;
    default:
      return;
  }

  if ($nba) {
    $nba->set_status('completed', true);
  }
}, 10, 3);
