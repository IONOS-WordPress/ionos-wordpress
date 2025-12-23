<?php

namespace ionos\essentials\dashboard\blocks\whatsnew;

defined('ABSPATH') || exit();

use ionos\essentials\Tenant;

function get_survey_url(): string
{
  $survey_links = [
    'de'    => 'https://feedback.ionos.com/nmdopgnfds?l=de',
    'en_us' => 'https://feedback.ionos.com/nmdopgnfds?l=en-us',
    'en'    => 'https://feedback.ionos.com/nmdopgnfds?l=en',
    'fr'    => 'https://feedback.ionos.com/nmdopgnfds?l=fr',
    'es'    => 'https://feedback.ionos.com/nmdopgnfds?l=es',
    'it'    => 'https://feedback.ionos.com/nmdopgnfds?l=it',
  ];
  $locale = \determine_locale();
  if ($locale === 'en_US') {
    return $survey_links['en_us'];
  }
  $lang = strtolower(preg_split('/[_-]/', $locale)[0]);
  if (isset($survey_links[$lang])) {
    return $survey_links[$lang];
  }
  return $survey_links['en'];
}

function render_callback(): void
{
  $image_url = \esc_url(\plugins_url('', __FILE__) . '/whats-new.png');

  $headline  = \esc_html__("What's New", 'ionos-essentials');
  $paragraph = \esc_html__('Get an overview of the latest updates and enhancements.', 'ionos-essentials');

  // translators: %s is placeholder for the tenant name
  $upgrade_headline = sprintf(
    \esc_html__('Your %s Hub Just Got a Powerful Upgrade!', 'ionos-essentials'),
    Tenant::get_label()
  );
  $upgrade_paragraph = \esc_html__(
    "We've just rolled out a brand-new \"Tools & Security\" tab! All the features and user interfaces from your previous security plugin have now found their new home here, making everything more centralized and easier to manage. Plus, you'll find a new maintenance page function that you can switch on whenever you need it.",
    'ionos-essentials'
  );

  $feedback_headline  = \esc_html__('Your Feedback is important to us', 'ionos-essentials');
  $feedback_paragraph = \esc_html__(
    'We\'re always looking for ways to make your WordPress hosting experience even better. Please take a few minutes to fill out a quick online survey.',
    'ionos-essentials'
  );

  $survey_url       = \esc_attr(get_survey_url());
  $survey_link_text = \esc_html__('Take the survey', 'ionos-essentials');

  printf(
    <<<EOF
<div class="card">
  <div class="card__content">
    <section class="card__section">
      <img src="{$image_url}" style="float: right; height: 120px;">

      <h2 class="headline headline--sub">{$headline}</h2>
      <p class="paragraph">{$paragraph}</p>

      <ul class="check-list">
        <li>
          <h3 class="headline headline--sub">{$upgrade_headline}</h3>
          <p>{$upgrade_paragraph}</p>
        </li>
        <li>
          <h3 class="headline headline--sub">{$feedback_headline}</h3>
          <p>{$feedback_paragraph}</p>
          <a href="{$survey_url}" target="_blank" class="link link--action">
            {$survey_link_text}
          </a>
        </li>
      </ul>
    </section>
  </div>
</div>
EOF
  );
}
