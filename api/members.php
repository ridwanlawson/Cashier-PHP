
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
            if (isset($_GET['search'])) {
                $search = $_GET['search'];
                $query = "SELECT * FROM members WHERE name LIKE ? OR phone LIKE ? ORDER BY name LIMIT 10";
                $stmt = $db->prepare($query);
                $stmt->execute(["%$search%", "%$search%"]);
            } else {
                $query = "SELECT * FROM members ORDER BY name";
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
            $data = json_decode($input, true);
            
            if (!isset($data['name']) || !isset($data['phone'])) {
                throw new Exception('Name and phone are required');
            }
            
            $query = "INSERT INTO members (name, phone, points) VALUES (?, ?, 0)";
            $stmt = $db->prepare($query);
            $result = $stmt->execute([$data['name'], $data['phone']]);
            
            if ($result) {
                echo json_encode(['success' => true, 'id' => $db->lastInsertId()]);
            } else {
                throw new Exception('Failed to add member');
            }
            break;
            
        case 'PUT':
            $input = file_get_contents("php://input");
            $data = json_decode($input, true);
            
            $query = "UPDATE members SET points = points + ? WHERE id = ?";
            $stmt = $db->prepare($query);
            $result = $stmt->execute([$data['points'], $data['id']]);
            
            if ($result) {
                echo json_encode(['success' => true]);
            } else {
                throw new Exception('Failed to update points');
            }
            break;
    }
    
} catch (Exception $e) {
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}
?>
