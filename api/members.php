<?php
session_start();
require_once '../auth.php';
require_once '../config/database.php';

header('Content-Type: application/json');

$auth = new Auth();
$auth->requireLogin();

try {
    $database = new Database();
    $db = $database->getConnection();
    $method = $_SERVER['REQUEST_METHOD'];

    switch($method) {
        case 'GET':
            if (isset($_GET['search'])) {
                // Search members
                $search = '%' . $_GET['search'] . '%';
                $query = "SELECT * FROM members WHERE name LIKE ? OR phone LIKE ? ORDER BY name ASC";
                $stmt = $db->prepare($query);
                $stmt->execute([$search, $search]);
                $members = $stmt->fetchAll(PDO::FETCH_ASSOC);

                foreach($members as &$member) {
                    $member['id'] = (int)$member['id'];
                    $member['points'] = (int)$member['points'];
                }

                echo json_encode($members);
            } else {
                // Get all members
                $query = "SELECT * FROM members ORDER BY created_at DESC";
                $stmt = $db->prepare($query);
                $stmt->execute();
                $members = $stmt->fetchAll(PDO::FETCH_ASSOC);

                foreach($members as &$member) {
                    $member['id'] = (int)$member['id'];
                    $member['points'] = (int)$member['points'];
                }

                echo json_encode($members);
            }
            break;

        case 'POST':
            $input = file_get_contents("php://input");
            $data = json_decode($input, true);

            if (!isset($data['name']) || !isset($data['phone'])) {
                throw new Exception('Name and phone are required');
            }

            $query = "INSERT INTO members (name, phone, points) VALUES (?, ?, ?)";
            $stmt = $db->prepare($query);
            $result = $stmt->execute([
                $data['name'],
                $data['phone'],
                $data['points'] ?? 0
            ]);

            if ($result) {
                echo json_encode(['success' => true, 'id' => $db->lastInsertId()]);
            } else {
                throw new Exception('Failed to add member');
            }
            break;

        case 'PUT':
            $input = file_get_contents("php://input");
            $data = json_decode($input, true);

            if (!isset($data['id']) || !isset($data['name']) || !isset($data['phone'])) {
                throw new Exception('ID, name and phone are required');
            }

            $query = "UPDATE members SET name = ?, phone = ?, points = ? WHERE id = ?";
            $stmt = $db->prepare($query);
            $result = $stmt->execute([
                $data['name'],
                $data['phone'],
                $data['points'] ?? 0,
                $data['id']
            ]);

            if ($result) {
                echo json_encode(['success' => true]);
            } else {
                throw new Exception('Failed to update member');
            }
            break;

        case 'DELETE':
            if (!isset($_GET['id'])) {
                throw new Exception('Member ID required');
            }

            $query = "DELETE FROM members WHERE id = ?";
            $stmt = $db->prepare($query);
            $result = $stmt->execute([$_GET['id']]);

            if ($result) {
                echo json_encode(['success' => true]);
            } else {
                throw new Exception('Failed to delete member');
            }
            break;
    }

} catch (Exception $e) {
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}
?>