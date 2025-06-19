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
            if(isset($_GET['stats'])) {
                // Get dashboard statistics
                $stats = [];

                // Total products
                $query = "SELECT COUNT(*) as total FROM products";
                $stmt = $db->prepare($query);
                $stmt->execute();
                $result = $stmt->fetch(PDO::FETCH_ASSOC);
                $stats['total_products'] = $result ? (int)$result['total'] : 0;

                // Today's transactions
                $query = "SELECT COUNT(*) as total FROM transactions WHERE DATE(transaction_date) = DATE('now')";
                $stmt = $db->prepare($query);
                $stmt->execute();
                $result = $stmt->fetch(PDO::FETCH_ASSOC);
                $stats['today_transactions'] = $result ? (int)$result['total'] : 0;

                // Today's revenue
                $query = "SELECT COALESCE(SUM(total), 0) as revenue FROM transactions WHERE DATE(transaction_date) = DATE('now')";
                $stmt = $db->prepare($query);
                $stmt->execute();
                $result = $stmt->fetch(PDO::FETCH_ASSOC);
                $stats['today_revenue'] = $result ? (float)$result['revenue'] : 0;

                // Monthly revenue
                $query = "SELECT COALESCE(SUM(total), 0) as revenue FROM transactions WHERE strftime('%Y-%m', transaction_date) = strftime('%Y-%m', 'now')";
                $stmt = $db->prepare($query);
                $stmt->execute();
                $result = $stmt->fetch(PDO::FETCH_ASSOC);
                $stats['monthly_revenue'] = $result ? (float)$result['revenue'] : 0;

                // Monthly transactions
                $query = "SELECT COUNT(*) as total FROM transactions WHERE strftime('%Y-%m', transaction_date) = strftime('%Y-%m', 'now')";
                $stmt = $db->prepare($query);
                $stmt->execute();
                $result = $stmt->fetch(PDO::FETCH_ASSOC);
                $stats['monthly_transactions'] = $result ? (int)$result['total'] : 0;

                // Average transaction
                $query = "SELECT COALESCE(AVG(total), 0) as avg_total FROM transactions WHERE strftime('%Y-%m', transaction_date) = strftime('%Y-%m', 'now')";
                $stmt = $db->prepare($query);
                $stmt->execute();
                $result = $stmt->fetch(PDO::FETCH_ASSOC);
                $stats['avg_transaction'] = $result ? (float)$result['avg_total'] : 0;

                // Best selling product
                $query = "SELECT p.name FROM transaction_items ti 
                         LEFT JOIN products p ON ti.product_id = p.id 
                         WHERE strftime('%Y-%m', (SELECT transaction_date FROM transactions WHERE id = ti.transaction_id)) = strftime('%Y-%m', 'now')
                         GROUP BY ti.product_id 
                         ORDER BY SUM(ti.quantity) DESC 
                         LIMIT 1";
                $stmt = $db->prepare($query);
                $stmt->execute();
                $result = $stmt->fetch(PDO::FETCH_ASSOC);
                $stats['best_product'] = $result ? $result['name'] : '-';

                // Low stock products (stock <= 10)
                $query = "SELECT COUNT(*) as total FROM products WHERE stock <= 10";
                $stmt = $db->prepare($query);
                $stmt->execute();
                $result = $stmt->fetch(PDO::FETCH_ASSOC);
                $stats['low_stock'] = $result ? (int)$result['total'] : 0;

                ob_clean();
                echo json_encode($stats);

            } elseif(isset($_GET['recent'])) {
                // Get recent transactions
                $limit = (int)$_GET['recent'];
                $query = "SELECT id, transaction_date, total FROM transactions ORDER BY transaction_date DESC LIMIT ?";
                $stmt = $db->prepare($query);
                $stmt->execute([$limit]);
                $transactions = $stmt->fetchAll(PDO::FETCH_ASSOC);

                foreach($transactions as &$transaction) {
                    $transaction['id'] = (int)$transaction['id'];
                    $transaction['total'] = (float)$transaction['total'];
                }

                ob_clean();
                echo json_encode($transactions);

            } elseif(isset($_GET['id'])) {
                // Get transaction detail
                $id = (int)$_GET['id'];

                $query = "SELECT t.*, ti.product_id, ti.quantity, ti.price, ti.subtotal, p.name as product_name 
                         FROM transactions t 
                         LEFT JOIN transaction_items ti ON t.id = ti.transaction_id 
                         LEFT JOIN products p ON ti.product_id = p.id 
                         WHERE t.id = ?";
                $stmt = $db->prepare($query);
                $stmt->execute([$id]);
                $results = $stmt->fetchAll(PDO::FETCH_ASSOC);

                if($results && count($results) > 0) {
                    $transaction = [
                        'id' => (int)$results[0]['id'],
                        'transaction_date' => $results[0]['transaction_date'],
                        'total' => (float)$results[0]['total'],
                        'items' => []
                    ];

                    foreach($results as $row) {
                        if($row['product_id']) {
                            $transaction['items'][] = [
                                'product_name' => $row['product_name'],
                                'quantity' => (int)$row['quantity'],
                                'price' => (float)$row['price'],
                                'subtotal' => (float)$row['subtotal']
                            ];
                        }
                    }

                    ob_clean();
                    echo json_encode($transaction);
                } else {
                    ob_clean();
                    echo json_encode(null);
                }
            } else {
                // Get all transactions
                $query = "SELECT id, transaction_date, total FROM transactions ORDER BY transaction_date DESC LIMIT 100";
                $stmt = $db->prepare($query);
                $stmt->execute();
                $transactions = $stmt->fetchAll(PDO::FETCH_ASSOC);

                // Convert numeric fields
                foreach($transactions as &$transaction) {
                    $transaction['id'] = (int)$transaction['id'];
                    $transaction['total'] = (float)$transaction['total'];
                }

                ob_clean();
                echo json_encode($transactions);
            }
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

            if (!isset($data['total']) || !isset($data['items']) || !is_array($data['items'])) {
                throw new Exception('Missing required fields: total and items');
            }

            if (empty($data['items'])) {
                throw new Exception('No items in transaction');
            }

            $db->beginTransaction();

            try {
                $subtotal = $data['subtotal'] ?? $data['total']; // Backward compatibility
                $taxAmount = $data['tax_amount'] ?? 0;
                $total = $data['total'];
                $items = $data['items'];

                // Insert transaction
                $query = "INSERT INTO transactions (subtotal, tax_amount, total, transaction_date) VALUES (?, ?, ?, datetime('now'))";
                $stmt = $db->prepare($query);
                $stmt->execute([$subtotal, $taxAmount, $total]);

                $transaction_id = $db->lastInsertId();

                // Insert transaction items and update stock
                foreach($data['items'] as $item) {
                    if (!isset($item['product_id']) || !isset($item['quantity']) || !isset($item['price']) || !isset($item['subtotal'])) {
                        throw new Exception('Invalid item data structure');
                    }

                    // Check if product exists and has enough stock
                    $query = "SELECT stock FROM products WHERE id = ?";
                    $stmt = $db->prepare($query);
                    $stmt->execute([(int)$item['product_id']]);
                    $product = $stmt->fetch(PDO::FETCH_ASSOC);

                    if (!$product) {
                        throw new Exception('Product not found: ' . $item['product_id']);
                    }

                    if ($product['stock'] < $item['quantity']) {
                        throw new Exception('Insufficient stock for product ID: ' . $item['product_id']);
                    }

                    // Insert transaction item
                    $query = "INSERT INTO transaction_items (transaction_id, product_id, quantity, price, subtotal) 
                             VALUES (?, ?, ?, ?, ?)";
                    $stmt = $db->prepare($query);
                    $stmt->execute([
                        $transaction_id,
                        (int)$item['product_id'],
                        (int)$item['quantity'],
                        (float)$item['price'],
                        (float)$item['subtotal']
                    ]);

                    // Get current stock before update
                    $currentStock = (int)$product['stock'];
                    $newStock = $currentStock - (int)$item['quantity'];

                    // Update product stock
                    $query = "UPDATE products SET stock = stock - ? WHERE id = ?";
                    $stmt = $db->prepare($query);
                    $stmt->execute([(int)$item['quantity'], (int)$item['product_id']]);

                    // Log inventory change for tracking
                    $query = "INSERT INTO inventory_log (product_id, quantity, stock_before, stock_after, notes, created_at) 
                             VALUES (?, ?, ?, ?, ?, datetime('now'))";
                    $stmt = $db->prepare($query);
                    $stmt->execute([
                        (int)$item['product_id'],
                        -(int)$item['quantity'], // Negative untuk penjualan
                        $currentStock,
                        $newStock,
                        'Penjualan - Transaksi #' . $transaction_id
                    ]);
                }

                $db->commit();
                ob_clean();
                echo json_encode(['success' => true, 'transaction_id' => (int)$transaction_id]);

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