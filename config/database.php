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
                transaction_date DATETIME DEFAULT CURRENT_TIMESTAMP,
                total REAL NOT NULL
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
                purchase_price REAL DEFAULT 0,
                selling_price REAL DEFAULT 0,
                margin_type TEXT DEFAULT 'percentage',
                margin_value REAL DEFAULT 0,
                notes TEXT,
                created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
                FOREIGN KEY (product_id) REFERENCES products(id)
            )"
        ];

        foreach($queries as $query) {
            $this->conn->exec($query);
        }
    }
}
?>