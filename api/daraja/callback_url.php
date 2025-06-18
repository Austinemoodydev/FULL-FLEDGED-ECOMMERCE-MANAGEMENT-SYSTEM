<?php
// callback_url.php

header("Content-Type: application/json");

// Get the raw JSON data
$data = file_get_contents("php://input");
$logFile = "mpesa_log_" . date("Y-m-d") . ".txt";

// Log the callback for verification
file_put_contents($logFile, $data . PHP_EOL, FILE_APPEND);

// Decode JSON data
$decoded = json_decode($data, true);

// You can store $decoded['Body']['stkCallback'] details into your DB here

echo json_encode(["ResultCode" => 0, "ResultDesc" => "Success"]);
