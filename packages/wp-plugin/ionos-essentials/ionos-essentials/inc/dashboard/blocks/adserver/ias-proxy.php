<?php

$url = 'https://ias.ionos.de/ias/zones/json';

$json_data = json_encode([
  "zones" => [
    [
      "zoneId" => "developers_docs_example",
      "container" => "ias.zone0"
    ]
  ],
  "data" => [
    "usePOST" => true
  ],
  'v'                    => '6.12.19',
  'subset'               => false,
  'application'          => 'WP_ADMIN',
  'page'                 => 'Dashboard',
  'frontendSessionToken' => 'adserver_default_token',
  'tenant'               => 'IONOS_DE',
  'tzOffset'             => '+2', // Note: %2B is decoded to "+"
  'origin'               => 'http://localhost:8888/wp-content/plugins/ionos-essentials/ionos-essentials/inc/dashboard/blocks/adserver/view.html',
  'screenWidth'          => 0,
  'screenHeight'         => 0,
]);

$ch = curl_init($url);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'POST');
curl_setopt($ch, CURLOPT_POSTFIELDS, $json_data);
curl_setopt($ch, CURLOPT_HTTPHEADER, [
  'Content-Type: application/json',
  'Content-Length: ' . strlen($json_data),
  'User-Agent: Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/120.0.0.0 Safari/537.36',
]);

$response = curl_exec($ch);

if (curl_errno($ch)) {
  echo 'cURL Error: ' . curl_error($ch);
} else {
  echo $response;
}

curl_close($ch);
