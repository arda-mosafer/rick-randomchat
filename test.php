<?php

$str = "/BTC";
echo trim($str, '/');











$curl = curl_init();

curl_setopt_array($curl, array(
   CURLOPT_URL => "https://one-api.ir/DigitalCurrency/?token=421226:67e31d864aa60",
   CURLOPT_RETURNTRANSFER => true,
   CURLOPT_ENCODING => '',
   CURLOPT_MAXREDIRS => 10,
   CURLOPT_TIMEOUT => 0,
   CURLOPT_FOLLOWLOCATION => true,
   CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
   CURLOPT_CUSTOMREQUEST => 'GET',
));

$response = curl_exec($curl);

curl_close($curl);
$result = (json_decode($response));

var_dump($result->result[0]->key, $result->result[1]->name); die();