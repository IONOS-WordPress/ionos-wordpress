<?php

namespace ionos\essentials\dashboard\blocks\whatsnew;

use ionos\essentials\Tenant;

function get_survey_url()
{
  $survey_links = [
    'de'    => 'https://feedback.ionos.com/nmdopgnfds?l=de',
    'en_us' => 'https://feedback.ionos.com/nmdopgnfds?l=en-us',
    'en'    => 'https://feedback.ionos.com/nmdopgnfds?l=en',
    'fr'    => 'https://feedback.ionos.com/nmdopgnfds?l=fr',
    'es'    => 'https://feedback.ionos.com/nmdopgnfds?l=es',
    'it'    => 'https://feedback.ionos.com/nmdopgnfds?l=it',
  ];
  $locale = determine_locale();
  if ($locale === 'en_US') {
    return $survey_links['en_us'];
  }
  $lang = strtolower(preg_split('/[_-]/', $locale)[0]);
  if (isset($survey_links[$lang])) {
    return $survey_links[$lang];
  }
  return $survey_links['en'];
}

function render_callback()
{
  ?>

<div class="card">
    <div class="card__content">
      <section class="card__section">
        <img src="<?php echo \esc_url(\plugins_url('', __FILE__) . '/whats-new.png'); ?>" style="float: right; height: 120px;">

        <h2 class="headline headline--sub"><?php \esc_html_e('What’s New', 'ionos-essentials'); ?></h2>
        <p class="paragraph"><?php \esc_html_e('Get an overview of the latest updates and enhancements.', 'ionos-essentials'); ?></p>

        <ul class="check-list">
          <li>
            <h3 class="headline headline--sub"><?php printf(\esc_html__('Your %s Hub Just Got a Powerful Upgrade!', 'ionos-essentials'), Tenant::getInstance()->label); ?></h3>
            <p><?php \esc_html_e(
              "We've just rolled out a brand-new \"Tools & Security\" tab! All the features and user interfaces from your previous security plugin have now found their new home here, making everything more centralized and easier to manage. Plus, you'll find a new maintenance page function that you can switch on whenever you need it.",
              'ionos-essentials'
            ); ?></p>
          </li>
          <li>
            <h3 class="headline headline--sub"><?php \esc_html_e('Your Feedback is important to us', 'ionos-essentials'); ?></h3>
            <p><?php \esc_html_e(
              'We\'re always looking for ways to make your WordPress hosting experience even better. Please take a few minutes to fill out a quick online survey.',
              'ionos-essentials'
            ); ?></p>
            <a href="<?php echo \esc_html(get_survey_url()); ?>" target="_blank" class="link link--action">
              <?php \esc_html_e('Take the survey', 'ionos-essentials'); ?>
            </a>
          </li>
        </ul>
      </section>

    </div>
  </div>
<?php
}
