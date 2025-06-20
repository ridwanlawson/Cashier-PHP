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

    if (!$db) {
        throw new Exception('Database connection failed');
    }

    // Get total products
    $query = "SELECT COUNT(*) as total_products FROM products";
    $stmt = $db->prepare($query);
    $stmt->execute();
    $totalProducts = $stmt->fetch(PDO::FETCH_ASSOC)['total_products'];

    // Get today's transactions
    $query = "SELECT COUNT(*) as today_transactions, COALESCE(SUM(total), 0) as today_revenue 
              FROM transactions 
              WHERE DATE(transaction_date) = DATE('now')";
    $stmt = $db->prepare($query);
    $stmt->execute();
    $todayStats = $stmt->fetch(PDO::FETCH_ASSOC);

    // Get monthly transactions
    $query = "SELECT COUNT(*) as monthly_transactions, COALESCE(SUM(total), 0) as monthly_revenue 
              FROM transactions 
              WHERE strftime('%Y-%m', transaction_date) = strftime('%Y-%m', 'now')";
    $stmt = $db->prepare($query);
    $stmt->execute();
    $monthlyStats = $stmt->fetch(PDO::FETCH_ASSOC);

    // Get low stock count
    $query = "SELECT COUNT(*) as low_stock FROM products WHERE stock < 10";
    $stmt = $db->prepare($query);
    $stmt->execute();
    $lowStock = $stmt->fetch(PDO::FETCH_ASSOC)['low_stock'];

    // Get best selling product
    $query = "SELECT p.name as best_product 
              FROM transaction_items ti 
              JOIN products p ON ti.product_id = p.id 
              GROUP BY ti.product_id 
              ORDER BY SUM(ti.quantity) DESC 
              LIMIT 1";
    $stmt = $db->prepare($query);
    $stmt->execute();
    $bestProduct = $stmt->fetch(PDO::FETCH_ASSOC);

    // Calculate average transaction
    $avgTransaction = $monthlyStats['monthly_transactions'] > 0 ? 
        $monthlyStats['monthly_revenue'] / $monthlyStats['monthly_transactions'] : 0;

    $stats = [
        'total_products' => (int)$totalProducts,
        'today_transactions' => (int)$todayStats['today_transactions'],
        'today_revenue' => (float)$todayStats['today_revenue'],
        'monthly_transactions' => (int)$monthlyStats['monthly_transactions'],
        'monthly_revenue' => (float)$monthlyStats['monthly_revenue'],
        'avg_transaction' => (float)$avgTransaction,
        'low_stock' => (int)$lowStock,
        'best_product' => $bestProduct ? $bestProduct['best_product'] : 'N/A'
    ];

    // Clean output buffer and send JSON
    if (ob_get_level()) {
        ob_clean();
    }
    echo json_encode($stats);
    if (ob_get_level()) {
        ob_end_flush();
    }

} catch (Exception $e) {
    if (ob_get_level()) {
        ob_clean();
    }
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
    if (ob_get_level()) {
        ob_end_flush();
    }
}
?>