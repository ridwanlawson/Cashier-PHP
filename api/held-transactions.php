
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
    $user = $auth->getUser();

    switch($method) {
        case 'GET':
            // Get held transactions for current user
            $query = "SELECT * FROM held_transactions WHERE user_id = ? ORDER BY created_at DESC";
            $stmt = $db->prepare($query);
            $stmt->execute([$user['id']]);
            $held = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            foreach($held as &$transaction) {
                $transaction['id'] = (int)$transaction['id'];
                $transaction['cart_data'] = json_decode($transaction['cart_data'], true);
            }
            
            echo json_encode($held);
            break;
            
        case 'POST':
            $input = file_get_contents("php://input");
            $data = json_decode($input, true);
            
            if (!isset($data['cart']) || !isset($data['note'])) {
                throw new Exception('Cart data and note are required');
            }
            
            $query = "INSERT INTO held_transactions (user_id, cart_data, note, created_at) VALUES (?, ?, ?, datetime('now'))";
            $stmt = $db->prepare($query);
            $result = $stmt->execute([
                $user['id'],
                json_encode($data['cart']),
                $data['note']
            ]);
            
            if ($result) {
                echo json_encode(['success' => true, 'id' => $db->lastInsertId()]);
            } else {
                throw new Exception('Failed to hold transaction');
            }
            break;
            
        case 'DELETE':
            if (!isset($_GET['id'])) {
                throw new Exception('Transaction ID required');
            }
            
            $query = "DELETE FROM held_transactions WHERE id = ? AND user_id = ?";
            $stmt = $db->prepare($query);
            $result = $stmt->execute([$_GET['id'], $user['id']]);
            
            if ($result) {
                echo json_encode(['success' => true]);
            } else {
                throw new Exception('Failed to delete held transaction');
            }
            break;
    }
    
} catch (Exception $e) {
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}
?>
