<?php

namespace ionos\essentials\wpscan\views;

function summary()
{
  global $wpscan;
  ?>
  <script>
    document.querySelector('a[href$="tools"]').classList.add('has-red-dot')
  </script>
  <div class="card ionos_vulnerability ionos-wpscan-summary high">
    <div class="card__content">
      <section class="card__section">

      <div style="display: flex; align-items: center;">
        <div class="paragraph--critical" style="margin-right: 1em;">
          <i class="exos-icon exos-icon-warningmessage-32"></i>
        </div>
        <div>
          <h2 class="headline headline--sub"><?php echo \esc_html__('Vulnerability scan', 'ionos-essentials'); ?></h2>
          <div class="ionos_vulnerability__content">
            <div class="issue-row high">
              <span class="bubble high"><?php echo count($wpscan->get_vulnerabilities()['critical']);
  ?></span> <?php \esc_html_e('critical issue found', 'ionos-essentials'); ?>
            </div>
            <div class="issue-row medium">
              <span class="bubble high"><?php
    echo count($wpscan->get_vulnerabilities()['warning']
    ) ?></span> <?php \esc_html_e('warnings found', 'ionos-essentials'); ?>
            </div>
          </div>
          <p class="paragraph paragraph--small">
            <?php
    echo \esc_html(sprintf('Last scan ran %s hours ago', $wpscan->get_vulnerabilities()['last_scan']));
  ?>
          </p>
        </div>
      </div>

      <p class="paragraph">
        We automatically scan daily and whenever a new plugin or theme is installed, using the WPScan vulnerability database. <span class="link link--lookup" id="learn-more">Learn more</span>
      </p>

      </section>

    </div>
  </div>


<?php
}
