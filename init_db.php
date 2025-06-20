
<?php
require_once 'config/database.php';

echo "=== KASIR DIGITAL - DATABASE INITIALIZATION ===\n";
echo "Initializing database for Kasir Digital POS System...\n\n";

try {
    $database = new Database();
    $db = $database->getConnection();
    
    if ($db) {
        echo "✓ Database connection successful\n";
        echo "✓ All tables created successfully\n";
        
        // Check if admin user exists
        $stmt = $db->prepare("SELECT COUNT(*) FROM users WHERE role = 'admin'");
        $stmt->execute();
        $adminCount = $stmt->fetchColumn();
        
        if ($adminCount == 0) {
            echo "Creating default admin user...\n";
            $hashedPassword = password_hash('password', PASSWORD_DEFAULT);
            $stmt = $db->prepare("INSERT INTO users (username, password, name, role) VALUES (?, ?, ?, ?)");
            $stmt->execute(['admin', $hashedPassword, 'Administrator', 'admin']);
            echo "✓ Admin user created (username: admin, password: password)\n";
        } else {
            echo "✓ Admin user already exists\n";
        }
        
        // Check if kasir user exists
        $stmt = $db->prepare("SELECT COUNT(*) FROM users WHERE username = 'kasir'");
        $stmt->execute();
        $kasirCount = $stmt->fetchColumn();
        
        if ($kasirCount == 0) {
            echo "Creating default kasir user...\n";
            $hashedPassword = password_hash('password', PASSWORD_DEFAULT);
            $stmt = $db->prepare("INSERT INTO users (username, password, name, role) VALUES (?, ?, ?, ?)");
            $stmt->execute(['kasir', $hashedPassword, 'Kasir', 'kasir']);
            echo "✓ Kasir user created (username: kasir, password: password)\n";
        } else {
            echo "✓ Kasir user already exists\n";
        }
        
        // Check if app settings exist
        $stmt = $db->prepare("SELECT COUNT(*) FROM app_settings");
        $stmt->execute();
        $settingsCount = $stmt->fetchColumn();
        
        if ($settingsCount == 0) {
            echo "Creating default app settings...\n";
            $stmt = $db->prepare("INSERT INTO app_settings (
                app_name, store_name, store_address, store_phone, store_email, 
                store_website, store_social_media, receipt_header, receipt_footer, 
                currency, logo_url, tax_enabled, tax_rate, points_per_amount, points_value
            ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
            $stmt->execute([
                'Kasir Digital', 'Toko ABC', '', '', '', 
                '', '', '', 'Terima kasih atas kunjungan Anda', 
                'Rp', '', 0, 0, 10000, 1
            ]);
            echo "✓ Default app settings created\n";
        } else {
            echo "✓ App settings already exist\n";
        }
        
        echo "\n🎉 DATABASE INITIALIZATION COMPLETED!\n\n";
        echo "=== DEFAULT LOGIN CREDENTIALS ===\n";
        echo "👑 ADMIN:\n";
        echo "   Username: admin\n";
        echo "   Password: password\n\n";
        echo "👤 KASIR:\n";
        echo "   Username: kasir\n";
        echo "   Password: password\n\n";
        echo "🌐 Access your application at: http://0.0.0.0:8000\n\n";
        echo "💡 Next steps:\n";
        echo "   1. Run 'php install_sample_data.php' for sample data (optional)\n";
        echo "   2. Start the server with 'php -S 0.0.0.0:8000'\n";
        echo "   3. Login and change default passwords\n\n";
        
    } else {
        echo "✗ Database connection failed\n";
        exit(1);
    }
} catch (Exception $e) {
    echo "✗ Error: " . $e->getMessage() . "\n";
    exit(1);
}
?>
