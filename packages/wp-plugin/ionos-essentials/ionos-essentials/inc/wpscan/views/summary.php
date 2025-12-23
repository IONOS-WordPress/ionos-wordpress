<?php

namespace ionos\essentials\wpscan\views;

defined('ABSPATH') || exit();

function summary(): void
{
  global $wpscan;

  if (count($wpscan->get_issues()) !== 0) {
    echo '<script>document.querySelector(\'a[href$="tools"]\').classList.add("has-red-dot");</script>';
  }

  $args = [
    'high'      => count($wpscan->get_issues('critical')),
    'medium'    => count($wpscan->get_issues('warning')),
    'last_scan' => $wpscan->get_lastscan(),
  ];

  $error = $wpscan->get_error();

  $class = ($args['high'] > 0) ? 'high' : ($args['medium'] > 0 ? 'medium' : '');
  $class = ($error) ? 'error' : $class;

  $class_attr    = \esc_attr($class);
  $headline      = \esc_html__('Vulnerability scan', 'ionos-essentials');
  $inner_content = '';

  if ($wpscan->get_error()) {
    $error_escaped = \esc_html($error);
    $inner_content = "<p class=\"ionos_vulnerability__error\">{$error_escaped}</p>";
  } else {
    $inner_content = get_problems_html($args) . get_last_scan_html($args);
  }

  $description = \esc_html__(
    'We automatically scan daily and whenever a new plugin or theme is installed, using the WPScan vulnerability database.',
    'ionos-essentials'
  );
  $learn_more = \esc_html__('Learn more', 'ionos-essentials');

  printf(
    <<<EOF
    <div class="card ionos_vulnerability ionos-wpscan-summary {$class_attr}">
      <div class="card__content">
        <section class="card__section">
          <div class="scan_info">
            <h2 class="headline headline--sub ionos_vulnerability__headline">{$headline}</h2>
            <div class="ionos_vulnerability__content" style="display: flex;">
              {$inner_content}
            </div>
          </div>
          <p class="paragraph">
            {$description} <span class="link link--lookup" id="learn-more">{$learn_more}</span>
          </p>
        </section>
      </div>
    </div>
    EOF
  );
}

function get_problems_html(array $args): string
{
  $output = '';

  if (0 < $args['high'] || 0 < $args['medium']) {
    $rows = '';

    if (0 < $args['high']) {
      $high_count = \esc_html($args['high']);
      $high_text  = \esc_html(
        \_n('critical issue found', 'critical issues found', $args['high'], 'ionos-essentials')
      );
      $rows .= sprintf(
        <<<EOF
        <div class="issue-row high">
          <span class="bubble">{$high_count}</span>{$high_text}
        </div>
        EOF
      );
    }

    if (0 < $args['medium']) {
      $medium_count = \esc_html($args['medium']);
      $medium_text  = \esc_html(\_n('warning found', 'warnings found', $args['medium'], 'ionos-essentials'));
      $rows .= sprintf(
        <<<EOF
        <div class="issue-row medium">
          <span class="bubble">{$medium_count}</span>{$medium_text}
        </div>
        EOF
      );
    }

    $output = "<div class=\"issue-rows\">{$rows}</div>";
  }

  if (0 === $args['high'] && 0 === $args['medium']) {
    $safe_text = \esc_html__('Website is safe and secure', 'ionos-essentials');
    $output    = sprintf(
      <<<EOF
      <div class="issue-row none">
        <span class="bubble">
          <svg class="icon-done" xmlns="http://www.w3.org/2000/svg" width="100%%" height="100%%" viewBox="0 0 20 20">
            <circle cx="10" cy="10" r="10" fill="#4caf50" />
            <path fill="#ffffff" d="M13.6,6.0 L8.48,11.18 L6.4,9.09 L5.0,10.5 L8.48,14.0 L15.0,7.4 L13.6,6.0 Z" />
          </svg>
        </span>
        <h4 class="headline headline--sub">{$safe_text}</h4>
      </div>
      EOF
    );
  }

  return "<div class=\"ionos_vulnerability__problems\">{$output}</div>";
}

function get_last_scan_html(array $args): string
{
  if (! $args['last_scan']) {
    $scan_text = \esc_html__('No scan has been performed yet.', 'ionos-essentials');
  } else {
    $last_scan = \esc_html($args['last_scan']);
    // translators: %s is placeholder for the time since the last scan
    $scan_text = sprintf(\esc_html__('Last scan ran %s ago', 'ionos-essentials'), $last_scan);
  }

  return "<p class=\"ionos_vulnerability__last-scan\">{$scan_text}</p>";
}
