<?php
require_once __DIR__ . '/config/database.php';

class Auth {
    public function __construct() {
        // Only start session if not already started and headers not sent
        if (session_status() === PHP_SESSION_NONE && !headers_sent()) {
            session_start();
        }
    }

    public function login($username, $password) {
        try {
            // Basic input validation
            if (empty($username) || empty($password)) {
                return false;
            }

            $database = new Database();
            $db = $database->getConnection();

            // Get user by username only
            $query = "SELECT * FROM users WHERE username = ?";
            $stmt = $db->prepare($query);
            $stmt->execute([trim($username)]);
            $user = $stmt->fetch(PDO::FETCH_ASSOC);

            if ($user && password_verify($password, $user['password'])) {
                // Regenerate session ID for security
                session_regenerate_id(true);

                $_SESSION['user'] = [
                    'id' => (int)$user['id'],
                    'username' => $user['username'],
                    'name' => $user['name'],
                    'role' => $user['role']
                ];
                return true;
            }
            return false;
        } catch (Exception $e) {
            error_log("Login error: " . $e->getMessage());
            return false;
        }
    }

    public function logout() {
        if (session_status() !== PHP_SESSION_NONE) {
            session_destroy();
        }
    }

    public function isLoggedIn() {
        return isset($_SESSION['user']) && !empty($_SESSION['user']);
    }

    public function getUser() {
        return $_SESSION['user'] ?? null;
    }

    public function requireLogin() {
        if (!$this->isLoggedIn()) {
            // For web pages, redirect to login
            if (!isset($_SERVER['HTTP_X_REQUESTED_WITH']) && 
                !str_contains($_SERVER['REQUEST_URI'], '/api/')) {
                if (!headers_sent()) {
                    header('Location: login.php');
                    exit;
                }
            } else {
                // For API requests, return JSON error
                if (!headers_sent()) {
                    header('HTTP/1.1 401 Unauthorized');
                    header('Content-Type: application/json');
                }
                echo json_encode(['error' => 'Authentication required']);
                exit;
            }
        }
    }
}

// Start session globally
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}