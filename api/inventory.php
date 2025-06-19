<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE');
header('Access-Control-Allow-Headers: Content-Type');

// Prevent any output before JSON
ob_start();

try {
    include_once '../config/database.php';

    $database = new Database();
    $db = $database->getConnection();

    if (!$db) {
        throw new Exception('Database connection failed');
    }

    $method = $_SERVER['REQUEST_METHOD'];

    switch($method) {
        case 'GET':
            // Get inventory history
            $query = "SELECT i.*, p.name as product_name 
                     FROM inventory_log i 
                     LEFT JOIN products p ON i.product_id = p.id 
                     ORDER BY i.created_at DESC LIMIT 100";
            $stmt = $db->prepare($query);
            $stmt->execute();
            $inventory = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            // Convert numeric fields
            foreach($inventory as &$item) {
                $item['id'] = (int)$item['id'];
                $item['product_id'] = (int)$item['product_id'];
                $item['quantity'] = (int)$item['quantity'];
                $item['stock_before'] = (int)$item['stock_before'];
                $item['stock_after'] = (int)$item['stock_after'];
            }
            
            ob_clean();
            echo json_encode($inventory);
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
            if (!isset($data['product_id']) || !isset($data['quantity'])) {
                throw new Exception('Product ID and quantity are required');
            }
            
            if ((int)$data['quantity'] <= 0) {
                throw new Exception('Quantity must be greater than 0');
            }
            
            $db->beginTransaction();
            
            try {
                // Get current stock
                $query = "SELECT stock FROM products WHERE id = ?";
                $stmt = $db->prepare($query);
                $stmt->execute([(int)$data['product_id']]);
                $product = $stmt->fetch(PDO::FETCH_ASSOC);
                
                if (!$product) {
                    throw new Exception('Product not found');
                }
                
                $currentStock = (int)$product['stock'];
                $newStock = $currentStock + (int)$data['quantity'];
                
                // Update product stock
                $query = "UPDATE products SET stock = ? WHERE id = ?";
                $stmt = $db->prepare($query);
                $stmt->execute([$newStock, (int)$data['product_id']]);
                
                // Log inventory change
                $query = "INSERT INTO inventory_log (product_id, quantity, stock_before, stock_after, notes, created_at) 
                         VALUES (?, ?, ?, ?, ?, datetime('now'))";
                $stmt = $db->prepare($query);
                $stmt->execute([
                    (int)$data['product_id'],
                    (int)$data['quantity'],
                    $currentStock,
                    $newStock,
                    isset($data['notes']) ? trim($data['notes']) : null
                ]);
                
                $db->commit();
                ob_clean();
                echo json_encode(['success' => true, 'message' => 'Stock added successfully']);
                
            } catch(Exception $e) {
                $db->rollback();
                throw $e;
            }
            break;
            
        default:
            ob_clean();
            echo json_encode(['success' => false, 'error' => 'Method not allowed']);
            break;
    }
} catch(Exception $e) {
    if (isset($db) && $db && $db->inTransaction()) {
        $db->rollback();
    }
    ob_clean();
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}
?>
