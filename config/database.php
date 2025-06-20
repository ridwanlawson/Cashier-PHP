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
            $this->conn = new PDO("sqlite:" . $db_path);
            $this->conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

            // Create tables if they don't exist
            $this->createTables();

        } catch(PDOException $exception) {
            echo "Connection error: " . $exception->getMessage();
        }

        return $this->conn;
    }

    private function createTables() {
        $queries = [
            "CREATE TABLE IF NOT EXISTS products (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                name TEXT NOT NULL,
                category TEXT NOT NULL,
                price REAL NOT NULL,
                stock INTEGER NOT NULL,
                barcode TEXT UNIQUE NOT NULL
            )",
            "CREATE TABLE IF NOT EXISTS transactions (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                subtotal REAL NOT NULL DEFAULT 0,
                tax_amount REAL NOT NULL DEFAULT 0,
                total REAL NOT NULL,
                transaction_date DATETIME DEFAULT CURRENT_TIMESTAMP
            )",
            "CREATE TABLE IF NOT EXISTS transaction_items (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                transaction_id INTEGER NOT NULL,
                product_id INTEGER NOT NULL,
                quantity INTEGER NOT NULL,
                price REAL NOT NULL,
                subtotal REAL NOT NULL,
                FOREIGN KEY (transaction_id) REFERENCES transactions(id),
                FOREIGN KEY (product_id) REFERENCES products(id)
            )",
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
            "CREATE TABLE IF NOT EXISTS users (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                username TEXT UNIQUE NOT NULL,
                password TEXT NOT NULL,
                name TEXT NOT NULL,
                role TEXT NOT NULL DEFAULT 'kasir',
                created_at DATETIME DEFAULT CURRENT_TIMESTAMP
            )",
            "INSERT OR IGNORE INTO users (username, password, name, role) VALUES 
             ('admin', 'password', 'Administrator', 'admin'),
            ('kasir', 'password', 'Kasir', 'kasir')",
            "CREATE TABLE IF NOT EXISTS app_settings (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                app_name TEXT DEFAULT 'Kasir Digital',
                store_name TEXT DEFAULT 'Toko ABC',
                store_address TEXT DEFAULT 'Jl. Contoh No. 123',
                store_phone TEXT DEFAULT '021-12345678',
                store_email TEXT DEFAULT 'info@example.com',
                store_website TEXT DEFAULT 'www.example.com',
                store_social_media TEXT DEFAULT '@example',
                receipt_footer TEXT DEFAULT 'Terima kasih atas kunjungan Anda',
                receipt_header TEXT DEFAULT '',
                currency TEXT DEFAULT 'Rp',
                logo_url TEXT DEFAULT '',
                tax_rate REAL DEFAULT 0,
                tax_enabled INTEGER DEFAULT 0,
                created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
                updated_at DATETIME DEFAULT CURRENT_TIMESTAMP
            )",
            "CREATE TABLE IF NOT EXISTS members (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                name TEXT NOT NULL,
                phone TEXT UNIQUE NOT NULL,
                points INTEGER NOT NULL DEFAULT 0,
                created_at DATETIME DEFAULT CURRENT_TIMESTAMP
            )",
            "CREATE TABLE IF NOT EXISTS held_transactions (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                items TEXT NOT NULL,
                member TEXT,
                payment_method TEXT DEFAULT 'cash',
                held_at DATETIME DEFAULT CURRENT_TIMESTAMP
            )",
            "ALTER TABLE transactions ADD COLUMN cashier_id INTEGER DEFAULT NULL",
            "ALTER TABLE transactions ADD COLUMN member_id INTEGER DEFAULT NULL",
            "ALTER TABLE transactions ADD COLUMN payment_method TEXT DEFAULT 'cash'",
            "ALTER TABLE transaction_items ADD COLUMN discount REAL DEFAULT 0"
        ];

        foreach($queries as $query) {
            $this->conn->exec($query);
        }
    }
}
?>