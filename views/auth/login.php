<?php

/**
 * Admin Login Form
 * Path: views/auth/login.php
 */

// Start session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Redirect if already logged in
if (isset($_SESSION['admin_logged_in']) && $_SESSION['admin_logged_in'] === true) {
    header('Location: ../admin/dashboard.php');
    exit();
}

// Include database connection
require_once '../../config/db.php';

// Handle form submission
$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';
    $remember = isset($_POST['remember']);
if (empty($username) || empty($password)) {
    $error = 'Please enter both username and password.';
} else {
    // Include auth controller
    require_once __DIR__ . '/../../controllers/authController.php';
    
    $authController = new AuthController();
    $result = $authController->login($username, $password, $remember);
}

        if ($result['success']) {
            header('Location: ../admin/dashboard.php');
            exit();
        } else {
            $error = $result['message'];
        }
    }

?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Login - eCommerce System</title>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link rel="stylesheet" href="../../assets/css/login.css">
</head>

<body>
    <div class="login-container">
        <div class="login-header">
            <h1><i class="fas fa-shield-alt"></i> Admin Panel</h1>
            <p>Please sign in to access the dashboard</p>
        </div>

        <div class="login-form">
            <?php if (!empty($error)): ?>
                <div class="alert alert-danger">
                    <i class="fas fa-exclamation-circle"></i> <?php echo htmlspecialchars($error); ?>
                </div>
            <?php endif; ?>

            <?php if (!empty($success)): ?>
                <div class="alert alert-success">
                    <i class="fas fa-check-circle"></i> <?php echo htmlspecialchars($success); ?>
                </div>
            <?php endif; ?>

            <form method="POST" action="" id="loginForm">
                <div class="form-group">
                    <label for="username">
                        <i class="fas fa-user"></i> Username or Email
                    </label>
                    <input
                        type="text"
                        id="username"
                        name="username"
                        class="form-control"
                        required
                        autofocus
                        value="<?php echo htmlspecialchars($_POST['username'] ?? ''); ?>"
                        placeholder="Enter your username or email">
                </div>

                <div class="form-group">
                    <label for="password">
                        <i class="fas fa-lock"></i> Password
                    </label>
                    <div style="position: relative;">
                        <input
                            type="password"
                            id="password"
                            name="password"
                            class="form-control"
                            required
                            placeholder="Enter your password">
                        <span class="password-toggle" onclick="togglePassword()">
                            <i class="fas fa-eye" id="toggleIcon"></i>
                        </span>
                    </div>
                </div>

                <div class="checkbox-group">
                    <input type="checkbox" id="remember" name="remember" value="1">
                    <label for="remember">Remember me for 30 days</label>
                </div>

                <button type="submit" class="btn-login" id="loginBtn">
                    <span id="loginText">
                        <i class="fas fa-sign-in-alt"></i> Sign In
                    </span>
                    <div class="loading" id="loading">
                        <div class="spinner"></div>
                    </div>
                </button>
            </form>

            <div class="forgot-password">
                <a href="forgot-password.php">
                    <i class="fas fa-key"></i> Forgot your password?
                </a>
            </div>
        </div>

        <div class="footer">
            <p>&copy; <?php echo date('Y'); ?> eCommerce System. All rights reserved.</p>
        </div>
    </div>
    <script src="../../assets/js/login.js" defer></script>
</body>

</html>