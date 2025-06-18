<?php
/**
 * Admin Logout Script
 * Path: views/auth/logout.php
 */

// Start session
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Include auth controller
require_once '../../controllers/authController.php';

$authController = new AuthController();

// Handle logout
$result = $authController->logout();

// Redirect to login page with message
if ($result['success']) {
    session_start();
    $_SESSION['logout_message'] = $result['message'];
    header('Location: login.php?logged_out=1');
} else {
    session_start();
    $_SESSION['error_message'] = $result['message'];
    header('Location: login.php?error=1');
}

exit();
?>