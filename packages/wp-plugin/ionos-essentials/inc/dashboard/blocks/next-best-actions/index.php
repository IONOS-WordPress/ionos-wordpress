<?php

namespace ionos\essentials\dashboard\blocks\next_best_actions;

use ionos\essentials\dashboard\Silent_Skin;

require_once ABSPATH . 'wp-admin/includes/plugin.php';
require_once ABSPATH . 'wp-admin/includes/class-wp-upgrader.php';
require_once ABSPATH . 'wp-admin/includes/plugin-install.php';
require_once ABSPATH . 'wp-admin/includes/file.php';
require_once ABSPATH . 'wp-admin/includes/misc.php';

use const ionos\essentials\PLUGIN_DIR;

function render_callback()
{
  require_once __DIR__ . '/class-nba.php';
  $actions = NBA::get_actions();
  if (empty($actions) || array_all($actions, fn (NBA $action) => ! $action->active)) {
    return;
  }

  $cards         = '';
  $card_template = '<div class="grid-col grid-col--4 grid-col--medium-6 grid-col--small-12">
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

    $buttons .= '<a data-nba-id="' . $action->id . '" class="button button--secondary">' . \esc_html__(
      'Dismiss',
      'ionos-essentials'
    ) . '</a>';

    $cards .= \sprintf($card_template, \esc_html($action->title), \esc_html($action->description), $buttons);

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
  <script>
    document.querySelector("#ionos_essentials_install_gml")?.addEventListener("click", function(event) {
    event.target.disabled = true;
    event.target.innerText = "<?php echo \esc_js(__('Installing...', 'ionos-essentials')); ?>";

    fetch("<?php echo get_rest_url(null, '/ionos/essentials/dashboard/nba/v1/install-gml'); ?>", {
      method: "GET",
      credentials: "include",
      headers: {
        "Content-Type": "application/json",
        "X-WP-Nonce": "<?php echo \wp_create_nonce('wp_rest'); ?>"
      }
    })
    .then(response => response.json())
    .then(data => {
      if (data.status === "success") {
        location.reload();
      } else {
        console.error("Failed to install the GML plugin.");
      }
    })
    .catch(error => console.error("Error:", error));
  });
  </script>
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

\add_action('rest_api_init', function () {
  \register_rest_route('ionos/essentials/dashboard/nba/v1', '/dismiss/(?P<id>[a-zA-Z0-9-]+)', [
    'methods'  => 'POST',
    'callback' => function ($request) {
      require_once __DIR__ . '/class-nba.php';
      $params = $request->get_params();
      $nba_id = $params['id'];

      $nba = NBA::get_nba($nba_id);
      $res = $nba->set_status('dismissed', true);
      if ($res) {
        return new \WP_REST_Response([
          'status' => 'success',
          'res'    => $res,
        ], 200);
      }
      return new \WP_REST_Response([
        'status' => 'error',
      ], 500);
    },
    'permission_callback' => function () {
      return \current_user_can('manage_options');
    },
  ]);

  \register_rest_route(
    'ionos/essentials/dashboard/nba/v1',
    'install-gml',
    [
      'methods'             => 'GET',
      'permission_callback' => function () {
        return \current_user_can('install_plugins');
      },
      'callback'            => function () {
        $plugin_slug = 'woocommerce-german-market-light/WooCommerce-German-Market-Light.php';
        if (! file_exists(WP_PLUGIN_DIR . '/' . $plugin_slug)) {
          if (! install_plugin_from_url(
            'https://marketpress.de/mp-download/no-key-woocommerce-german-market-light/woocommerce-german-market-light/1und1/'
          )) {
            return new \WP_REST_Response([
              'status' => 'error',
            ], 500);
          }
        }
        \activate_plugin($plugin_slug, '', false, true);

        return new \WP_REST_Response([
          'status' => 'success',
        ], 200);
      },
    ]
  );
});

function install_plugin_from_url($plugin_url)
{
  require_once PLUGIN_DIR . '/inc/dashboard/class-silent-skin.php';

  $skin     = new Silent_Skin();
  $upgrader = new \Plugin_Upgrader($skin);
  $result   = $upgrader->install($plugin_url);

  return ! is_wp_error($result);
}

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
