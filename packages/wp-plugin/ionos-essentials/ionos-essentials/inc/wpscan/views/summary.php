<?php

namespace ionos\essentials\wpscan\views;

defined('ABSPATH') || exit();

function summary(): void
{
  global $wpscan;

  if (count($wpscan->get_issues()) !== 0) {
    echo '<script>document.querySelector(\'a[href$="tools"]\').classList.add("has-red-dot");</script>';
  }

  $class = $wpscan->get_issues('warning') ? 'medium' : '';
  $class = $wpscan->get_issues('critical') ? 'high' : $class;

  ?>
  <div class="card ionos_vulnerability ionos-wpscan-summary <?php echo \esc_attr($class); ?>">
    <div class="card__content">
      <section class="card__section">

      <div style="display: flex; align-items: center;">

        <div class="paragraph--critical" style="margin-right: 1em;">
          <i class="exos-icon exos-icon-warningmessage-32 with-issues-only"></i>
          <i class="exos-icon exos-icon-firmationmessage-32 without-issues-only color--success" ></i>
        </div>
        <div>
          <h2 class="headline headline--sub"><?php \esc_html_e('Vulnerability scan', 'ionos-essentials'); ?></h2>
          <div class="ionos_vulnerability__content with-issues-only-flex">
            <div class="issue-row high">
              <span class="bubble high"><?php echo count($wpscan->get_issues('critical'));
  ?></span> <?php \esc_html_e('critical issues found', 'ionos-essentials'); ?>
            </div>
            <div class="issue-row medium">
              <span class="bubble high"><?php
    echo count($wpscan->get_issues('warning')
    ) ?></span> <?php \esc_html_e('warnings found', 'ionos-essentials'); ?>
            </div>
          </div>

          <p class="paragraph paragraph--large paragraph--bold without-issues-only">
            <?php \esc_html_e('Website is safe and secure', 'ionos-essentials'); ?>
          </p>

          <p class="paragraph paragraph--neutral">
            <?php
            $last_scan = $wpscan->get_lastscan();
  // translators: %s is placeholder for the time since the last scan
  (! $last_scan) ? \esc_html_e('No scan has been performed yet.', 'ionos-essentials') : printf(
    \esc_html__('Last scan ran %s ago', 'ionos-essentials'),
    $last_scan
  );
  ?>
          </p>
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
