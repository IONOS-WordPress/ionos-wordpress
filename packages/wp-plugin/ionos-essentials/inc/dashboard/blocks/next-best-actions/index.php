<?php

namespace ionos\essentials\dashboard\blocks\next_best_actions;

defined('ABSPATH') || exit();

function render_callback(): void
{
  require_once __DIR__ . '/class-nba.php';
  $actions = NBA::get_actions();
  if (empty($actions) || count(array_filter($actions, fn (NBA $action) => $action->active)) === 0) {
    return;
  }

  $cards         = '';
  $card_template = '<div id="%s" class="grid-col grid-col--4 grid-col--medium-6 grid-col--small-12">
  <div class="card nba-card">
    <div class="card__content">
      <section class="card__section">
        <h2 class="headline headline--sub">%s</h2>
        <p class="paragraph">%s</p>
        <div>%s</div>
      </section>
    </div>
  </div>
</div>';

  foreach ($actions as $action) {
    if (! $action->active) {
      continue;
    }

    $target = false === strpos(\esc_url($action->link), home_url()) ? '_blank' : '_top';
    if ('#' === $action->link) {
      $target = '';
    }

    $buttons = sprintf(
      '<a data-nba-id="%s" href="%s" class="button button--secondary" target="%s">%s</a>',
      $action->id,
      \esc_url($action->link),
      $target,
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
      \esc_html($action->title),
      \esc_html($action->description),
      $buttons
    );

  }

  ?>
      <div class="card ionos_next_best_actions">
        <div class="card__content">
          <section class="card__section ionos_next_best_actions__section">
            <div class="headline"><?php \esc_html_e("Unlock Your Website's Potential", 'ionos-essentials'); ?></div>
            <div class="paragraph"><?php \esc_html_e(
              'Your website is live, but your journey is just beginning. Explore the recommended next actions to drive growth, improve performance, and achieve your online goals.',
              'ionos-essentials'
            ); ?></div>

            <div class="grid">
              <?php echo \wp_kses($cards, 'post'); ?>
            </div>
          </section>
        </div>
      </div>
  <?php
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
