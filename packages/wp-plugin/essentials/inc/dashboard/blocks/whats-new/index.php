<?php

namespace ionos_wordpress\essentials\dashboard\blocks\whatsnew;

use const ionos_wordpress\essentials\PLUGIN_DIR;

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
<h3 class="wp-block-heading">' . \esc_html__('What’s New') . '</h3>
<p>' . \esc_html__(
    'Discover new features and get an overview of the latest updates and enhancements of your WordPress Hub'
  ) . '</p>
<div class="wp-block-group" style="margin-top:var(--wp--preset--spacing--30);margin-bottom:var(--wp--preset--spacing--30);padding-top:0;padding-right:0;padding-bottom:0;padding-left:0"><!-- wp:list -->
<ul class="wp-block-list">
<li class="has-small-font-size">
  <h3>' . \esc_html__('Introducing the new WordPress Hub') . '</h3>
  <p>' . \esc_html__(
    "We're excited to announce the launch of our new user interface, featuring a completely new design and improved user experience. Managing your WordPress website has never been easier."
  ) . '</p>
</li>
<li class="has-small-font-size">
  <h3>' . \esc_html__('Vulnerability Scan Results') . '</h3>
  <p>' . \esc_html__(
    "We've added vulnerability scan results directly to your dashboard, giving you instant visibility into potential security risks to keep your WordPress site protected."
  ) . '</p>
</li>
';
}
