<?php

namespace ionos\essentials\wpscan;

class WPScan
{
  /**
   * @var array
   */
  private $issues;

  public function __construct()
  {
    require_once __DIR__ . '/testdata.php';
    $this->issues = $testdata;
  }

  public function convert_middleware_data()
  {
    $issues = \get_option('ionos_security_wpscan', []);

    // Plugin Essentials
    $issues['result']['plugins'][0]['issues'] = [
      [
        'title'    => 'Test Essentials Vulnerability',
        'fixed_in' => '1.2.3',
        'score'    => 9.5,
      ],
    ];

    // Plugin Marketplace
    $issues['result']['plugins'][1]['issues'] = [
      [
        'title'    => 'Test Marketplace Vulnerability',
        'fixed_in' => '1.2.3',
        'score'    => 1.5,
      ],
    ];

    // Theme 2011
    $issues['result']['themes'][0]['issues'] = [
      [
        'title'    => 'Test 2011 Vulnerability',
        'fixed_in' => '1.2.3',
        'score'    => 2.5,
      ],
      [
        'title'    => 'Test 2011 Vulnerability',
        'fixed_in' => '1.2.3',
        'score'    => 3.5,
      ],
    ];

    // Plugin which is not installed
    $issues['result']['plugins'][]          = $issues['result']['plugins'][1];
    $issues['result']['plugins'][3]['slug'] = 'not-installed-plugin';

    // Sort issues by score, ascending
    foreach (['plugins', 'themes'] as $type) {
      if (! empty($issues['result'][$type])) {
        foreach ($issues['result'][$type] as &$item) {
          if (! empty($item['issues']) && is_array($item['issues'])) {
            usort($item['issues'], function ($a, $b) {
              return $b['score'] <=> $a['score'];
            });
          }
        }
        unset($item);
      }
    }

    // Filter out items without issues
    foreach (['plugins', 'themes'] as $type) {
      $issues['result'][$type] = array_values(array_filter(
        $issues['result'][$type],
        function ($item) {
          return ! empty($item['issues']);
        }
      ));
    }

    $critical = [];
    $warning  = [];
    foreach (['plugins', 'themes'] as $type) {
      foreach ($issues['result'][$type] as $item) {
        if (! empty($item['issues'])) {
          foreach ($item['issues'] as $vuln) {
            $vuln['type'] = substr($type, 0, -1);

            $names = \get_plugins();
            $names = array_combine(
              array_map(function ($file) {
                return basename($file, '.php');
              }, array_keys($names)),
              array_values($names)
            );
            if (isset($item['slug'])) { // this line is just needed for the phpunit test
              $vuln['name'] = $names[$item['slug']]['Name'] ?? $item['slug'];
              $vuln['slug'] = $item['slug'];
            }

            if (isset($vuln['score']) && 7 < $vuln['score']) {
              $critical[] = $vuln;
            } else {
              $warning[] = $vuln;
            }
            break; // Only take the first (=highest) vulnerability for each item
          }
        }
      }
    }

    $this->issues = [
      'critical'  => $critical,
      'warning'   => $warning,
      'last_scan' => '8',
    ];
  }

  public function get_issues($filter = null)
  {
    if (null === $filter) {
      return $this->issues;
    }

    return array_filter(
      $this->issues ?? [],
      fn ($issue) => ('critical' === $filter) ? 7 < $issue['score'] : 7 >= $issue['score']
    );
  }

  public function get_lastscan()
  {
    return human_time_diff(time() - 4 * HOUR_IN_SECONDS, time());
  }
}
