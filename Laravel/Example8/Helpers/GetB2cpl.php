<?php

namespace App\Helpers;

class GetB2cpl
{
    public function getDeliveryZipData($zipCode,  $weight)
    {
        $endpoint = "https://api.b2cpl.ru/services/api_client.ashx";
        $jsonData = [
            "client" => "*******",
            "key" => "******",
            "func" => "tarif",
            "region" => 77,
            "zip" => $zipCode,
            "route_code" => 1,
            "parcels" => [
                [
                    "weight" => $weight
                ]
            ],
            "price_assess" => 0,
            "price_amount" => 0,
            "flag_courier" => true,
            "flag_pvz" => true,
            "flag_post" => true
        ];
        $curl = curl_init();

        curl_setopt_array($curl, array(
            CURLOPT_URL => $endpoint,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_ENCODING => '',
            CURLOPT_MAXREDIRS => 10,
            CURLOPT_TIMEOUT => 0,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
            CURLOPT_CUSTOMREQUEST => 'POST',
            CURLOPT_POSTFIELDS => json_encode($jsonData),
            CURLOPT_HTTPHEADER => array(
                'Content-Type: text/plain'
            ),
        ));

        $response = curl_exec($curl);
        curl_close($curl);

        return $response;
    }
}