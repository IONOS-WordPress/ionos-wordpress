<?php

namespace ionos\essentials\wpscan;

require_once __DIR__ . '/class-wpscan.php';
$wpscan = new WPScan();
function render_summary()
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
function render_issues($args)
{
  global $wpscan;

  $count = count($wpscan->get_vulnerabilities()[$args['type']]);

  if (0 === $count) {
    return;
  }
  ?>
<div class="grid-col grid-col--12">
  <div class="sheet ionos-wpscan <?php echo esc_attr($args['type'] ?? ''); ?>">
    <section class="sheet__section">
      <div class="grid">
          <div class="grid-col grid-col--12">
              <h2 class="headline headline--sub headline--<?php echo esc_attr($args['type'] ?? ''); ?>">
                <?php
  ('critical' === $args['type'])
  ? printf(\_n('%d critical issue', '%d critical issues', $count, 'ionos-essentials'), $count)
  : printf(\_n('%d warning', '%d warnings', $count, 'ionos-essentials'), $count);
  ?>

                <span class="et-has-tooltip">
                  <span class="paragraph--cropped paragraph--activating paragraph--exos-icon exos-icon-info-1 et-tooltip-anchor"></span>
                  <span class="et-tooltip-content">
                    <?php
                    echo ('critical' === $args['type'])
                      ? esc_html__(
                        'Critical website security issues, identified by a CVSS score of 7.0 or higher, require immediate attention.',
                        'ionos-essentials'
                      )
                      : esc_html__(
                        'Website security warnings, identified by a CVSS score up to 6.9, require prompt attention.',
                        'ionos-essentials'
                      );
  ?>
                  </span>
                </span>
              </h2>
          </div>

          <div class="grid grid-col-12">
            <ul class="sheet__stripes">
              <?php

    foreach ($wpscan->get_vulnerabilities()[$args['type']] as $issue) {
      render_issue_line([
        'issue'           => $issue,
        'theme_or_plugin' => $issue['type'] ?? 'plugin',
        'slug'            => $issue['slug'] ?? '',
      ]);
    }
  ?>
              </ul>
          </div>
      </div>
    </section>
  </div>
</div>
<?php }

function render_issue_line($args)
{
  ?>
 <li class="settings-stripe settings-stripe--link  <?php echo esc_attr($args['theme_or_plugin']); ?>">
    <div class="settings-stripe__label"><strong><?php echo esc_html($args['issue']['name']); ?></strong></div>
    <div class="settings-stripe__action">

      <?php if ('plugin' === $args['theme_or_plugin']) {
        printf(
          '<span class="link link-action" data-slug="%s" style="margin-right: 1em;">%s</span>',
          \esc_attr($args['slug']),
          \esc_html__('View update details', 'ionos-essentials')
        );
        printf('<button class="button button-primary">%s</button>', esc_html('Update', 'ionos-essentials'));
      } else {
        printf('<button class="button delete">%s</button>', esc_html('Delete', 'ionos-essentials'));
      }
  ?>

    </div>
  </li>

  <?php
}
