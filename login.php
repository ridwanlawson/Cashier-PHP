<?php
session_start();
require_once 'auth.php';

$auth = new Auth();
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = $_POST['username'] ?? '';
    $password = $_POST['password'] ?? '';
    
    if ($auth->login($username, $password)) {
        header('Location: index.php');
        exit;
    } else {
        $error = 'Username atau password salah!';
    }
}

if ($auth->isLoggedIn()) {
    header('Location: index.php');
    exit;
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - Kasir Digital</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        :root {
            --bg-primary: #0f1419;
            --bg-secondary: #1a202c;
            --bg-tertiary: #2d3748;
            --accent-primary: #00d4ff;
            --text-primary: #e2e8f0;
            --text-secondary: #a0aec0;
            --border-color: #374151;
        }

        body {
            background: linear-gradient(135deg, var(--bg-primary) 0%, var(--bg-secondary) 100%);
            color: var(--text-primary);
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            transition: all 0.3s ease;
        }

        body.light-mode {
            background: linear-gradient(135deg, #f8f9fa 0%, #e9ecef 100%) !important;
            color: #212529 !important;
        }
        
        .login-container {
            background: var(--bg-secondary);
            border: 1px solid var(--border-color);
            border-radius: 15px;
            box-shadow: 0 10px 25px rgba(0, 0, 0, 0.3);
            padding: 2rem;
            max-width: 400px;
            width: 100%;
            transition: all 0.3s ease;
        }

        body.light-mode .login-container {
            background: #ffffff !important;
            border: 1px solid #dee2e6 !important;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1) !important;
            color: #212529 !important;
        }
        
        .login-header {
            text-align: center;
            margin-bottom: 2rem;
        }
        
        .login-header h2 {
            color: #00d4ff;
            margin-bottom: 0.5rem;
        }
        
        .form-control {
            background: var(--bg-tertiary);
            border: 1px solid var(--border-color);
            color: var(--text-primary);
            margin-bottom: 1rem;
            transition: all 0.3s ease;
        }
        
        .form-control:focus {
            background: var(--bg-tertiary);
            border-color: var(--accent-primary);
            box-shadow: 0 0 0 0.2rem rgba(0, 212, 255, 0.25);
            color: var(--text-primary);
        }

        body.light-mode .form-control {
            background: #ffffff !important;
            border: 1px solid #ced4da !important;
            color: #495057 !important;
        }

        body.light-mode .form-control:focus {
            background: #ffffff !important;
            border-color: var(--accent-primary) !important;
            color: #495057 !important;
        }

        body.light-mode .login-header h2 {
            color: var(--accent-primary) !important;
        }

        body.light-mode .user-info {
            background: #f8f9fa !important;
            color: #495057 !important;
        }

        .theme-toggle {
            position: fixed;
            top: 20px;
            right: 20px;
            background: var(--accent-primary);
            color: white;
            border: none;
            border-radius: 50%;
            width: 50px;
            height: 50px;
            display: flex;
            align-items: center;
            justify-content: center;
            cursor: pointer;
            z-index: 1000;
            transition: all 0.3s ease;
        }

        .theme-toggle:hover {
            transform: scale(1.1);
        }

        body.light-mode .theme-toggle {
            background: #495057 !important;
            color: white !important;
        }
        
        .btn-primary {
            background: linear-gradient(135deg, #00d4ff, #0891b2);
            border: none;
            width: 100%;
            padding: 0.75rem;
            font-weight: 600;
        }
        
        .btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 25px rgba(0, 212, 255, 0.5);
        }
        
        .alert-danger {
            background: rgba(255, 107, 107, 0.1);
            border: 1px solid #ff6b6b;
            color: #ff6b6b;
        }
        
        .user-info {
            background: var(--bg-tertiary);
            border-radius: 10px;
            padding: 1rem;
            margin-bottom: 1rem;
            font-size: 0.875rem;
            transition: all 0.3s ease;
        }
    </style>
</head>
<body>
    <button class="theme-toggle" onclick="toggleTheme()" title="Toggle Theme">
        <i class="fas fa-moon" id="themeIcon"></i>
    </button>
    <div class="login-container">
        <div class="login-header">
            <h2><i class="fas fa-cash-register"></i> Kasir Digital</h2>
            <p class="text-muted">Silakan login untuk melanjutkan</p>
        </div>
        
        <?php if ($error): ?>
            <div class="alert alert-danger">
                <i class="fas fa-exclamation-triangle"></i> <?= htmlspecialchars($error) ?>
            </div>
        <?php endif; ?>
        
        <div class="user-info">
            <strong>Demo Login:</strong><br>
            <strong>Admin:</strong> username: admin, password: password<br>
            <strong>Kasir:</strong> username: kasir, password: password
        </div>
        
        <form method="POST">
            <div class="mb-3">
                <label for="username" class="form-label">Username</label>
                <input type="text" class="form-control" id="username" name="username" required>
            </div>
            <div class="mb-3">
                <label for="password" class="form-label">Password</label>
                <div class="input-group">
                    <input type="password" class="form-control" id="password" name="password" required>
                    <button class="btn btn-outline-secondary" type="button" onclick="togglePassword()">
                        <i class="fas fa-eye" id="togglePasswordIcon"></i>
                    </button>
                </div>
            </div>
            <button type="submit" class="btn btn-primary">
                <i class="fas fa-sign-in-alt"></i> Login
            </button>
        </form>
    </div>

    <script>
        // Dark mode toggle functionality
        function toggleTheme() {
            const body = document.body;
            const themeIcon = document.getElementById('themeIcon');
            
            body.classList.toggle('light-mode');
            
            if (body.classList.contains('light-mode')) {
                themeIcon.className = 'fas fa-sun';
                localStorage.setItem('theme', 'light');
            } else {
                themeIcon.className = 'fas fa-moon';
                localStorage.setItem('theme', 'dark');
            }
        }

        // Toggle password visibility
        function togglePassword() {
            const passwordField = document.getElementById('password');
            const toggleIcon = document.getElementById('togglePasswordIcon');
            
            if (passwordField.type === 'password') {
                passwordField.type = 'text';
                toggleIcon.className = 'fas fa-eye-slash';
            } else {
                passwordField.type = 'password';
                toggleIcon.className = 'fas fa-eye';
            }
        }

        // Load saved theme
        document.addEventListener('DOMContentLoaded', function() {
            const savedTheme = localStorage.getItem('theme');
            const themeIcon = document.getElementById('themeIcon');
            
            if (savedTheme === 'light') {
                document.body.classList.add('light-mode');
                themeIcon.className = 'fas fa-sun';
            } else {
                themeIcon.className = 'fas fa-moon';
            }
        });
    </script>
</body>
</html>
