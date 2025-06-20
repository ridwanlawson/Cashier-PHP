<?php
session_start();
require_once '../auth.php';
require_once '../config/database.php';

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE');
header('Access-Control-Allow-Headers: Content-Type');

$auth = new Auth();
$auth->requireLogin();

try {
    $database = new Database();
    $db = $database->getConnection();
    $method = $_SERVER['REQUEST_METHOD'];

    switch($method) {
        case 'GET':
            if (isset($_GET['id'])) {
                $id = (int)$_GET['id'];
                $query = "SELECT * FROM members WHERE id = ?";
                $stmt = $db->prepare($query);
                $stmt->execute([$id]);
                $member = $stmt->fetch(PDO::FETCH_ASSOC);
                
                if ($member) {
                    $member['id'] = (int)$member['id'];
                    $member['points'] = (int)$member['points'];
                    echo json_encode($member);
                } else {
                    echo json_encode(null);
                }
            } elseif (isset($_GET['search'])) {
                $search = $_GET['search'];
                $query = "SELECT * FROM members WHERE name LIKE ? OR phone LIKE ? ORDER BY name LIMIT 10";
                $stmt = $db->prepare($query);
                $stmt->execute(["%$search%", "%$search%"]);
                $members = $stmt->fetchAll(PDO::FETCH_ASSOC);
                
                foreach($members as &$member) {
                    $member['id'] = (int)$member['id'];
                    $member['points'] = (int)$member['points'];
                }
                
                echo json_encode($members);
            } else {
                $query = "SELECT * FROM members ORDER BY name";
                $stmt = $db->prepare($query);
                $stmt->execute();
                $members = $stmt->fetchAll(PDO::FETCH_ASSOC);
                
                foreach($members as &$member) {
                    $member['id'] = (int)$member['id'];
                    $member['points'] = (int)$member['points'];
                }
                
                // Store members for reference
                echo json_encode($members);
            }
            break;
            
        case 'POST':
            $input = file_get_contents("php://input");
            $data = json_decode($input, true);
            
            if (!isset($data['name']) || !isset($data['phone'])) {
                throw new Exception('Name and phone are required');
            }
            
            // Check if phone already exists
            $checkQuery = "SELECT id FROM members WHERE phone = ?";
            $checkStmt = $db->prepare($checkQuery);
            $checkStmt->execute([$data['phone']]);
            if ($checkStmt->fetch()) {
                throw new Exception('Nomor telepon sudah terdaftar');
            }
            
            $points = isset($data['points']) ? (int)$data['points'] : 0;
            $query = "INSERT INTO members (name, phone, points, created_at) VALUES (?, ?, ?, datetime('now'))";
            $stmt = $db->prepare($query);
            $result = $stmt->execute([$data['name'], $data['phone'], $points]);
            
            if ($result) {
                echo json_encode(['success' => true, 'id' => $db->lastInsertId()]);
            } else {
                throw new Exception('Failed to add member');
            }
            break;
            
        case 'PUT':
            $input = file_get_contents("php://input");
            $data = json_decode($input, true);
            
            if (!isset($data['id'])) {
                throw new Exception('Member ID is required');
            }
            
            $fields = [];
            $values = [];
            
            if (isset($data['name'])) {
                $fields[] = "name = ?";
                $values[] = $data['name'];
            }
            
            if (isset($data['phone'])) {
                // Check if phone already exists for different member
                $checkQuery = "SELECT id FROM members WHERE phone = ? AND id != ?";
                $checkStmt = $db->prepare($checkQuery);
                $checkStmt->execute([$data['phone'], $data['id']]);
                if ($checkStmt->fetch()) {
                    throw new Exception('Nomor telepon sudah terdaftar');
                }
                
                $fields[] = "phone = ?";
                $values[] = $data['phone'];
            }
            
            if (isset($data['points'])) {
                $fields[] = "points = ?";
                $values[] = (int)$data['points'];
            }
            
            if (empty($fields)) {
                throw new Exception('No data to update');
            }
            
            $values[] = (int)$data['id'];
            $query = "UPDATE members SET " . implode(', ', $fields) . " WHERE id = ?";
            $stmt = $db->prepare($query);
            $result = $stmt->execute($values);
            
            if ($result) {
                echo json_encode(['success' => true]);
            } else {
                throw new Exception('Failed to update member');
            }
            break;
            
        case 'DELETE':
            if (!isset($_GET['id'])) {
                throw new Exception('Member ID is required');
            }
            
            $id = (int)$_GET['id'];
            
            // Check if member has transactions
            $checkQuery = "SELECT COUNT(*) as count FROM transactions WHERE member_id = ?";
            $checkStmt = $db->prepare($checkQuery);
            $checkStmt->execute([$id]);
            $result = $checkStmt->fetch(PDO::FETCH_ASSOC);
            
            if ($result['count'] > 0) {
                throw new Exception('Cannot delete member with existing transactions');
            }
            
            $query = "DELETE FROM members WHERE id = ?";
            $stmt = $db->prepare($query);
            $result = $stmt->execute([$id]);
            
            if ($result) {
                echo json_encode(['success' => true]);
            } else {
                throw new Exception('Failed to delete member');
            }
            break;
            
        default:
            throw new Exception('Method not allowed');
    }
    
} catch (Exception $e) {
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}
?>
<?php
session_start();
require_once '../auth.php';
require_once '../config/database.php';

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    exit(0);
}

try {
    $auth = new Auth();
    if (!$auth->isLoggedIn()) {
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
            if (isset($_GET['search'])) {
                $search = '%' . $_GET['search'] . '%';
                $query = "SELECT * FROM members WHERE name LIKE ? OR phone LIKE ? ORDER BY name ASC LIMIT 10";
                $stmt = $db->prepare($query);
                $stmt->execute([$search, $search]);
            } else {
                $query = "SELECT * FROM members ORDER BY created_at DESC";
                $stmt = $db->prepare($query);
                $stmt->execute();
            }
            
            $members = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            foreach($members as &$member) {
                $member['id'] = (int)$member['id'];
                $member['points'] = (int)$member['points'];
            }

            echo json_encode($members);
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

            $required = ['name', 'phone'];
            foreach ($required as $field) {
                if (!isset($data[$field]) || trim($data[$field]) === '') {
                    throw new Exception("Field '$field' is required");
                }
            }

            // Check for duplicate phone
            $query = "SELECT id FROM members WHERE phone = ?";
            $stmt = $db->prepare($query);
            $stmt->execute([$data['phone']]);
            if ($stmt->fetch()) {
                throw new Exception('Phone number already exists');
            }

            $query = "INSERT INTO members (name, phone, points, created_at) VALUES (?, ?, ?, datetime('now'))";
            $stmt = $db->prepare($query);

            $result = $stmt->execute([
                trim($data['name']),
                trim($data['phone']),
                (int)($data['points'] ?? 0)
            ]);

            if($result) {
                echo json_encode(['success' => true, 'message' => 'Member added successfully']);
            } else {
                echo json_encode(['success' => false, 'error' => 'Failed to add member']);
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

            $required = ['id', 'name', 'phone'];
            foreach ($required as $field) {
                if (!isset($data[$field]) || (is_string($data[$field]) && trim($data[$field]) === '')) {
                    throw new Exception("Field '$field' is required");
                }
            }

            // Check for duplicate phone (excluding current member)
            $query = "SELECT id FROM members WHERE phone = ? AND id != ?";
            $stmt = $db->prepare($query);
            $stmt->execute([$data['phone'], (int)$data['id']]);
            if ($stmt->fetch()) {
                throw new Exception('Phone number already exists');
            }

            $query = "UPDATE members SET name = ?, phone = ?, points = ? WHERE id = ?";
            $stmt = $db->prepare($query);
            $result = $stmt->execute([
                trim($data['name']),
                trim($data['phone']),
                (int)($data['points'] ?? 0),
                (int)$data['id']
            ]);

            if($result) {
                echo json_encode(['success' => true, 'message' => 'Member updated successfully']);
            } else {
                echo json_encode(['success' => false, 'error' => 'Failed to update member']);
            }
            break;

        case 'DELETE':
            if (!isset($_GET['id'])) {
                throw new Exception('Member ID is required');
            }

            $id = (int)$_GET['id'];

            $query = "DELETE FROM members WHERE id = ?";
            $stmt = $db->prepare($query);
            $result = $stmt->execute([$id]);

            if($result) {
                echo json_encode(['success' => true, 'message' => 'Member deleted successfully']);
            } else {
                echo json_encode(['success' => false, 'error' => 'Failed to delete member']);
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
