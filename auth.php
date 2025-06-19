<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require_once __DIR__ . '/config/database.php';

class Auth {
    public function login($username, $password) {
        try {
            $database = new Database();
            $db = $database->getConnection();

            $query = "SELECT * FROM users WHERE username = ? AND password = ?";
            $stmt = $db->prepare($query);
            $stmt->execute([$username, $password]);
            $user = $stmt->fetch(PDO::FETCH_ASSOC);

            if ($user) {
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
            header('Location: login.php');
            exit;
        }
    }
}
?>