<?php
require_once 'auth.php';

// Initialize session handling
$auth = new Auth();

// Clear all session data
$_SESSION = [];

// Destroy the session
$auth->logout();

// Remove session cookie
if (ini_get('session.use_cookies')) {
    $params = session_get_cookie_params();
    setcookie(session_name(), '', time() - 42000,
        $params['path'], $params['domain'],
        $params['secure'], $params['httponly']
    );
}

// Redirect to login page
header('Location: login.php');
exit;

