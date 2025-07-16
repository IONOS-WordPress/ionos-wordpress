<?php

namespace ionos\essentials\wpscan;

class WPScan
{
  /**
   * @var array
   */
  private $vulnerabilities;

  public function __construct()
  {
    $vulnerabilities = \get_option('ionos_security_wpscan', []);

    // Plugin Essentials
    $vulnerabilities['result']['plugins'][0]['vulnerabilities'] = [
      [
        'title'    => 'Test Essentials Vulnerability',
        'fixed_in' => '1.2.3',
        'score'    => 9.5,
      ],
    ];

    // Plugin Marketplace
    $vulnerabilities['result']['plugins'][1]['vulnerabilities'] = [
      [
        'title'    => 'Test Marketplace Vulnerability',
        'fixed_in' => '1.2.3',
        'score'    => 1.5,
      ],
    ];

    // Theme 2011
    $vulnerabilities['result']['themes'][0]['vulnerabilities'] = [
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
    $vulnerabilities['result']['plugins'][]          = $vulnerabilities['result']['plugins'][1];
    $vulnerabilities['result']['plugins'][3]['slug'] = 'not-installed-plugin';

    // Sort vulnerabilities by score, ascending
    foreach (['plugins', 'themes'] as $type) {
      if (! empty($vulnerabilities['result'][$type])) {
        foreach ($vulnerabilities['result'][$type] as &$item) {
          if (! empty($item['vulnerabilities']) && is_array($item['vulnerabilities'])) {
            usort($item['vulnerabilities'], function ($a, $b) {
              return $b['score'] <=> $a['score'];
            });
          }
        }
        unset($item);
      }
    }

    // Filter out items without vulnerabilities
    foreach (['plugins', 'themes'] as $type) {
      $vulnerabilities['result'][$type] = array_values(array_filter(
        $vulnerabilities['result'][$type],
        function ($item) {
          return ! empty($item['vulnerabilities']);
        }
      ));
    }

    $critical = [];
    $warning  = [];
    foreach (['plugins', 'themes'] as $type) {
      foreach ($vulnerabilities['result'][$type] as $item) {
        if (! empty($item['vulnerabilities'])) {
          foreach ($item['vulnerabilities'] as $vuln) {
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

    $this->vulnerabilities = [
      'critical'  => $critical,
      'warning'   => $warning,
      'last_scan' => '8',
    ];
  }

  public function get_vulnerabilities()
  {
    return $this->vulnerabilities;
  }
}
