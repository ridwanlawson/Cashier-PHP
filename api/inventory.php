<?php
// Prevent any output before JSON
ob_start();

session_start();
require_once '../auth.php';
require_once '../config/database.php';

// Check if user is logged in
$auth = new Auth();
if (!$auth->isLoggedIn()) {
    ob_clean();
    http_response_code(401);
    echo json_encode(['success' => false, 'error' => 'Unauthorized']);
    exit;
}

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    ob_clean();
    exit(0);
}

try {
    $database = new Database();
    $db = $database->getConnection();

    if (!$db) {
        throw new Exception('Database connection failed');
    }

    $method = $_SERVER['REQUEST_METHOD'];

    // Create inventory_log table if it doesn't exist
    $createTableQuery = "CREATE TABLE IF NOT EXISTS inventory_log (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        product_id INTEGER NOT NULL,
        quantity INTEGER NOT NULL,
        stock_before INTEGER NOT NULL,
        stock_after INTEGER NOT NULL,
        notes TEXT,
        created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (product_id) REFERENCES products(id)
    )";
    $db->exec($createTableQuery);

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

            // Clear any output buffer content
            if (ob_get_level()) {
                ob_clean();
            }
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
            if (!isset($data['product_id']) || !isset($data['quantity']) || !isset($data['purchase_price'])) {
                throw new Exception('Product ID, quantity, and purchase price are required');
            }

            $productId = (int)$data['product_id'];
            $quantity = (int)$data['quantity'];
            $purchasePrice = (float)$data['purchase_price'];
            $marginType = isset($data['margin_type']) ? $data['margin_type'] : 'percentage'; // percentage or fixed
            $marginValue = isset($data['margin_value']) ? (float)$data['margin_value'] : 0;
            $notes = isset($data['notes']) ? trim($data['notes']) : '';

            if ($quantity <= 0) {
                throw new Exception('Quantity must be greater than 0');
            }

            if ($purchasePrice <= 0) {
                throw new Exception('Purchase price must be greater than 0');
            }

            // Calculate selling price based on margin
            $sellingPrice = $purchasePrice;
            if ($marginValue > 0) {
                if ($marginType === 'percentage') {
                    $sellingPrice = $purchasePrice + ($purchasePrice * $marginValue / 100);
                } else {
                    $sellingPrice = $purchasePrice + $marginValue;
                }
            }

            // Begin transaction
            $db->beginTransaction();

            try {
                // Get current product stock
                $stmt = $db->prepare("SELECT stock FROM products WHERE id = ?");
                $stmt->execute([$productId]);
                $product = $stmt->fetch(PDO::FETCH_ASSOC);

                if (!$product) {
                    throw new Exception('Product not found');
                }

                $stockBefore = (int)$product['stock'];
                $stockAfter = $stockBefore + $quantity;

                // Update product stock and price
                $stmt = $db->prepare("UPDATE products SET stock = ?, price = ? WHERE id = ?");
                $stmt->execute([$stockAfter, $sellingPrice, $productId]);

                // Insert inventory log 
                $stmt = $db->prepare("INSERT INTO inventory_log (product_id, quantity, stock_before, stock_after, notes) VALUES (?, ?, ?, ?, ?)");
                $stmt->execute([$productId, $quantity, $stockBefore, $stockAfter, $notes]);

                $db->commit();

                if (ob_get_level()) {
                    ob_clean();
                }
                echo json_encode([
                    'success' => true,
                    'message' => 'Stock added successfully',
                    'stock_before' => $stockBefore,
                    'stock_after' => $stockAfter
                ]);

            } catch (Exception $e) {
                $db->rollBack();
                throw $e;
            }
            break;

        default:
            throw new Exception('Method not allowed');
    }

} catch (Exception $e) {
    // Clear any output buffer content
    if (ob_get_level()) {
        ob_clean();
    }
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}
?>