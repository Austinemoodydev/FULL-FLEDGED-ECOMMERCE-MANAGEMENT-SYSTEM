<?php
// config.php

define('CONSUMER_KEY', 'YourConsumerKeyHere');
define('CONSUMER_SECRET', 'YourConsumerSecretHere');
define('BUSINESS_SHORTCODE', '174379'); // Use your actual shortcode
define('PASSKEY', 'YourLipaNaMpesaPasskey');
define('CALLBACK_URL', 'https://yourdomain.com/daraja/callback_url.php');
define('ENV', 'sandbox'); // Change to 'production' when going live

function getDarajaBaseURL() {
    return ENV === 'sandbox'
        ? 'https://sandbox.safaricom.co.ke'
        : 'https://api.safaricom.co.ke';
}
