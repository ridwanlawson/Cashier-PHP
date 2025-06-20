<?php
// Start output buffering immediately and clean any existing output
if (ob_get_level()) {
    ob_end_clean();
}
ob_start();

// Start session first, before any includes
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once '../config/database.php';
require_once '../auth.php';

header('Content-Type: application/json');

$auth = new Auth();
$auth->requireLogin();

$user = $auth->getUser();
$method = $_SERVER['REQUEST_METHOD'];

// For GET requests, allow all authenticated users to view settings
// For POST requests, only admin can modify settings

try {
    $database = new Database();
    $db = $database->getConnection();

    if (!$db) {
        throw new Exception('Database connection failed');
    }

    switch($method) {
        case 'GET':
            // Get settings - allow all authenticated users to read settings
            $query = "SELECT * FROM app_settings LIMIT 1";
            $stmt = $db->prepare($query);
            $stmt->execute();
            $settings = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$settings) {
                // Return default settings if none exist
                $settings = [
                    'app_name' => 'Kasir Digital',
                    'store_name' => 'Toko ABC',
                    'store_address' => '',
                    'store_phone' => '',
                    'store_email' => '',
                    'store_website' => '',
                    'store_social_media' => '',
                    'receipt_header' => '',
                    'receipt_footer' => 'Terima kasih atas kunjungan Anda',
                    'currency' => 'Rp',
                    'logo_url' => '',
                    'tax_enabled' => false,
                    'tax_rate' => 0,
                    'points_per_amount' => 10000,
                    'points_value' => 1
                ];
            } else {
                // Convert string booleans to actual booleans
                $settings['tax_enabled'] = (bool)$settings['tax_enabled'];
                $settings['tax_rate'] = (float)$settings['tax_rate'];
                $settings['points_per_amount'] = (int)$settings['points_per_amount'];
                $settings['points_value'] = (int)$settings['points_value'];
            }

            // Clean output buffer and send JSON
            ob_clean();
            echo json_encode($settings);
            ob_end_flush();
            break;

        case 'POST':
            // Only admin can modify settings
            if ($user['role'] !== 'admin') {
                throw new Exception('Access denied. Admin role required.');
            }

            $input = file_get_contents("php://input");
            if (!$input) {
                throw new Exception('No input data received');
            }

            $data = json_decode($input, true);
            if (json_last_error() !== JSON_ERROR_NONE) {
                throw new Exception('Invalid JSON data: ' . json_last_error_msg());
            }

            // Validate required fields
            if (empty($data['app_name']) || empty($data['store_name']) || empty($data['receipt_footer'])) {
                throw new Exception('App name, store name, and receipt footer are required');
            }

            // Check if settings table exists, create if not
            $createTable = "CREATE TABLE IF NOT EXISTS app_settings (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                app_name TEXT NOT NULL,
                store_name TEXT NOT NULL,
                store_address TEXT,
                store_phone TEXT,
                store_email TEXT,
                store_website TEXT,
                store_social_media TEXT,
                receipt_header TEXT,
                receipt_footer TEXT NOT NULL,
                currency TEXT DEFAULT 'Rp',
                logo_url TEXT,
                tax_enabled BOOLEAN DEFAULT 0,
                tax_rate DECIMAL(5,2) DEFAULT 0,
                points_per_amount INTEGER DEFAULT 10000,
                points_value INTEGER DEFAULT 1,
                created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
                updated_at DATETIME DEFAULT CURRENT_TIMESTAMP
            )";
            $db->exec($createTable);

            // Check if settings exist
            $checkQuery = "SELECT id FROM app_settings LIMIT 1";
            $checkStmt = $db->prepare($checkQuery);
            $checkStmt->execute();
            $existingSettings = $checkStmt->fetch();

            if ($existingSettings) {
                // Update existing settings
                $updateQuery = "UPDATE app_settings SET 
                    app_name = ?, store_name = ?, store_address = ?, store_phone = ?, 
                    store_email = ?, store_website = ?, store_social_media = ?, 
                    receipt_header = ?, receipt_footer = ?, currency = ?, logo_url = ?, 
                    tax_enabled = ?, tax_rate = ?, points_per_amount = ?, points_value = ?,
                    updated_at = CURRENT_TIMESTAMP 
                    WHERE id = ?";
                
                $stmt = $db->prepare($updateQuery);
                $result = $stmt->execute([
                    $data['app_name'],
                    $data['store_name'],
                    $data['store_address'] ?? '',
                    $data['store_phone'] ?? '',
                    $data['store_email'] ?? '',
                    $data['store_website'] ?? '',
                    $data['store_social_media'] ?? '',
                    $data['receipt_header'] ?? '',
                    $data['receipt_footer'],
                    $data['currency'] ?? 'Rp',
                    $data['logo_url'] ?? '',
                    $data['tax_enabled'] ? 1 : 0,
                    $data['tax_rate'] ?? 0,
                    $data['points_per_amount'] ?? 10000,
                    $data['points_value'] ?? 1,
                    $existingSettings['id']
                ]);
            } else {
                // Insert new settings
                $insertQuery = "INSERT INTO app_settings (
                    app_name, store_name, store_address, store_phone, store_email, 
                    store_website, store_social_media, receipt_header, receipt_footer, 
                    currency, logo_url, tax_enabled, tax_rate, points_per_amount, points_value
                ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
                
                $stmt = $db->prepare($insertQuery);
                $result = $stmt->execute([
                    $data['app_name'],
                    $data['store_name'],
                    $data['store_address'] ?? '',
                    $data['store_phone'] ?? '',
                    $data['store_email'] ?? '',
                    $data['store_website'] ?? '',
                    $data['store_social_media'] ?? '',
                    $data['receipt_header'] ?? '',
                    $data['receipt_footer'],
                    $data['currency'] ?? 'Rp',
                    $data['logo_url'] ?? '',
                    $data['tax_enabled'] ? 1 : 0,
                    $data['tax_rate'] ?? 0,
                    $data['points_per_amount'] ?? 10000,
                    $data['points_value'] ?? 1
                ]);
            }

            if ($result) {
                ob_clean();
                echo json_encode(['success' => true, 'message' => 'Settings saved successfully']);
                ob_end_flush();
            } else {
                throw new Exception('Failed to save settings');
            }
            break;

        default:
            throw new Exception('Method not allowed');
    }

} catch (Exception $e) {
    ob_clean();
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
    ob_end_flush();
}
?>
