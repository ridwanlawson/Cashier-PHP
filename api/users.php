<?php
session_start();
require_once '../auth.php';
require_once '../config/database.php';

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

try {
    // Check if user is logged in and is admin
    $auth = new Auth();
    if (!$auth->isLoggedIn() || $auth->getUser()['role'] !== 'admin') {
        http_response_code(401);
        echo json_encode(['success' => false, 'error' => 'Unauthorized']);
        exit;
    }

    $database = new Database();
    $db = $database->getConnection();
    
    if (!$db) {
        throw new Exception('Database connection failed');
    }

    $method = $_SERVER['REQUEST_METHOD'];

    switch($method) {
        case 'GET':
            $query = "SELECT id, username, name, role, created_at FROM users ORDER BY username";
            $stmt = $db->prepare($query);
            $stmt->execute();
            $users = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            foreach($users as &$user) {
                $user['id'] = (int)$user['id'];
            }
            
            echo json_encode($users);
            break;
            
        case 'POST':
            $input = file_get_contents("php://input");
            $data = json_decode($input, true);
            
            if (!isset($data['username']) || !isset($data['name']) || !isset($data['password']) || !isset($data['role'])) {
                throw new Exception('Username, name, password, and role are required');
            }
            
            // Check if username already exists
            $checkQuery = "SELECT id FROM users WHERE username = ?";
            $checkStmt = $db->prepare($checkQuery);
            $checkStmt->execute([$data['username']]);
            if ($checkStmt->fetch()) {
                throw new Exception('Username sudah digunakan');
            }
            
            $query = "INSERT INTO users (username, password, name, role, created_at) VALUES (?, ?, ?, ?, datetime('now'))";
            $stmt = $db->prepare($query);
            $result = $stmt->execute([
                $data['username'],
                password_hash($data['password'], PASSWORD_DEFAULT),
                $data['name'],
                $data['role']
            ]);
            
            if ($result) {
                echo json_encode(['success' => true, 'message' => 'User berhasil ditambahkan']);
            } else {
                throw new Exception('Failed to add user');
            }
            break;
            
        case 'PUT':
            $input = file_get_contents("php://input");
            $data = json_decode($input, true);
            
            if (!isset($data['id'])) {
                throw new Exception('User ID is required');
            }
            
            $fields = [];
            $values = [];
            
            if (isset($data['username'])) {
                // Check if username already exists for different user
                $checkQuery = "SELECT id FROM users WHERE username = ? AND id != ?";
                $checkStmt = $db->prepare($checkQuery);
                $checkStmt->execute([$data['username'], $data['id']]);
                if ($checkStmt->fetch()) {
                    throw new Exception('Username sudah digunakan');
                }
                
                $fields[] = "username = ?";
                $values[] = $data['username'];
            }
            
            if (isset($data['name'])) {
                $fields[] = "name = ?";
                $values[] = $data['name'];
            }
            
            if (isset($data['password']) && !empty($data['password'])) {
                $fields[] = "password = ?";
                $values[] = password_hash($data['password'], PASSWORD_DEFAULT);
            }
            
            if (isset($data['role'])) {
                $fields[] = "role = ?";
                $values[] = $data['role'];
            }
            
            if (empty($fields)) {
                throw new Exception('No data to update');
            }
            
            $values[] = (int)$data['id'];
            $query = "UPDATE users SET " . implode(', ', $fields) . " WHERE id = ?";
            $stmt = $db->prepare($query);
            $result = $stmt->execute($values);
            
            if ($result) {
                echo json_encode(['success' => true, 'message' => 'User berhasil diupdate']);
            } else {
                throw new Exception('Failed to update user');
            }
            break;
            
        case 'DELETE':
            if (!isset($_GET['id'])) {
                throw new Exception('User ID is required');
            }
            
            $id = (int)$_GET['id'];
            
            // Don't allow deleting yourself
            $currentUser = $auth->getUser();
            if ($currentUser['id'] == $id) {
                throw new Exception('Cannot delete your own account');
            }
            
            $query = "DELETE FROM users WHERE id = ?";
            $stmt = $db->prepare($query);
            $result = $stmt->execute([$id]);
            
            if ($result) {
                echo json_encode(['success' => true, 'message' => 'User berhasil dihapus']);
            } else {
                throw new Exception('Failed to delete user');
            }
            break;
            
        default:
            throw new Exception('Method not allowed');
    }
    
} catch (Exception $e) {
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    exit(0);
}

try {
    $database = new Database();
    $db = $database->getConnection();
    $method = $_SERVER['REQUEST_METHOD'];

    switch($method) {
        case 'GET':
            $query = "SELECT id, username, name, role, created_at FROM users ORDER BY created_at DESC";
            $stmt = $db->prepare($query);
            $stmt->execute();
            $users = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            foreach($users as &$user) {
                $user['id'] = (int)$user['id'];
            }
            
            echo json_encode($users);
            break;
            
        case 'POST':
            $input = file_get_contents("php://input");
            if (!$input) {
                throw new Exception('No input data received');
            }
            
            $data = json_decode($input, true);
            if (json_last_error() !== JSON_ERROR_NONE) {
                throw new Exception('Invalid JSON data: ' . json_last_error_msg());
            }
            
            // Validate required fields
            $required = ['username', 'name', 'password', 'role'];
            foreach ($required as $field) {
                if (!isset($data[$field]) || trim($data[$field]) === '') {
                    throw new Exception("Field '$field' is required");
                }
            }
            
            // Check for duplicate username
            $query = "SELECT id FROM users WHERE username = ?";
            $stmt = $db->prepare($query);
            $stmt->execute([$data['username']]);
            if ($stmt->fetch()) {
                throw new Exception('Username already exists');
            }
            
            $query = "INSERT INTO users (username, name, password, role) VALUES (?, ?, ?, ?)";
            $stmt = $db->prepare($query);
            
            $result = $stmt->execute([
                trim($data['username']),
                trim($data['name']),
                trim($data['password']),
                trim($data['role'])
            ]);
            
            if($result) {
                echo json_encode(['success' => true, 'message' => 'User added successfully']);
            } else {
                echo json_encode(['success' => false, 'error' => 'Failed to add user']);
            }
            break;
            
        case 'PUT':
            $input = file_get_contents("php://input");
            if (!$input) {
                throw new Exception('No input data received');
            }
            
            $data = json_decode($input, true);
            if (json_last_error() !== JSON_ERROR_NONE) {
                throw new Exception('Invalid JSON data: ' . json_last_error_msg());
            }
            
            // Validate required fields
            $required = ['id', 'username', 'name', 'role'];
            foreach ($required as $field) {
                if (!isset($data[$field]) || (is_string($data[$field]) && trim($data[$field]) === '')) {
                    throw new Exception("Field '$field' is required");
                }
            }
            
            // Check for duplicate username (excluding current user)
            $query = "SELECT id FROM users WHERE username = ? AND id != ?";
            $stmt = $db->prepare($query);
            $stmt->execute([$data['username'], (int)$data['id']]);
            if ($stmt->fetch()) {
                throw new Exception('Username already exists');
            }
            
            if (isset($data['password']) && trim($data['password']) !== '') {
                $query = "UPDATE users SET username = ?, name = ?, password = ?, role = ? WHERE id = ?";
                $stmt = $db->prepare($query);
                $result = $stmt->execute([
                    trim($data['username']),
                    trim($data['name']),
                    trim($data['password']),
                    trim($data['role']),
                    (int)$data['id']
                ]);
            } else {
                $query = "UPDATE users SET username = ?, name = ?, role = ? WHERE id = ?";
                $stmt = $db->prepare($query);
                $result = $stmt->execute([
                    trim($data['username']),
                    trim($data['name']),
                    trim($data['role']),
                    (int)$data['id']
                ]);
            }
            
            if($result) {
                echo json_encode(['success' => true, 'message' => 'User updated successfully']);
            } else {
                echo json_encode(['success' => false, 'error' => 'Failed to update user']);
            }
            break;
            
        case 'DELETE':
            if (!isset($_GET['id'])) {
                throw new Exception('User ID is required');
            }
            
            $id = (int)$_GET['id'];
            
            // Prevent deleting current user
            $currentUser = $auth->getUser();
            if ($currentUser['id'] == $id) {
                throw new Exception('Cannot delete current user');
            }
            
            $query = "DELETE FROM users WHERE id = ?";
            $stmt = $db->prepare($query);
            $result = $stmt->execute([$id]);
            
            if($result) {
                echo json_encode(['success' => true, 'message' => 'User deleted successfully']);
            } else {
                echo json_encode(['success' => false, 'error' => 'Failed to delete user']);
            }
            break;
            
        default:
            throw new Exception('Method not allowed');
    }

} catch (Exception $e) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
}
?>
