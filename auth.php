<?php
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

            // Note: In production, use password_hash() and password_verify()
            $query = "SELECT * FROM users WHERE username = ? AND password = ?";
            $stmt = $db->prepare($query);
            $stmt->execute([trim($username), $password]);
            $user = $stmt->fetch(PDO::FETCH_ASSOC);

            if ($user) {
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
        session_destroy();
    }

    public function isLoggedIn() {
        return isset($_SESSION['user']);
    }

    public function getUser() {
        return $_SESSION['user'] ?? null;
    }

    public function requireLogin() {
        if (!$this->isLoggedIn()) {
            if (!headers_sent()) {
                header('HTTP/1.1 401 Unauthorized');
            }
            echo json_encode(['error' => 'Authentication required']);
            exit;
        }
    }
}
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require_once __DIR__ . '/config/database.php';