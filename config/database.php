
<?php
class Database {
    private $host = "localhost";
    private $db_name = "kasir_digital";
    private $conn;

    public function getConnection() {
        $this->conn = null;

        try {
            // Use SQLite for Replit compatibility
            $db_path = __DIR__ . '/../api/kasir_digital.db';

            // Ensure directory exists
            $db_dir = dirname($db_path);
            if (!is_dir($db_dir)) {
                mkdir($db_dir, 0755, true);
            }

            $this->conn = new PDO("sqlite:" . $db_path);
            $this->conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

            // Create tables if they don't exist
            $this->createTables();

        } catch(PDOException $exception) {
            error_log("Database connection error: " . $exception->getMessage());
            return null;
        }

        return $this->conn;
    }

    private function createTables() {
        if (!$this->conn) {
            return false;
        }

        $queries = [
            // Users table
            "CREATE TABLE IF NOT EXISTS users (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                username TEXT UNIQUE NOT NULL,
                password TEXT NOT NULL,
                name TEXT NOT NULL,
                role TEXT NOT NULL DEFAULT 'kasir',
                created_at DATETIME DEFAULT CURRENT_TIMESTAMP
            )",
            
            // Products table
            "CREATE TABLE IF NOT EXISTS products (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                name TEXT NOT NULL,
                category TEXT NOT NULL,
                price REAL NOT NULL,
                stock INTEGER NOT NULL DEFAULT 0,
                barcode TEXT UNIQUE NOT NULL,
                created_at DATETIME DEFAULT CURRENT_TIMESTAMP
            )",
            
            // Transactions table
            "CREATE TABLE IF NOT EXISTS transactions (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                subtotal REAL NOT NULL DEFAULT 0,
                tax_amount REAL NOT NULL DEFAULT 0,
                total REAL NOT NULL,
                cashier_id INTEGER DEFAULT NULL,
                member_id INTEGER DEFAULT NULL,
                payment_method TEXT DEFAULT 'cash',
                transaction_date DATETIME DEFAULT CURRENT_TIMESTAMP,
                FOREIGN KEY (cashier_id) REFERENCES users(id),
                FOREIGN KEY (member_id) REFERENCES members(id)
            )",
            
            // Transaction items table
            "CREATE TABLE IF NOT EXISTS transaction_items (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                transaction_id INTEGER NOT NULL,
                product_id INTEGER NOT NULL,
                quantity INTEGER NOT NULL,
                price REAL NOT NULL,
                discount REAL DEFAULT 0,
                subtotal REAL NOT NULL,
                FOREIGN KEY (transaction_id) REFERENCES transactions(id),
                FOREIGN KEY (product_id) REFERENCES products(id)
            )",
            
            // Inventory log table
            "CREATE TABLE IF NOT EXISTS inventory_log (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                product_id INTEGER NOT NULL,
                quantity INTEGER NOT NULL,
                stock_before INTEGER NOT NULL,
                stock_after INTEGER NOT NULL,
                notes TEXT,
                created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
                FOREIGN KEY (product_id) REFERENCES products(id)
            )",
            
            // Members table
            "CREATE TABLE IF NOT EXISTS members (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                name TEXT NOT NULL,
                phone TEXT UNIQUE NOT NULL,
                points INTEGER NOT NULL DEFAULT 0,
                created_at DATETIME DEFAULT CURRENT_TIMESTAMP
            )",
            
            // Held transactions table
            "CREATE TABLE IF NOT EXISTS held_transactions (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                cart_data TEXT NOT NULL,
                member TEXT,
                payment_method TEXT DEFAULT 'cash',
                note TEXT,
                held_at DATETIME DEFAULT CURRENT_TIMESTAMP,
                created_at DATETIME DEFAULT CURRENT_TIMESTAMP
            )",
            
            // App settings table
            "CREATE TABLE IF NOT EXISTS app_settings (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                app_name TEXT NOT NULL DEFAULT 'Kasir Digital',
                store_name TEXT NOT NULL DEFAULT 'Toko ABC',
                store_address TEXT DEFAULT '',
                store_phone TEXT DEFAULT '',
                store_email TEXT DEFAULT '',
                store_website TEXT DEFAULT '',
                store_social_media TEXT DEFAULT '',
                receipt_header TEXT DEFAULT '',
                receipt_footer TEXT NOT NULL DEFAULT 'Terima kasih atas kunjungan Anda',
                currency TEXT DEFAULT 'Rp',
                logo_url TEXT DEFAULT '',
                tax_enabled INTEGER DEFAULT 0,
                tax_rate REAL DEFAULT 0,
                points_per_amount INTEGER DEFAULT 10000,
                points_value INTEGER DEFAULT 1,
                created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
                updated_at DATETIME DEFAULT CURRENT_TIMESTAMP
            )",
            
            // Insert default admin user
            "INSERT OR IGNORE INTO users (username, password, name, role) VALUES 
             ('admin', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Administrator', 'admin')",
             
            // Insert default kasir user
            "INSERT OR IGNORE INTO users (username, password, name, role) VALUES 
             ('kasir', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Kasir', 'kasir')",
             
            // Insert default app settings
            "INSERT OR IGNORE INTO app_settings (
                app_name, store_name, store_address, store_phone, store_email, 
                store_website, store_social_media, receipt_header, receipt_footer, 
                currency, logo_url, tax_enabled, tax_rate, points_per_amount, points_value
            ) VALUES (
                'Kasir Digital', 'Toko ABC', '', '', '', 
                '', '', '', 'Terima kasih atas kunjungan Anda', 
                'Rp', '', 0, 0, 10000, 1
            )"
        ];

        foreach($queries as $query) {
            try {
                $this->conn->exec($query);
            } catch(PDOException $e) {
                // Log error but continue with other queries
                error_log("Table creation error: " . $e->getMessage() . " Query: " . $query);
            }
        }

        return true;
    }
}
?>
