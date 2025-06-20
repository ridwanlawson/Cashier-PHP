
<?php
require_once 'config/database.php';

echo "Initializing database...\n";

try {
    $database = new Database();
    $db = $database->getConnection();
    
    if ($db) {
        echo "✓ Database connection successful\n";
        echo "✓ Tables created successfully\n";
        
        // Check if admin user exists
        $stmt = $db->prepare("SELECT COUNT(*) FROM users WHERE role = 'admin'");
        $stmt->execute();
        $adminCount = $stmt->fetchColumn();
        
        if ($adminCount == 0) {
            echo "Creating default admin user...\n";
            $stmt = $db->prepare("INSERT INTO users (username, password, name, role) VALUES (?, ?, ?, ?)");
            $stmt->execute(['admin', password_hash('password', PASSWORD_DEFAULT), 'Administrator', 'admin']);
            echo "✓ Admin user created (username: admin, password: password)\n";
        }
        
        echo "Database initialization complete!\n";
    } else {
        echo "✗ Database connection failed\n";
    }
} catch (Exception $e) {
    echo "✗ Error: " . $e->getMessage() . "\n";
}
?>
