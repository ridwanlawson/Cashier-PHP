
<?php
session_start();
require_once '../auth.php';
require_once '../config/database.php';

header('Content-Type: application/json');

$auth = new Auth();
$auth->requireLogin();

// Only admin can manage settings
$user = $auth->getUser();
if ($user['role'] !== 'admin') {
    http_response_code(403);
    echo json_encode(['success' => false, 'error' => 'Access denied']);
    exit;
}

try {
    $database = new Database();
    $db = $database->getConnection();
    
    $method = $_SERVER['REQUEST_METHOD'];
    
    switch($method) {
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
                    'tax_rate' => 0
                ];
            }
            
            echo json_encode($settings);
            break;
            
        case 'POST':
            $input = file_get_contents("php://input");
            $data = json_decode($input, true);
            
            if (!$data) {
                throw new Exception('Invalid JSON data');
            }
            
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
                         tax_rate = ?, updated_at = CURRENT_TIMESTAMP WHERE id = ?";
                $stmt = $db->prepare($query);
                $result = $stmt->execute([
                    $data['app_name'], $data['store_name'], $data['store_address'], $data['store_phone'],
                    $data['store_email'], $data['store_website'], $data['store_social_media'],
                    $data['receipt_footer'], $data['currency'], $data['logo_url'], $data['receipt_header'],
                    $data['tax_rate'], $exists['id']
                ]);
            } else {
                // Insert new settings
                $query = "INSERT INTO app_settings 
                         (app_name, store_name, store_address, store_phone, store_email, store_website,
                          store_social_media, receipt_footer, currency, logo_url, receipt_header, tax_rate)
                         VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
                $stmt = $db->prepare($query);
                $result = $stmt->execute([
                    $data['app_name'], $data['store_name'], $data['store_address'], $data['store_phone'],
                    $data['store_email'], $data['store_website'], $data['store_social_media'],
                    $data['receipt_footer'], $data['currency'], $data['logo_url'], $data['receipt_header'],
                    $data['tax_rate']
                ]);
            }
            
            if ($result) {
                echo json_encode(['success' => true, 'message' => 'Settings saved successfully']);
            } else {
                echo json_encode(['success' => false, 'error' => 'Failed to save settings']);
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
