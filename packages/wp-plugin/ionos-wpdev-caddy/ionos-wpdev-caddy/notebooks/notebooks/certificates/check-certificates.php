<?php

$test_urls = [
  'https://api.wordpress.org/plugins/info/1.2/?action=plugin_information&slug=extendify',
  'https://api.wordpress.org/plugins/info/1.2/?action=plugin_information&slug=contact-form-7',
];

$multi_handle = curl_multi_init();
$curl_handles = [];

// 1. Alle cURL-Handles vorbereiten
foreach ($test_urls as $url) {
  $ch = curl_init();
  curl_setopt($ch, CURLOPT_URL, $url);
  curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

  // ⚠️ SSL-Verifikation deaktivieren (nur für Tests)
  curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
  curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);

  curl_multi_add_handle($multi_handle, $ch);
  $curl_handles[(int) $ch] = [
    'handle' => $ch,
    'url'    => $url,
  ];
}

// 2. Requests parallel ausführen
$running = null;
do {
  curl_multi_exec($multi_handle, $running);
  curl_multi_select($multi_handle);

  // 3. Fertige Handles abfragen
  while ($info = curl_multi_info_read($multi_handle)) {
    $ch  = $info['handle'];
    $url = $curl_handles[(int) $ch]['url'];

    if ($info['result'] !== CURLE_OK) {
      // Fehler nach cURL-Fehlernummer unterscheiden
      $errno = curl_errno($ch);
      echo curl_error($ch) . "\n";
      if ($errno === 60) {
        echo "Error for {$url}: SSL certificate problem: certificate missing or invalid.\n";
      } elseif ($errno === 77) {
        echo "Error for {$url}: Problem reading the CA cert file or path.\n";
      } else {
        echo "Error for {$url}: cURL Error ({$errno}): " . curl_error($ch) . "\n";
      }
    } else {
      $response = curl_multi_getcontent($ch);
      echo "Response for {$url} (first 200 chars):\n";
      echo substr($response, 0, 200) . "\n\n";
    }

    curl_multi_remove_handle($multi_handle, $ch);
    curl_close($ch);
    unset($curl_handles[(int) $ch]);
  }

} while ($running > 0);

curl_multi_close($multi_handle);
