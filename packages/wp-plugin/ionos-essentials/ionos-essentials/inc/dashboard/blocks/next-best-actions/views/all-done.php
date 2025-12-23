<?php

namespace ionos\essentials\dashboard\blocks\next_best_actions;

defined('ABSPATH') || exit();
use ionos\essentials\Tenant;
use const ionos\essentials\PLUGIN_FILE;

function all_done_view(): void
{
  $digital_guide_supported_languages = ['de', 'es', 'fr', 'it'];
  $language                          = explode('_', get_locale())[0];
  $tld                               = in_array($language, $digital_guide_supported_languages) ? $language : 'com';
  $digital_guide_url                 = \esc_attr('https://www.ionos.' . $tld . '/digitalguide/hub/wordpress/');
  $survey_dismissed                  = \get_option(NBA::OPTION_STATUS_NAME)['survey']['dismissed'] ?? false ?
    sprintf(<<<DISMISSED
    <a href="%s" class="button button--secondary" target="_blank">%s</a>
    DISMISSED
      , \esc_attr(get_survey_url()), esc_html__('Leave feedback', 'ionos-essentials'))
    : '';

  $thumbs_up_url     = esc_attr(\plugins_url('/ionos-essentials/inc/dashboard/assets/thumbs-up.svg', PLUGIN_FILE));
  $headline          = esc_html__('You\'re all caught up!', 'ionos-essentials');
  $completed_message =  \esc_html__(
    'You\'ve completed all of our current recommendations. This list will update automatically with new tips, so check in again soon to see what\'s next.',
    'ionos-essentials'
  );
  $buttons = '';
  if ('ionos' === Tenant::get_slug()) {
    $digital_guide_label = \esc_html__('View Digital Guide', 'ionos-essentials');
    $buttons             = sprintf(<<<BUTTONS
      <div class="buttons">
      <a href="{$digital_guide_url}" class="button button--secondary" target="_blank">{$digital_guide_label}</a>
      {$survey_dismissed}
    </div>
    BUTTONS);
  }

  printf(<<<EOF
  <div id="ionos_next_best_actions__all_done">
    <div class="card">
      <div class="container">
        <img src="{$thumbs_up_url}" alt="All Done Thumb" width="100" height="100">
        <h3 class="headline">{$headline}</h3>
        <p class="paragraph">
          <span>{$completed_message}</span>
        </p>
        {$buttons}
      </div>
    </div>
  </div>
  EOF);
}
