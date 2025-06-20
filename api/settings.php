<?php
require_once '../config/database.php';
require_once '../auth.php';

// Clean any output buffers and start fresh
if (ob_get_level()) {
    ob_end_clean();
}
ob_start();

session_start();
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
            // Create app_settings table if it doesn't exist
            $createTableQuery = "CREATE TABLE IF NOT EXISTS app_settings (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                app_name TEXT DEFAULT 'Kasir Digital',
                store_name TEXT DEFAULT 'Toko ABC',
                store_address TEXT DEFAULT '',
                store_phone TEXT DEFAULT '',
                store_email TEXT DEFAULT '',
                store_website TEXT DEFAULT '',
                store_social_media TEXT DEFAULT '',
                receipt_footer TEXT DEFAULT 'Terima kasih atas kunjungan Anda',
                receipt_header TEXT DEFAULT '',
                currency TEXT DEFAULT 'Rp',
                logo_url TEXT DEFAULT '',
                tax_enabled INTEGER DEFAULT 0,
                tax_rate REAL DEFAULT 0,
                points_per_amount INTEGER DEFAULT 10000,
                points_value INTEGER DEFAULT 1
            )";
            $db->exec($createTableQuery);

            // Get current settings
            $query = "SELECT * FROM app_settings LIMIT 1";
            $stmt = $db->prepare($query);
            $stmt->execute();
            $settings = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$settings) {
                // Insert default settings if none exist
                $insertQuery = "INSERT INTO app_settings 
                    (app_name, store_name, store_address, store_phone, store_email, store_website, 
                     store_social_media, receipt_footer, receipt_header, currency, logo_url, 
                     tax_enabled, tax_rate, points_per_amount, points_value) 
                    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
                
                $stmt = $db->prepare($insertQuery);
                $stmt->execute([
                    'Kasir Digital',
                    'Toko ABC',
                    'Jl. Contoh No. 123, Kota, Provinsi',
                    '021-12345678',
                    'info@tokoabc.com',
                    'www.tokoabc.com',
                    '@tokoabc',
                    'Terima kasih atas kunjungan Anda',
                    '',
                    'Rp',
                    '',
                    0,
                    0,
                    10000,
                    1
                ]);

                // Get the newly inserted settings
                $stmt = $db->prepare($query);
                $stmt->execute();
                $settings = $stmt->fetch(PDO::FETCH_ASSOC);
            }

            ob_clean();
            echo json_encode($settings);
            ob_end_flush();
            break;

        case 'POST':
            $input = file_get_contents("php://input");
            $data = json_decode($input, true);

            if (!$data) {
                throw new Exception('Invalid JSON data');
            }

            // Update settings
            $updateQuery = "UPDATE app_settings SET 
                app_name = ?,
                store_name = ?,
                store_address = ?,
                store_phone = ?,
                store_email = ?,
                store_website = ?,
                store_social_media = ?,
                receipt_footer = ?,
                receipt_header = ?,
                currency = ?,
                logo_url = ?,
                tax_enabled = ?,
                tax_rate = ?,
                points_per_amount = ?,
                points_value = ?
                WHERE id = 1";

            $stmt = $db->prepare($updateQuery);
            $result = $stmt->execute([
                $data['app_name'] ?? 'Kasir Digital',
                $data['store_name'] ?? 'Toko ABC',
                $data['store_address'] ?? '',
                $data['store_phone'] ?? '',
                $data['store_email'] ?? '',
                $data['store_website'] ?? '',
                $data['store_social_media'] ?? '',
                $data['receipt_footer'] ?? 'Terima kasih atas kunjungan Anda',
                $data['receipt_header'] ?? '',
                $data['currency'] ?? 'Rp',
                $data['logo_url'] ?? '',
                $data['tax_enabled'] ? 1 : 0,
                $data['tax_rate'] ?? 0,
                $data['points_per_amount'] ?? 10000,
                $data['points_value'] ?? 1
            ]);

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
