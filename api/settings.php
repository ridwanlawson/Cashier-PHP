<?php
// Ensure no output before this point
if (ob_get_level()) {
    ob_end_clean();
}

// Start session only if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once '../auth.php';
require_once '../config/database.php';

// Set headers after session is started
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    exit(0);
}

try {
    $auth = new Auth();
    
    $database = new Database();
    $db = $database->getConnection();

    if (!$db) {
        throw new Exception('Database connection failed');
    }

    $method = $_SERVER['REQUEST_METHOD'];

    switch($method) {
        case 'GET':
            // Get app settings
            $query = "SELECT * FROM app_settings ORDER BY id DESC LIMIT 1";
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
                // Convert string to boolean for tax_enabled
                $settings['tax_enabled'] = (bool)$settings['tax_enabled'];
                $settings['tax_rate'] = (float)$settings['tax_rate'];
                $settings['points_per_amount'] = (int)$settings['points_per_amount'];
                $settings['points_value'] = (int)$settings['points_value'];
            }

            echo json_encode($settings);
            break;

        case 'POST':
            // Check authentication for saving settings
            if (!$auth->isLoggedIn()) {
                http_response_code(401);
                echo json_encode(['success' => false, 'error' => 'Authentication required']);
                exit;
            }

            $user = $auth->getUser();
            if ($user['role'] !== 'admin') {
                http_response_code(403);
                echo json_encode(['success' => false, 'error' => 'Access denied. Admin role required.']);
                exit;
            }

            $input = file_get_contents("php://input");
            if (!$input) {
                throw new Exception('No input data received');
            }

            $data = json_decode($input, true);
            if (json_last_error() !== JSON_ERROR_NONE) {
                throw new Exception('Invalid JSON data: ' . json_last_error_msg());
            }

            // Check if settings exist
            $query = "SELECT COUNT(*) FROM app_settings";
            $stmt = $db->prepare($query);
            $stmt->execute();
            $count = $stmt->fetchColumn();

            if ($count > 0) {
                // Update existing settings
                $query = "UPDATE app_settings SET 
                    app_name = ?, store_name = ?, store_address = ?, store_phone = ?, 
                    store_email = ?, store_website = ?, store_social_media = ?,
                    receipt_header = ?, receipt_footer = ?, currency = ?, logo_url = ?,
                    tax_enabled = ?, tax_rate = ?, points_per_amount = ?, points_value = ?,
                    updated_at = datetime('now')
                    WHERE id = (SELECT id FROM app_settings ORDER BY id DESC LIMIT 1)";
            } else {
                // Insert new settings
                $query = "INSERT INTO app_settings (
                    app_name, store_name, store_address, store_phone, store_email,
                    store_website, store_social_media, receipt_header, receipt_footer,
                    currency, logo_url, tax_enabled, tax_rate, points_per_amount, points_value,
                    created_at, updated_at
                ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, datetime('now'), datetime('now'))";
            }

            $stmt = $db->prepare($query);
            $result = $stmt->execute([
                $data['app_name'] ?? 'Kasir Digital',
                $data['store_name'] ?? 'Toko ABC',
                $data['store_address'] ?? '',
                $data['store_phone'] ?? '',
                $data['store_email'] ?? '',
                $data['store_website'] ?? '',
                $data['store_social_media'] ?? '',
                $data['receipt_header'] ?? '',
                $data['receipt_footer'] ?? 'Terima kasih atas kunjungan Anda',
                $data['currency'] ?? 'Rp',
                $data['logo_url'] ?? '',
                isset($data['tax_enabled']) ? (int)$data['tax_enabled'] : 0,
                $data['tax_rate'] ?? 0,
                $data['points_per_amount'] ?? 10000,
                $data['points_value'] ?? 1
            ]);

            if($result) {
                echo json_encode(['success' => true, 'message' => 'Settings saved successfully']);
            } else {
                echo json_encode(['success' => false, 'error' => 'Failed to save settings']);
            }
            break;

        default:
            throw new Exception('Method not allowed');
    }

} catch (Exception $e) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
}
?>
