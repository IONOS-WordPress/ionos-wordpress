<?php

namespace ionos\essentials\wpscan\views;

defined('ABSPATH') || exit();

function issues(array $args): void
{
  global $wpscan;

  $count = count($wpscan->get_issues($args['type']));

  if (0 === $count) {
    return;
  }
  ?>
<div class="grid-col grid-col--12">
  <div class="sheet ionos-wpscan <?php echo \esc_attr($args['type']); ?>">
    <section class="sheet__section">
      <div class="grid">
          <div class="grid-col grid-col--12">
              <h2 class="headline headline--sub headline--<?php echo \esc_attr($args['type']); ?>">
                <?php
                  echo \esc_html(sprintf(
                    'critical' === $args['type'] ?
                      \_n('%d critical issue', '%d critical issues', $count, 'ionos-essentials')
                      : \_n('%d warning', '%d warnings', $count, 'ionos-essentials'), $count
                  ));
  ?>
                <span class="et-has-tooltip">
                  <span class="paragraph--cropped paragraph--activating paragraph--exos-icon exos-icon-info-1 et-tooltip-anchor"></span>
                  <span class="et-tooltip-content">
                    <?php
      echo 'critical' === $args['type']
        ? \esc_html__(
          'Critical website security issues, identified by a CVSS score of 7.0 or higher, require immediate attention.',
          'ionos-essentials'
        )
        : \esc_html__(
          'Website security warnings, identified by a CVSS score up to 6.9, require prompt attention.',
          'ionos-essentials'
        )
  ?>
                  </span>
                </span>
              </h2>
          </div>

          <div class="grid grid-col-12">
            <ul class="sheet__stripes">
              <?php
                foreach ($wpscan->get_issues($args['type']) as $issue) {
                  issue_line($issue);
                }
  ?>
              </ul>
          </div>
      </div>
    </section>
  </div>
</div>
<?php }

function issue_line(array $issue): void
{
  ?>
 <li class="settings-stripe settings-stripe--link  <?php echo \esc_attr($issue['type']); ?>">
    <div class="settings-stripe__label"><strong><?php echo \esc_html($issue['name']); ?></strong></div>
    <div class="settings-stripe__action">

      <?php

      $payload = [
        'path' => $issue['path'],
        'type' => $issue['type'],
        'slug' => $issue['slug'],
      ];

  if ($issue['update']) {
    if ('plugin' === $issue['type']) {
      printf(
        '<span class="link link-action" data-slug="%s" style="margin-right: 1em;">%s</span>',
        \esc_attr($issue['slug']),
        \esc_html__('View update details', 'ionos-essentials')
      );
    }
    $payload['action'] = 'update';
    $payload           = \wp_json_encode($payload);

    printf(
      '<button class="button button-primary" data-wpscan="%s">%s</button>',
      \esc_attr($payload),
      \esc_html('Update', 'ionos-essentials')
    );
  } else {
    $active_theme = \wp_get_theme();
    if ('theme' === $issue['type'] and strtolower($active_theme->get('Name')) === strtolower($issue['name'])) {
      printf(
        '<p class="paragraph">%s</p>',
        \esc_html__('This theme is active. Please activate another theme.', 'ionos-essentials')
      );
    } else {
      $payload['action'] = 'delete';
      $payload           = \wp_json_encode($payload);
      printf(
        '<button class="button delete" data-wpscan="%s">%s</button>',
        \esc_attr($payload),
        \esc_html('Delete', 'ionos-essentials')
      );
    }
  }
  ?>

    </div>
  </li>

  <?php
}
