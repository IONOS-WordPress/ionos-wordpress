<?php

namespace ionos\essentials\dashboard\blocks\whatsnew;

use const ionos\essentials\PLUGIN_DIR;

\add_action('init', function () {
  \register_block_type(
    PLUGIN_DIR . '/build/dashboard/blocks/whats-new',
    [
      'render_callback' => __NAMESPACE__ . '\\render_callback',
    ]
  );
});

function render_callback()
{
  return '
 <div class="whats-new">
<header>
<img src="' . \esc_url(\plugins_url('', __FILE__) . '/whats-new.png') . '" alt="' . \esc_attr__(
    'What’s New'
  ) . '" class="wp-block-image" />
<h3 class="wp-block-heading">' .
  \esc_html__('What’s New', 'ionos-essentials') .
'</h3>
</header>
<content>
<p>' . \esc_html__(
  'Discover new features and get an overview of the latest updates and enhancements of your WordPress Hub',
  'ionos-essentials'
) .
'</p>
<ul class="wp-block-list">
<li class="has-small-font-size">
  <b>' . \esc_html__('Introducing the new WordPress Hub', 'ionos-essentials') . '</b>
  <br>' . \esc_html__(
  'Featuring a completely new design and an improved user experience, making managing your website easier than ever.',
  'ionos-essentials'
) . '
</li>
<li class="has-small-font-size">
  <b>' . \esc_html__('Vulnerability Scan Results', 'ionos-essentials') . '</b>
  <br>' . \esc_html__(
  "We've added scan results directly to your dashboard, giving you instant visibility into potential security risks.",
  'ionos-essentials'
) . '
</li>
</ul>
</content>
</div>';
}
