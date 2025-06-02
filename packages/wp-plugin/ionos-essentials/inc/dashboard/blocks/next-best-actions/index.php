<?php

namespace ionos\essentials\dashboard\blocks\next_best_actions;

function render_callback()
{
  require_once __DIR__ . '/class-nba.php';
  $actions = NBA::get_actions();
  if (empty($actions) || array_all($actions, fn (NBA $action) => ! $action->active)) {
    return;
  }

  $cards         = '';
  $card_template = '<div id="%s" class="grid-col grid-col--4 grid-col--medium-6 grid-col--small-12">
  <div class="card nba-card">
    <div class="card__content">
      <section class="card__section">
        <h2 class="card__headline">%s</h2>
        <p class="paragraph">%s</p>
        %s
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
      '<a data-nba-id="%s" href="%s" class="button button--primary" target="%s">%s</a>',
      $action->id,
      \esc_url($action->link),
      $target,
      \esc_html($action->anchor)
    );

    // Overwrite cta button for GML installation
    if ('woocommerce-gml' === $action->id) {
      $buttons = '<a id="ionos_essentials_install_gml" class="button button--primary">' . $action->anchor . '</a>';
    }

    $buttons .= '<a data-nba-id="' . $action->id . '" class="button button--secondary ionos-dismiss-nba">' . \esc_html__(
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
  <div class="grid ionos_next_best_actions">
      <div class="grid-col grid-col--12">
        <div class="headline"><?php echo \esc_html__("Unlock Your Website's Potential", 'ionos-essentials'); ?></div>
        <div class="headline headline--sub"><?php echo \esc_html__(
          'Your website is live, but your journey is just beginning. Explore the recommended next actions to drive growth, improve performance, and achieve your online goals.',
          'ionos-essentials'
        ); ?></div>
      </div>
      <?php echo $cards; ?>
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
