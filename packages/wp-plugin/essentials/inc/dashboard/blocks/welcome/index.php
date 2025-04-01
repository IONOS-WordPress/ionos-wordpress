<?php

namespace ionos_wordpress\essentials\dashboard\blocks\welcome;

use const ionos_wordpress\essentials\PLUGIN_DIR;

\add_action('init', function () {
  \register_block_type(
    PLUGIN_DIR . '/build/dashboard/blocks/welcome',
    [
      'render_callback' => __NAMESPACE__ . '\\render_callback',
    ]
  );
});

const NONCE         = 'ionos_essentials_welcome_nonce';
const USER_META_KEY = 'ionos_essentials_welcome_4';
function render_callback(): string
{
  $user_meta = \get_user_meta(user_id: \get_current_user_id(), key: USER_META_KEY, single: true);

  if (! empty($user_meta)) {
    return '';
  }

  $brand_name         = \get_option('ionos_group_brand_menu', 'IONOS');
  $welcome_banner_url = \plugins_url('data/tenant-logos/welcome-banner.jpg', \dirname(__DIR__, 1));
  $nonce              = \wp_create_nonce(NONCE);

  return '
<dialog id="essentials-welcome_block">
    <div class="horizontal-card">
        <header class="horizontal-card__header">
            <img class="horizontal-card__visual" src="' . esc_url($welcome_banner_url) . '" alt="Welcome Banner">
        </header>
        <div class="horizontal-card__content">
            <section class="horizontal-card__section">
                <h2 class="horizontal-card__headline">
                    ' .
                    // translators: %s: Brand name
                    sprintf( \esc_html__('Welcome to your %s Hub', 'ionos-essentials'), $brand_name ) . '
                </h2>
                <p class="paragraph">
                    ' . \esc_html__('This overview is your gateway to unlocking the full potential of your WordPress website.', 'ionos-essentials') . '
                </p>
            </section>
            <section class="horizontal-card__section">
                <ul class="check-list">
                    <li>' . \esc_html__('Recommendations for next steps', 'ionos-essentials') . '</li>
                    <li>' . \esc_html__('Helpful links and shortcuts', 'ionos-essentials') . '</li>
                    <li>' . \esc_html__('Comprehensive help section, including: AI chat support, Guided tours and an extensive knowledge database', 'ionos-essentials') . '</li>
                </ul>
            </section>
            <section class="horizontal-card__section">
                <div class="paragraph">
                    ' . \esc_html__('Stay tuned for exciting new features and updates to come!', 'ionos-essentials') . '
                </div>
            </section>
        </div>
    </div>
    <footer class="horizontal-card__footer horizontal-card__footer--small-align-center">
        <button class="button button--primary" nonce="' . esc_html($nonce) . '" autofocus>
            ' . \esc_html__('Close', 'ionos-essentials') . '
        </button>
    </footer>
</dialog>';
}
\add_action('rest_api_init', function () {
  \register_rest_route(
    'ionos/essentials/dashboard/welcome/v1',
    '/closer',
    [
      'methods'             => 'POST',
      'permission_callback' => fn () => \current_user_can('manage_options'),
      'callback'            => function (\WP_REST_Request $req) {
        $body = \json_decode($req->get_body());

        if (empty($body->nonce)) {
          return rest_ensure_response(new \WP_REST_Response([
            'error' => 'nonce not set',
          ], 400));
        }

        if (! \wp_verify_nonce($body->nonce, NONCE)) {
          return rest_ensure_response(new \WP_REST_Response([
            'error' => 'nonce not valid',
          ], 400));
        }

        $meta = \update_user_meta(\get_current_user_id(), USER_META_KEY, true);

        if (false === $meta) {
          return rest_ensure_response(new \WP_REST_Response([
            'error' => 'failed to update user meta',
          ], 500));
        }

        return rest_ensure_response(new \WP_REST_Response([
          'status' => $meta,
        ], 200));
      },
    ]
  );
});
