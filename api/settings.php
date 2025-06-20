<?php
session_start();
require_once '../auth.php';
require_once '../config/database.php';

header('Content-Type: application/json');

$auth = new Auth();
$auth->requireLogin();

$user = $auth->getUser();
$method = $_SERVER['REQUEST_METHOD'];

// For GET requests, allow all authenticated users to view settings
// For POST requests, only admin can modify settings
if ($method !== 'GET' && $user['role'] !== 'admin') {
    http_response_code(403);
    echo json_encode(['success' => false, 'error' => 'Access denied']);
    exit;
}

try {
    $database = new Database();
    $db = $database->getConnection();

    if (!$db) {
        throw new Exception('Database connection failed');
    }

    switch($_SERVER['REQUEST_METHOD']) {
        case 'GET':
            // Get current settings
            $query = "SELECT * FROM app_settings LIMIT 1";
            $stmt = $db->prepare($query);
            $stmt->execute();
            $settings = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$settings) {
                // Return default settings if none exist
                $settings = [
                    'app_name' => 'Kasir Digital',
                    'store_name' => 'Toko ABC',
                    'store_address' => 'Jl. Contoh No. 123, Kota, Provinsi',
                    'store_phone' => '021-12345678',
                    'store_email' => 'info@tokoabc.com',
                    'store_website' => 'www.tokoabc.com',
                    'store_social_media' => '@tokoabc',
                    'receipt_footer' => 'Terima kasih atas kunjungan Anda',
                    'currency' => 'Rp',
                    'logo_url' => '',
                    'receipt_header' => '',
                    'tax_enabled' => false,
                    'tax_rate' => 0,
                    'points_per_amount' => 10000,
                    'points_value' => 1
                ];
            } else {
                // Ensure points settings exist with defaults
                if (!isset($settings['points_per_amount'])) {
                    $settings['points_per_amount'] = 10000;
                }
                if (!isset($settings['points_value'])) {
                    $settings['points_value'] = 1;
                }
            }

            echo json_encode($settings);
            break;

        case 'POST':
            $input = file_get_contents("php://input");
            $data = json_decode($input, true);

            if (!$data) {
                throw new Exception('Invalid JSON data');
            }

            // Update or insert settings
            $fields = [
                'app_name', 'store_name', 'store_address', 'store_phone', 'store_email',
                'store_website', 'store_social_media', 'receipt_footer', 'receipt_header',
                'currency', 'logo_url', 'tax_enabled', 'tax_rate', 'points_per_amount', 'points_value'
            ];

            foreach ($fields as $field) {
                if (isset($data[$field])) {
                    $value = $data[$field];

                    // Convert boolean values to integers for storage
                    if ($field === 'tax_enabled') {
                        $value = $value ? 1 : 0;
                    }

                    // Check if setting already exists
                    $query = "SELECT id FROM settings WHERE setting_key = ?";
                    $stmt = $db->prepare($query);
                    $stmt->execute([$field]);
                    $existing = $stmt->fetch();

                    if ($existing) {
                        // Update existing setting
                        $query = "UPDATE settings SET setting_value = ? WHERE setting_key = ?";
                        $stmt = $db->prepare($query);
                        $stmt->execute([$value, $field]);
                    } else {
                        // Insert new setting
                        $query = "INSERT INTO settings (setting_key, setting_value) VALUES (?, ?)";
                        $stmt = $db->prepare($query);
                        $stmt->execute([$field, $value]);
                    }
                }
            }

            echo json_encode(['success' => true, 'message' => 'Settings saved successfully']);
            break;

        default:
            throw new Exception('Method not allowed');
    }

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}
?>