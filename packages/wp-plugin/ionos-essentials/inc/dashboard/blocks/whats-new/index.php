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
  if (strtolower(\get_option('ionos_group_brand', 'ionos')) === 'ionos') {
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
  'Get an overview of the latest updates and enhancements.',
  'ionos-essentials'
) .
'</p>
<ul class="wp-block-list">
<li class="has-small-font-size">
  <b>'. \esc_html__('Your Feedback is important to us', 'ionos-essentials') . '</b>
  <p>' . \esc_html__(
    'We\'re always looking for ways to make your WordPress hosting experience even better. Please take a few minutes to fill out a quick online survey.',
    'ionos-essentials'
  ) . '</p>

</li>
<div class="wp-block-button">
  <button id="ionos_essentials_install_security" class="wp-block-button__link wp-element-button">
  '. \esc_html__('Take the survey', 'ionos-essentials') .
  '</button>
  </div>
</ul>
</content>
</div>';}

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
  'Get an overview of the latest updates and enhancements.',
  'ionos-essentials'
) .
'</p>
<ul class="wp-block-list">
<li class="has-small-font-size">
  <b>' . \esc_html__('New User Interface', 'ionos-essentials') . '</b>
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
