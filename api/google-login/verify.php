<?php
// verify.php

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $token = $_POST['credential'];

    $clientID = "YOUR_GOOGLE_CLIENT_ID"; // Same as in index.html

    $url = "https://oauth2.googleapis.com/tokeninfo?id_token=" . $token;

    $response = file_get_contents($url);
    $data = json_decode($response, true);

    if ($data && isset($data['aud']) && $data['aud'] === $clientID) {
        // ✅ Token is valid
        $email = $data['email'];
        $name = $data['name'];
        $picture = $data['picture'];

        // You can now create a session or save the user in your DB
        echo "<h3>Welcome, $name</h3>";
        echo "<img src='$picture' alt='Profile Picture'>";
        echo "<p>Email: $email</p>";
    } else {
        echo "Invalid ID Token";
    }
} else {
    echo "No token received.";
}
