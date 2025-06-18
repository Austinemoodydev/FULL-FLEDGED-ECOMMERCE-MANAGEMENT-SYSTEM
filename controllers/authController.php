<?php
// controllers/authController.php

require_once dirname(__DIR__) . '/config/db.php';

class AuthController
{
    protected $db;

    public function __construct()
    {
        $this->db = getDB();
    }

    public function login($username, $password, $remember = false)
    {
        $user = $this->db->fetchOne(
            "SELECT * FROM admin_users WHERE username = ? AND is_active = 1",
            [$username]
        );

        if (!$user) {
            return [
                'success' => false,
                'message' => 'User not found or inactive.'
            ];
        }

        if (!password_verify($password, $user['password'])) {
            return [
                'success' => false,
                'message' => 'Incorrect password.'
            ];
        }

        // You can set session or cookies here based on $remember
        $_SESSION['user_id'] = $user['id'];
        $_SESSION['username'] = $user['username'];
        $_SESSION['role'] = $user['role'];

        if ($remember) {
            // Set persistent login cookie here (e.g., JWT or a token system)
        }

        return [
            'success' => true,
            'message' => 'Login successful',
            'user' => [
                'id' => $user['id'],
                'username' => $user['username'],
                'email' => $user['email'],
                'full_name' => $user['full_name'],
                'role' => $user['role']
            ]
        ];
    }
    public function logout()
    {
        // Start session if not started
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        // Destroy session
        session_unset();
        session_destroy();

        return [
            'success' => true,
            'message' => 'You have been logged out successfully.'
        ];
    }
}
