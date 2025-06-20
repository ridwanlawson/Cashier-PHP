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
            if(isset($_GET['id'])) {
                // Get single product
                $id = (int)$_GET['id'];
                $query = "SELECT * FROM products WHERE id = ?";
                $stmt = $db->prepare($query);
                $stmt->execute([$id]);
                $product = $stmt->fetch(PDO::FETCH_ASSOC);

                if ($product) {
                    $product['id'] = (int)$product['id'];
                    $product['price'] = (float)$product['price'];
                    $product['stock'] = (int)$product['stock'];
                }

                // Clean output buffer and send JSON
                if (ob_get_level()) {
                    ob_clean();
                }
                echo json_encode($product ? $product : null);
                if (ob_get_level()) {
                    ob_end_flush();
                }
            } elseif(isset($_GET['lowstock'])) {
                // Get low stock products
                $limit = (int)$_GET['lowstock'];
                $query = "SELECT * FROM products WHERE stock <= 10 ORDER BY stock ASC LIMIT ?";
                $stmt = $db->prepare($query);
                $stmt->execute([$limit]);
                $products = $stmt->fetchAll(PDO::FETCH_ASSOC);

                // Convert numeric fields
                foreach($products as &$product) {
                    $product['id'] = (int)$product['id'];
                    $product['price'] = (float)$product['price'];
                    $product['stock'] = (int)$product['stock'];
                }

                // Clean output buffer and send JSON
                if (ob_get_level()) {
                    ob_clean();
                }
                echo json_encode($products);
                if (ob_get_level()) {
                    ob_end_flush();
                }
            } elseif(isset($_GET['search'])) {
                // Search products
                $search = '%' . $_GET['search'] . '%';
                $query = "SELECT * FROM products WHERE name LIKE ? OR barcode LIKE ? OR category LIKE ? ORDER BY name LIMIT 10";
                $stmt = $db->prepare($query);
                $stmt->execute([$search, $search, $search]);
                $products = $stmt->fetchAll(PDO::FETCH_ASSOC);

                // Convert numeric fields
                foreach($products as &$product) {
                    $product['id'] = (int)$product['id'];
                    $product['price'] = (float)$product['price'];
                    $product['stock'] = (int)$product['stock'];
                }

                // Clean output buffer and send JSON
                if (ob_get_level()) {
                    ob_clean();
                }
                echo json_encode($products);
                if (ob_get_level()) {
                    ob_end_flush();
                }
            } elseif(isset($_GET['barcode'])) {
                // Search by barcode
                $barcode = $_GET['barcode'];
                $query = "SELECT * FROM products WHERE barcode = ?";
                $stmt = $db->prepare($query);
                $stmt->execute([$barcode]);
                $product = $stmt->fetch(PDO::FETCH_ASSOC);

                if ($product) {
                    $product['id'] = (int)$product['id'];
                    $product['price'] = (float)$product['price'];
                    $product['stock'] = (int)$product['stock'];
                }

                // Clean output buffer and send JSON
                if (ob_get_level()) {
                    ob_clean();
                }
                echo json_encode($product ? $product : null);
                if (ob_get_level()) {
                    ob_end_flush();
                }
            } else {
                // Get all products
                $query = "SELECT * FROM products ORDER BY name";
                $stmt = $db->prepare($query);
                $stmt->execute();
                $products = $stmt->fetchAll(PDO::FETCH_ASSOC);

                // Convert numeric fields
                foreach($products as &$product) {
                    $product['id'] = (int)$product['id'];
                    $product['price'] = (float)$product['price'];
                    $product['stock'] = (int)$product['stock'];
                }

                // Clean output buffer and send JSON
                if (ob_get_level()) {
                    ob_clean();
                }
                echo json_encode($products);
                if (ob_get_level()) {
                    ob_end_flush();
                }
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

            // Validate required fields
            $required = ['name', 'category', 'price', 'stock', 'barcode'];
            foreach ($required as $field) {
                if (!isset($data[$field]) || (is_string($data[$field]) && trim($data[$field]) === '')) {
                    throw new Exception("Field '$field' is required");
                }
            }

            // Check for duplicate barcode
            $query = "SELECT id FROM products WHERE barcode = ?";
            $stmt = $db->prepare($query);
            $stmt->execute([$data['barcode']]);
            if ($stmt->fetch()) {
                throw new Exception('Barcode already exists');
            }

            $query = "INSERT INTO products (name, category, price, stock, barcode) VALUES (?, ?, ?, ?, ?)";
            $stmt = $db->prepare($query);

            $result = $stmt->execute([
                trim($data['name']),
                trim($data['category']),
                (float)$data['price'],
                (int)$data['stock'],
                trim($data['barcode'])
            ]);

            ob_clean();
            if($result) {
                echo json_encode(['success' => true, 'message' => 'Product added successfully']);
            } else {
                echo json_encode(['success' => false, 'error' => 'Failed to add product']);
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

            // Validate required fields
            $required = ['id', 'name', 'category', 'price', 'stock', 'barcode'];
            foreach ($required as $field) {
                if (!isset($data[$field]) || (is_string($data[$field]) && trim($data[$field]) === '')) {
                    throw new Exception("Field '$field' is required");
                }
            }

            // Check for duplicate barcode (excluding current product)
            $query = "SELECT id FROM products WHERE barcode = ? AND id != ?";
            $stmt = $db->prepare($query);
            $stmt->execute([$data['barcode'], (int)$data['id']]);
            if ($stmt->fetch()) {
                throw new Exception('Barcode already exists');
            }

            $query = "UPDATE products SET name = ?, category = ?, price = ?, stock = ?, barcode = ? WHERE id = ?";
            $stmt = $db->prepare($query);

            $result = $stmt->execute([
                trim($data['name']),
                trim($data['category']),
                (float)$data['price'],
                (int)$data['stock'],
                trim($data['barcode']),
                (int)$data['id']
            ]);

            ob_clean();
            if($result) {
                echo json_encode(['success' => true, 'message' => 'Product updated successfully']);
            } else {
                echo json_encode(['success' => false, 'error' => 'Failed to update product']);
            }
            break;

        case 'DELETE':
            if (!isset($_GET['id'])) {
                throw new Exception('Product ID is required');
            }

            $id = (int)$_GET['id'];

            // Check if product exists in any transactions
            $query = "SELECT COUNT(*) as count FROM transaction_items WHERE product_id = ?";
            $stmt = $db->prepare($query);
            $stmt->execute([$id]);
            $result = $stmt->fetch(PDO::FETCH_ASSOC);

            if ($result['count'] > 0) {
                throw new Exception('Cannot delete product that has transaction history');
            }

            $query = "DELETE FROM products WHERE id = ?";
            $stmt = $db->prepare($query);
            $result = $stmt->execute([$id]);

            ob_clean();
            if($result) {
                echo json_encode(['success' => true, 'message' => 'Product deleted successfully']);
            } else {
                echo json_encode(['success' => false, 'error' => 'Failed to delete product']);
            }
            break;

        default:
            ob_clean();
            echo json_encode(['success' => false, 'error' => 'Method not allowed']);
            break;
    }
} catch(Exception $e) {
    ob_clean();
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}
?>