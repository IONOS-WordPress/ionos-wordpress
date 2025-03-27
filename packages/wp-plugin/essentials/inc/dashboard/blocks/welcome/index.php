<?php

namespace ionos_wordpress\essentials\dashboard\blocks\welcome;

use const ionos_wordpress\essentials\PLUGIN_DIR;
use const ionos_wordpress\essentials\PLUGIN_FILE;

\add_action('init', function () {
  \register_block_type(
    PLUGIN_DIR . '/build/dashboard/blocks/welcome',
    [
      'render_callback' => __NAMESPACE__ . '\\render_callback',
    ]
  );
});

function render_callback(): string
{
  $template = '<div id="essentials-welcome_block">
<div class="card" style="max-width: 640px;">
<div class="card__content">
    <section class="card__section">
      <h2 class="card__headline">Welcome to your [Brand] Hub</h2>
      <h4 class="card__subheadline">This overview is your gateway to unlocking the full potential of your WordPress website.</h4>
      <ul class="check-list">
        <li>Recommendations for next steps</li>
        <li>Helpful links and shortcuts</li>
        <li>Comprehensive help section, including: AI chat support, Guided tours and an extensive knowledge database</li>
      </ul>
      <div class="paragraph">Stay tuned for exciting new features and updates to come!</div>
    </section>
    <footer class="card__footer card__footer--align-center">
      <a class="button button--primary">Order now</a>
    </footer>
  </div>
  </div>
</div>';
  // return $template;


$message_version = "1.0.0";

  return "<dialog>
<div class='card' style='max-width: 640px;'>
<div class='card__content'>
    <section class='card__section'>
      <h2 class='card__headline'>" . sprintf(\__('Welcome to your %s Hub', 'ionos-essentials'), \get_option('ionos_group_brand_menu', 'IONOS'))  ."</h2>
      <h4 class='card__subheadline'>" . \__(
  'This overview is your gateway to unlocking the full potential of your WordPress website.',
  'ionos-essentials'
)."</h4>
      <ul class='check-list'>
        <li>".\__('Recommendations for next steps', 'ionos-essentials')."</li>
        <li>".\__('Helpful links and shortcuts', 'ionos-essentials')."</li>
        <li>".\__(
  'Comprehensive help section, including: AI chat support, Guided tours and an extensive knowledge database',
  'ionos-essentials')."</li>
      </ul>
      <div class='paragraph'>".\__('Stay tuned for exciting new features and updates to come!', 'ionos-essentials')."</div>
    </section>
    <footer class='card__footer card__footer--align-center'>
      <button class='button button--primary' data-version='{$message_version}' autofocus >" .\__('Close', 'ionos-essentials') . "</button>
    </footer>
  </div>
  </div>
  </dialog>";
}
