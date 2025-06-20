<?php
// Clean any output buffers and start fresh BEFORE any output
while (ob_get_level()) {
    ob_end_clean();
}
ob_start();

require_once '../config/database.php';
require_once '../auth.php';

// Start session only if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
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
                    // Handle both 'items' and 'cart_data' column names
                    if (isset($transaction['items'])) {
                        $transaction['cart_data'] = json_decode($transaction['items'], true);
                    } else if (isset($transaction['cart_data'])) {
                        $transaction['cart_data'] = json_decode($transaction['cart_data'], true);
                    } else {
                        $transaction['cart_data'] = [];
                    }
                    echo json_encode($transaction);
                } else {
                    echo json_encode(null);
                }
            } else {
                // Get all held transactions
                $query = "SELECT * FROM held_transactions ORDER BY held_at DESC";
                $stmt = $db->prepare($query);
                $stmt->execute();
                $held = $stmt->fetchAll(PDO::FETCH_ASSOC);
                
                foreach($held as &$transaction) {
                    $transaction['id'] = (int)$transaction['id'];
                    // Handle both 'items' and 'cart_data' column names
                    if (isset($transaction['items'])) {
                        $transaction['cart_data'] = json_decode($transaction['items'], true);
                    } else if (isset($transaction['cart_data'])) {
                        $transaction['cart_data'] = json_decode($transaction['cart_data'], true);
                    } else {
                        $transaction['cart_data'] = [];
                    }
                    // Set created_at if doesn't exist
                    if (!isset($transaction['created_at']) && isset($transaction['held_at'])) {
                        $transaction['created_at'] = $transaction['held_at'];
                    }
                }
                
                // Clean output buffer and send JSON
                if (ob_get_level()) {
                    ob_clean();
                }
                echo json_encode($held);
                if (ob_get_level()) {
                    ob_end_flush();
                }
            }
            break;
            
        case 'POST':
            $input = file_get_contents("php://input");
            $data = json_decode($input, true);
            
            if (!isset($data['cart']) || !isset($data['note'])) {
                throw new Exception('Cart data and note are required');
            }
            
            // Try to insert using new schema first, fall back to old schema
            try {
                $query = "INSERT INTO held_transactions (cart_data, note, created_at) VALUES (?, ?, datetime('now'))";
                $stmt = $db->prepare($query);
                $result = $stmt->execute([
                    json_encode($data['cart']),
                    $data['note'] ?? ''
                ]);
            } catch (Exception $e) {
                // Fallback to old schema
                $query = "INSERT INTO held_transactions (items, member, payment_method, held_at) VALUES (?, ?, ?, datetime('now'))";
                $stmt = $db->prepare($query);
                $result = $stmt->execute([
                    json_encode($data['cart']),
                    $data['note'] ?? '',
                    'cash'
                ]);
            }
            
            if ($result) {
                if (ob_get_level()) {
                    ob_clean();
                }
                echo json_encode(['success' => true, 'id' => $db->lastInsertId()]);
                if (ob_get_level()) {
                    ob_end_flush();
                }
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
                if (ob_get_level()) {
                    ob_clean();
                }
                echo json_encode(['success' => true]);
                if (ob_get_level()) {
                    ob_end_flush();
                }
            } else {
                throw new Exception('Failed to delete held transaction');
            }
            break;
    }
    
} catch (Exception $e) {
    if (ob_get_level()) {
        ob_clean();
    }
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
    if (ob_get_level()) {
        ob_end_flush();
    }
}
?>
