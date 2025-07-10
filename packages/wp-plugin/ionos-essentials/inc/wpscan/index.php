<?php

namespace ionos\essentials\wpscan;

function render_summary()
{ ?>
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
              <span class="bubble high">1</span> <?php echo \esc_html__('critical issue found', 'ionos-essentials'); ?>
            </div>
            <div class="issue-row medium">
              <span class="bubble high">1</span> <?php echo \esc_html__('warnings found', 'ionos-essentials'); ?>
            </div>
          </div>
          <p class="paragraph paragraph--small">
            <?php echo \esc_html__('Last scan ran', 'ionos-essentials'); ?>
            7
            <?php echo \esc_html__('hours ago', 'ionos-essentials'); ?>
          </p>
        </div>
      </div>


        <p class="paragraph">
          We automatically scan daily and whenever a new plugin or theme is installed, using the WPScan vulnerability database. <a href="javascript: alert('How dare you to test this function sooo early');" class="link link--lookup">Learn more</a>
        </p>

      </section>

    </div>
  </div>


<?php
}
function render_issues($args)
{
  ?>
<div class="grid-col grid-col--12">
  <div class="sheet ionos-wpscan <?php echo esc_attr($args['type'] ?? ''); ?>">
    <section class="sheet__section">
      <div class="grid">
          <div class="grid-col grid-col--12">
              <h2 class="headline headline--<?php echo esc_attr($args['exos_class'] ?? ''); ?>">
                <?php
                    $count = count($args['issues']);
  echo ('high' === $args['type'])
  ? esc_html(sprintf(\_n('%s critical issue', '%d critical issues', $count, 'ionos-essentials'), $count))
  : esc_html(sprintf(\_n('%s warning', '%d warnings', $count, 'ionos-essentials'), $count));
  ?>

                <span class="paragraph--cropped paragraph--activating paragraph--exos-icon exos-icon-info-1 tooltip" data-tooltip="Place your tooltip content" data-tooltip-position="right"></span>
                </h2>
          </div>

          <div class="grid grid-col-12">
            <ul class="sheet__stripes">
              <?php
    foreach ($args['issues'] as $issue) {
      $theme_or_plugin = (is_array($issue) ? 'plugin' : 'theme');
      render_issue_line([
        'issue'           => $issue,
        'theme_or_plugin' => $theme_or_plugin,
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
 <li class="settings-stripe settings-stripe--link __direct-selection <?php echo esc_attr($args['theme_or_plugin']); ?>">
    <div class="settings-stripe__label"><strong><?php echo esc_html($args['issue']['Name']); ?></strong></div>
    <div class="settings-stripe__value"></div>
    <div class="settings-stripe__action">
      <a href="#" class="link link-action" style="margin-right: 1em;"><?php esc_html_e('View update details', 'ionos-essentials'); ?></a>
      <button class="button delete" onClick="alert('How dare you to delete this');"><?php esc_html_e('Delete', 'ionos-essentials'); ?></button>
      <button class="button button-primary"><?php esc_html_e('Update', 'ionos-essentials'); ?></button>

    </div>
  </li>

  <?php
}
