<?php
session_start();

class Auth {
    private $users = [
        'admin' => [
            'password' => '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', // password: password
            'name' => 'Administrator',
            'role' => 'admin'
        ],
        'kasir' => [
            'password' => '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', // password: password
            'name' => 'Kasir',
            'role' => 'kasir'
        ]
    ];

    public function login($username, $password) {
        if (isset($this->users[$username])) {
            if (password_verify($password, $this->users[$username]['password'])) {
                $_SESSION['user'] = [
                    'username' => $username,
                    'name' => $this->users[$username]['name'],
                    'role' => $this->users[$username]['role']
                ];
                return true;
            }
        }
        return false;
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
