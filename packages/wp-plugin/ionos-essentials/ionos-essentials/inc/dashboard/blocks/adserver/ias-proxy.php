<?php

$data = json_decode(file_get_contents('php://input'), true);

$ch = curl_init('https://ias.ionos.de/ias/zones/json');
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'POST');
curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
curl_setopt($ch, CURLOPT_HTTPHEADER, [
  'Content-Type: application/json',
  'Content-Length: ' . strlen(json_encode($data)),
  'User-Agent: Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/120.0.0.0 Safari/537.36',
]);

$response = curl_exec($ch);

if (! curl_errno($ch)) {
  echo $response;
}

curl_close($ch);
