<?php
require_once 'access_token.php';
require_once 'config.php';

function lipaNaMpesa($phone, $amount, $accountRef = 'TestPayment', $transactionDesc = 'Pay') {
    $accessToken = generateAccessToken();

    $timestamp = date('YmdHis');
    $password = base64_encode(BUSINESS_SHORTCODE . PASSKEY . $timestamp);

    $url = getDarajaBaseURL() . '/mpesa/stkpush/v1/processrequest';

    $payload = [
        "BusinessShortCode" => BUSINESS_SHORTCODE,
        "Password" => $password,
        "Timestamp" => $timestamp,
        "TransactionType" => "CustomerPayBillOnline",
        "Amount" => $amount,
        "PartyA" => $phone,
        "PartyB" => BUSINESS_SHORTCODE,
        "PhoneNumber" => $phone,
        "CallBackURL" => CALLBACK_URL,
        "AccountReference" => $accountRef,
        "TransactionDesc" => $transactionDesc
    ];

    $headers = [
        "Authorization: Bearer $accessToken",
        "Content-Type: application/json"
    ];

    $curl = curl_init();
    curl_setopt_array($curl, [
        CURLOPT_URL => $url,
        CURLOPT_HTTPHEADER => $headers,
        CURLOPT_POST => true,
        CURLOPT_POSTFIELDS => json_encode($payload),
        CURLOPT_RETURNTRANSFER => true
    ]);

    $response = curl_exec($curl);
    curl_close($curl);

    return json_decode($response, true);
}

// Example Usage
$response = lipaNaMpesa("254712345678", 10);
print_r($response);
