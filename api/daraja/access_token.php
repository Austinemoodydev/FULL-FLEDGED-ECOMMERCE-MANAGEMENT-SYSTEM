<?php
require_once 'config.php';

function generateAccessToken() {
    $url = getDarajaBaseURL() . '/oauth/v1/generate?grant_type=client_credentials';

    $credentials = base64_encode(CONSUMER_KEY . ':' . CONSUMER_SECRET);

    $headers = [
        'Authorization: Basic ' . $credentials
    ];

    $curl = curl_init();
    curl_setopt_array($curl, [
        CURLOPT_URL => $url,
        CURLOPT_HTTPHEADER => $headers,
        CURLOPT_RETURNTRANSFER => true
    ]);

    $response = curl_exec($curl);
    curl_close($curl);

    $result = json_decode($response);
    return $result->access_token ?? null;
}
