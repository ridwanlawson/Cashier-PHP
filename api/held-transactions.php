
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
            if (isset($_GET['id'])) {
                // Get specific held transaction
                $query = "SELECT * FROM held_transactions WHERE id = ?";
                $stmt = $db->prepare($query);
                $stmt->execute([$_GET['id']]);
                $transaction = $stmt->fetch(PDO::FETCH_ASSOC);
                
                if ($transaction) {
                    $transaction['id'] = (int)$transaction['id'];
                    $transaction['cart_data'] = json_decode($transaction['cart_data'], true);
                    echo json_encode($transaction);
                } else {
                    echo json_encode(null);
                }
            } else {
                // Get all held transactions
                $query = "SELECT * FROM held_transactions ORDER BY created_at DESC";
                $stmt = $db->prepare($query);
                $stmt->execute();
                $held = $stmt->fetchAll(PDO::FETCH_ASSOC);
                
                foreach($held as &$transaction) {
                    $transaction['id'] = (int)$transaction['id'];
                    $transaction['cart_data'] = json_decode($transaction['cart_data'], true);
                }
                
                echo json_encode($held);
            }
            break;
            
        case 'POST':
            $input = file_get_contents("php://input");
            $data = json_decode($input, true);
            
            if (!isset($data['cart']) || !isset($data['note'])) {
                throw new Exception('Cart data and note are required');
            }
            
            $query = "INSERT INTO held_transactions (cart_data, note, created_at) VALUES (?, ?, datetime('now'))";
            $stmt = $db->prepare($query);
            $result = $stmt->execute([
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
            
            $query = "DELETE FROM held_transactions WHERE id = ?";
            $stmt = $db->prepare($query);
            $result = $stmt->execute([$_GET['id']]);
            
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
