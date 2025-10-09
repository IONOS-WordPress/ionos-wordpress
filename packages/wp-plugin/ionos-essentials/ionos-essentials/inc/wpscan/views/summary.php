<?php

namespace ionos\essentials\wpscan\views;

defined('ABSPATH') || exit();

function summary(): void
{
  global $wpscan;

  if (count($wpscan->get_issues()) !== 0) {
    echo '<script>document.querySelector(\'a[href$="tools"]\').classList.add("has-red-dot");</script>';
  }

  $args   = [
    'high'       => count($wpscan->get_issues('critical')),
    'medium'     => count($wpscan->get_issues('warning')),
    'last_scan'  => $wpscan->get_lastscan(),
  ];

  $error = $wpscan->get_error();

  $class = ($args['high'] > 0) ? 'high' : ($args['medium'] > 0 ? 'medium' : '');
  $class = ($error) ? 'error' : $class;

  ?>
  <div class="card ionos_vulnerability ionos-wpscan-summary <?php echo \esc_attr($class); ?>">
    <div class="card__content">
      <section class="card__section">

        <div class="scan_info">
          <h2 class="headline headline--sub ionos_vulnerability__headline"><?php \esc_html_e('Vulnerability scan', 'ionos-essentials'); ?></h2>
          <div class="ionos_vulnerability__content" style="display: flex;">
          <?php
          if ($wpscan->get_error()) {
            printf('<p class="ionos_vulnerability__error">%s</p>', \esc_html($error));
          } else {
            display_problems($args);
            display_last_scan($args);
          }
  ?>
          </div>
        </div>
        <p class="paragraph">
          <?php \esc_html_e(
            'We automatically scan daily and whenever a new plugin or theme is installed, using the WPScan vulnerability database.',
            'ionos-essentials'
          ); ?> <span class="link link--lookup" id="learn-more"><?php \esc_html_e('Learn more', 'ionos-essentials'); ?></span>
        </p>
      </section>
    </div>
  </div>


<?php
}

function display_problems(array $args): void
{

  echo '<div class="ionos_vulnerability__problems">';

  if (0 < $args['high'] || 0 < $args['medium']) {
    echo '<div class="issue-rows">';

    if (0 < $args['high']) {
      echo '<div class="issue-row high">';
      echo '<span class="bubble">' . \esc_html($args['high']) . '</span>' . \esc_html(
        _n('critical issue found', 'critical issues found', $args['high'], 'ionos-essentials')
      );
      echo '</div>';
    }
    if (0 < $args['medium']) {
      echo '<div class="issue-row medium">';
      echo '<span class="bubble">' . \esc_html($args['medium']) . '</span>' . \esc_html(
        _n('warning found', 'warnings found', $args['medium'], 'ionos-essentials')
      );
      echo '</div>';
    }
    echo '</div>';

  }

  if (0 === $args['high'] && 0 === $args['medium']) {
    echo '<div class="issue-row none">';
    echo '<span class="bubble"><i class="exos-icon exos-icon-check-16"></i></span><h4 class="headline headline--sub">' . \esc_html__(
      'Website is safe and secure',
      'ionos-essentials'
    );
    echo '</h4></div>';
  }
  echo '</div>';
}

function display_last_scan(array $args): void
{
  echo '<p class="ionos_vulnerability__last-scan">';
  (! $args['last_scan']) ? \esc_html_e('No scan has been performed yet.', 'ionos-essentials') : printf(
    // translators: %s is placeholder for the time since the last scan
    \esc_html__('Last scan ran %s ago', 'ionos-essentials'),
    $args['last_scan']
  );
  echo '</p>';
}
