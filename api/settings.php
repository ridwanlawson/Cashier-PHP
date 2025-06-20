
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

            if (empty($input)) {
                http_response_code(400);
                echo json_encode(['success' => false, 'error' => 'No input data received']);
                exit;
            }

            $data = json_decode($input, true);

            if (json_last_error() !== JSON_ERROR_NONE) {
                http_response_code(400);
                echo json_encode(['success' => false, 'error' => 'Invalid JSON data: ' . json_last_error_msg()]);
                exit;
            }

            if (!$data) {
                http_response_code(400);
                echo json_encode(['success' => false, 'error' => 'Empty JSON data']);
                exit;
            }

            // Validate required fields
            if (empty($data['app_name']) || empty($data['store_name']) || empty($data['receipt_footer'])) {
                http_response_code(400);
                echo json_encode(['success' => false, 'error' => 'Required fields are missing: app_name, store_name, receipt_footer']);
                exit;
            }

            // Sanitize data
            $cleanData = [
                'app_name' => trim($data['app_name']),
                'store_name' => trim($data['store_name']),
                'store_address' => trim($data['store_address'] ?? ''),
                'store_phone' => trim($data['store_phone'] ?? ''),
                'store_email' => trim($data['store_email'] ?? ''),
                'store_website' => trim($data['store_website'] ?? ''),
                'store_social_media' => trim($data['store_social_media'] ?? ''),
                'receipt_footer' => trim($data['receipt_footer']),
                'receipt_header' => trim($data['receipt_header'] ?? ''),
                'currency' => trim($data['currency'] ?? 'Rp'),
                'logo_url' => trim($data['logo_url'] ?? ''),
                'tax_enabled' => isset($data['tax_enabled']) ? (int)(bool)$data['tax_enabled'] : 0,
                'tax_rate' => floatval($data['tax_rate'] ?? 0),
                'points_per_amount' => max(1, intval($data['points_per_amount'] ?? 10000)),
                'points_value' => max(1, intval($data['points_value'] ?? 1))
            ];

            // Check if settings exist
            $query = "SELECT id FROM app_settings LIMIT 1";
            $stmt = $db->prepare($query);
            $stmt->execute();
            $exists = $stmt->fetch(PDO::FETCH_ASSOC);

            if ($exists) {
                // Update existing settings
                $query = "UPDATE app_settings SET 
                         app_name = ?, store_name = ?, store_address = ?, store_phone = ?,
                         store_email = ?, store_website = ?, store_social_media = ?,
                         receipt_footer = ?, currency = ?, logo_url = ?, receipt_header = ?,
                         tax_enabled = ?, tax_rate = ?, points_per_amount = ?, points_value = ?,
                         updated_at = datetime('now') WHERE id = ?";
                $stmt = $db->prepare($query);
                $result = $stmt->execute([
                    $cleanData['app_name'], $cleanData['store_name'], $cleanData['store_address'], $cleanData['store_phone'],
                    $cleanData['store_email'], $cleanData['store_website'], $cleanData['store_social_media'],
                    $cleanData['receipt_footer'], $cleanData['currency'], $cleanData['logo_url'], $cleanData['receipt_header'],
                    $cleanData['tax_enabled'], $cleanData['tax_rate'], $cleanData['points_per_amount'], $cleanData['points_value'],
                    $exists['id']
                ]);
            } else {
                // Insert new settings
                $query = "INSERT INTO app_settings 
                         (app_name, store_name, store_address, store_phone, store_email, store_website,
                          store_social_media, receipt_footer, currency, logo_url, receipt_header, tax_enabled, tax_rate,
                          points_per_amount, points_value, created_at, updated_at)
                         VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, datetime('now'), datetime('now'))";
                $stmt = $db->prepare($query);
                $result = $stmt->execute([
                    $cleanData['app_name'], $cleanData['store_name'], $cleanData['store_address'], $cleanData['store_phone'],
                    $cleanData['store_email'], $cleanData['store_website'], $cleanData['store_social_media'],
                    $cleanData['receipt_footer'], $cleanData['currency'], $cleanData['logo_url'], $cleanData['receipt_header'],
                    $cleanData['tax_enabled'], $cleanData['tax_rate'], $cleanData['points_per_amount'], $cleanData['points_value']
                ]);
            }

            if ($result) {
                echo json_encode(['success' => true, 'message' => 'Settings saved successfully']);
            } else {
                $errorInfo = $stmt->errorInfo();
                http_response_code(500);
                echo json_encode(['success' => false, 'error' => 'Database error: ' . $errorInfo[2]]);
            }
            break;

        default:
            http_response_code(405);
            echo json_encode(['success' => false, 'error' => 'Method not allowed']);
    }

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}
?>
