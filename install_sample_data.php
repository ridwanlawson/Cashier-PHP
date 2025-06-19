
<?php
echo "=== KASIR DIGITAL - SAMPLE DATA INSTALLER ===\n";
echo "Installing sample data for Kasir Digital POS System...\n\n";

include_once 'config/database.php';

try {
    $database = new Database();
    $db = $database->getConnection();

    if (!$db) {
        throw new Exception("Database connection failed!");
    }

    echo "✓ Database connected successfully\n";

    // Create tables if they don't exist
    echo "📋 Creating database tables...\n";

    // Users table
    $createUsersTable = "CREATE TABLE IF NOT EXISTS users (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        username VARCHAR(50) UNIQUE NOT NULL,
        password VARCHAR(255) NOT NULL,
        name VARCHAR(100) NOT NULL,
        role VARCHAR(20) NOT NULL DEFAULT 'kasir',
        created_at DATETIME DEFAULT CURRENT_TIMESTAMP
    )";
    $db->exec($createUsersTable);

    // Products table
    $createProductsTable = "CREATE TABLE IF NOT EXISTS products (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        name VARCHAR(255) NOT NULL,
        category VARCHAR(100) NOT NULL,
        price DECIMAL(10,2) NOT NULL,
        stock INTEGER NOT NULL DEFAULT 0,
        barcode VARCHAR(100) UNIQUE NOT NULL,
        created_at DATETIME DEFAULT CURRENT_TIMESTAMP
    )";
    $db->exec($createProductsTable);

    // Transactions table
    $createTransactionsTable = "CREATE TABLE IF NOT EXISTS transactions (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        total DECIMAL(10,2) NOT NULL,
        transaction_date DATETIME DEFAULT CURRENT_TIMESTAMP
    )";
    $db->exec($createTransactionsTable);

    // Transaction items table
    $createTransactionItemsTable = "CREATE TABLE IF NOT EXISTS transaction_items (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        transaction_id INTEGER NOT NULL,
        product_id INTEGER NOT NULL,
        quantity INTEGER NOT NULL,
        price DECIMAL(10,2) NOT NULL,
        subtotal DECIMAL(10,2) NOT NULL,
        FOREIGN KEY (transaction_id) REFERENCES transactions(id),
        FOREIGN KEY (product_id) REFERENCES products(id)
    )";
    $db->exec($createTransactionItemsTable);

    // Inventory log table
    $createInventoryLogTable = "CREATE TABLE IF NOT EXISTS inventory_log (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        product_id INTEGER NOT NULL,
        quantity INTEGER NOT NULL,
        stock_before INTEGER NOT NULL,
        stock_after INTEGER NOT NULL,
        notes TEXT,
        created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (product_id) REFERENCES products(id)
    )";
    $db->exec($createInventoryLogTable);

    echo "✓ Database tables created\n\n";

    // Begin transaction
    $db->beginTransaction();

    // Sample users
    echo "👥 Installing sample users...\n";
    $sampleUsers = [
        ['admin', 'password', 'Administrator', 'admin'],
        ['kasir', 'password', 'Kasir Utama', 'kasir'],
        ['manager', 'password', 'Manager Toko', 'admin'],
        ['kasir2', 'password', 'Kasir Shift 2', 'kasir']
    ];

    $userStmt = $db->prepare("INSERT OR IGNORE INTO users (username, password, name, role) VALUES (?, ?, ?, ?)");
    $userCount = 0;
    foreach ($sampleUsers as $user) {
        if ($userStmt->execute($user)) {
            $userCount++;
            echo "  → {$user[2]} ({$user[0]}) - Role: {$user[3]}\n";
        }
    }
    echo "✓ Installed {$userCount} users\n\n";

    // Sample products with more realistic Indonesian items
    echo "📦 Installing sample products...\n";
    $sampleProducts = [
        // Makanan
        ['Nasi Gudeg Jogja', 'Makanan', 18000, 25, 'FOOD001'],
        ['Nasi Padang Komplit', 'Makanan', 25000, 20, 'FOOD002'],
        ['Ayam Goreng Kremes', 'Makanan', 22000, 30, 'FOOD003'],
        ['Sate Ayam 10 Tusuk', 'Makanan', 20000, 15, 'FOOD004'],
        ['Bakso Malang', 'Makanan', 15000, 35, 'FOOD005'],
        ['Gado-Gado Jakarta', 'Makanan', 13000, 25, 'FOOD006'],
        ['Soto Betawi', 'Makanan', 16000, 20, 'FOOD007'],
        ['Rendang Daging', 'Makanan', 28000, 12, 'FOOD008'],

        // Minuman
        ['Es Teh Manis', 'Minuman', 5000, 100, 'DRINK001'],
        ['Es Jeruk Peras', 'Minuman', 8000, 80, 'DRINK002'],
        ['Kopi Hitam', 'Minuman', 7000, 90, 'DRINK003'],
        ['Cappuccino', 'Minuman', 12000, 50, 'DRINK004'],
        ['Es Campur', 'Minuman', 10000, 40, 'DRINK005'],
        ['Jus Alpukat', 'Minuman', 12000, 30, 'DRINK006'],
        ['Air Mineral 600ml', 'Minuman', 3000, 200, 'DRINK007'],
        ['Es Kelapa Muda', 'Minuman', 9000, 45, 'DRINK008'],

        // Snack
        ['Keripik Singkong', 'Snack', 8000, 75, 'SNACK001'],
        ['Kacang Tanah Goreng', 'Snack', 6000, 60, 'SNACK002'],
        ['Pisang Goreng', 'Snack', 5000, 40, 'SNACK003'],
        ['Tahu Goreng', 'Snack', 4000, 50, 'SNACK004'],
        ['Kerupuk Udang', 'Snack', 7000, 65, 'SNACK005'],
        ['Martabak Mini', 'Snack', 10000, 25, 'SNACK006'],

        // Sembako
        ['Beras Premium 5kg', 'Sembako', 65000, 20, 'SEMBAKO001'],
        ['Minyak Goreng 2L', 'Sembako', 28000, 30, 'SEMBAKO002'],
        ['Gula Pasir 1kg', 'Sembako', 15000, 40, 'SEMBAKO003'],
        ['Tepung Terigu 1kg', 'Sembako', 12000, 25, 'SEMBAKO004'],
        ['Kecap Manis 600ml', 'Sembako', 8000, 35, 'SEMBAKO005'],
        ['Sambal ABC 335ml', 'Sembako', 9000, 45, 'SEMBAKO006'],

        // Produk Kering
        ['Indomie Goreng', 'Produk Kering', 3500, 150, 'KERING001'],
        ['Biskuit Marie', 'Produk Kering', 8000, 40, 'KERING002'],
        ['Wafer Chocolate', 'Produk Kering', 6000, 55, 'KERING003'],
        ['Kopi Sachet', 'Produk Kering', 2000, 200, 'KERING004']
    ];

    $productStmt = $db->prepare("INSERT OR IGNORE INTO products (name, category, price, stock, barcode) VALUES (?, ?, ?, ?, ?)");
    $productCount = 0;
    foreach ($sampleProducts as $product) {
        if ($productStmt->execute($product)) {
            $productCount++;
            echo "  → {$product[0]} - {$product[1]} - Rp " . number_format($product[2], 0, ',', '.') . " (Stok: {$product[3]})\n";
        }
    }
    echo "✓ Installed {$productCount} products\n\n";

    // Sample transactions
    echo "💰 Installing sample transactions...\n";
    $currentDate = date('Y-m-d H:i:s');
    $transactions = [
        [40909, 4091, 45000, $currentDate], // subtotal, tax (10%), total, date
        [70909, 7091, 78000, date('Y-m-d H:i:s', strtotime('-1 hour'))],
        [29091, 2909, 32000, date('Y-m-d H:i:s', strtotime('-2 hours'))],
        [113636, 11364, 125000, date('Y-m-d H:i:s', strtotime('-1 day'))],
        [60909, 6091, 67000, date('Y-m-d H:i:s', strtotime('-1 day -2 hours'))]
    ];

    $transactionStmt = $db->prepare("INSERT INTO transactions (subtotal, tax_amount, total, transaction_date) VALUES (?, ?, ?, ?)");
    $transactionCount = 0;
    foreach ($transactions as $transaction) {
        if ($transactionStmt->execute($transaction)) {
            $transactionCount++;
            echo "  → Transaksi Rp " . number_format($transaction[2], 0, ',', '.') . " (Subtotal: Rp " . number_format($transaction[0], 0, ',', '.') . " + Pajak: Rp " . number_format($transaction[1], 0, ',', '.') . ") - {$transaction[3]}\n";
        }
    }
    echo "✓ Installed {$transactionCount} transactions\n\n";

    // Sample inventory logs
    echo "📊 Installing sample inventory logs...\n";
    $inventoryLogs = [
        [1, 10, 25, 35, 'Restock barang dari supplier A'],
        [2, 15, 20, 35, 'Tambahan stok untuk promo weekend'],
        [3, 20, 30, 50, 'Stok bulanan ayam goreng'],
        [4, 5, 15, 20, 'Restock sate ayam'],
        [5, 25, 35, 60, 'Penambahan stok bakso']
    ];

    $inventoryStmt = $db->prepare("INSERT INTO inventory_log (product_id, quantity, stock_before, stock_after, notes) VALUES (?, ?, ?, ?, ?)");
    $inventoryCount = 0;
    foreach ($inventoryLogs as $log) {
        if ($inventoryStmt->execute($log)) {
            $inventoryCount++;
            echo "  → Produk ID {$log[0]} - Tambah {$log[1]} unit - {$log[4]}\n";
        }
    }
    echo "✓ Installed {$inventoryCount} inventory logs\n\n";

    // Sample app settings
    echo "⚙️ Installing sample app settings...\n";
    $sampleSettings = [
        'Kasir Digital Pro',
        'Toko Serba Ada Sejahtera',
        'Jl. Merdeka No. 123, Jakarta Pusat, DKI Jakarta 10110',
        '(021) 123-4567',
        'info@tokoserbaada.com',
        'www.tokoserbaada.com',
        '@tokoserbaada',
        'Selamat datang di Toko Serba Ada Sejahtera\nMelayani dengan sepenuh hati',
        'Terima kasih atas kepercayaan Anda berbelanja di toko kami\nBarang yang sudah dibeli tidak dapat dikembalikan\nSimpan struk ini sebagai bukti pembelian',
        'Rp',
        '',
        false,
        10.0
    ];

    $settingsStmt = $db->prepare("INSERT OR IGNORE INTO app_settings (app_name, store_name, store_address, store_phone, store_email, store_website, store_social_media, receipt_header, receipt_footer, currency, logo_url, tax_enabled, tax_rate) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
    if ($settingsStmt->execute($sampleSettings)) {
        echo "  → Pengaturan aplikasi berhasil diinstall\n";
        echo "    • Nama Aplikasi: {$sampleSettings[0]}\n";
        echo "    • Nama Toko: {$sampleSettings[1]}\n";
        echo "    • Pajak: " . ($sampleSettings[11] ? "Aktif ({$sampleSettings[12]}%)" : "Tidak Aktif") . "\n";
    }
    echo "✓ Sample settings installed\n\n";

    // Commit transaction
    $db->commit();

    echo "🎉 INSTALLATION COMPLETED SUCCESSFULLY!\n\n";
    echo "=== LOGIN CREDENTIALS ===\n";
    echo "👑 ADMIN:\n";
    echo "   Username: admin\n";
    echo "   Password: password\n\n";
    echo "👤 KASIR:\n";
    echo "   Username: kasir\n";
    echo "   Password: password\n\n";
    echo "🌐 Access your application at: http://localhost:8000\n\n";
    echo "📋 SUMMARY:\n";
    echo "   • Users installed: {$userCount}\n";
    echo "   • Products installed: {$productCount}\n";
    echo "   • Sample transactions: {$transactionCount}\n";
    echo "   • Inventory logs: {$inventoryCount}\n";
    echo "   • App settings: configured\n\n";
    echo "💡 TIPS:\n";
    echo "   • Test barcode scanning with: FOOD001, DRINK001, SNACK001\n";
    echo "   • Use 'Nasi Gudeg' for quick product search test\n";
    echo "   • Check dashboard for real-time statistics\n";
    echo "   • Try dark/light mode toggle in top-right corner\n\n";
    echo "🚀 Your Kasir Digital POS System is ready to use!\n";

} catch (Exception $e) {
    if (isset($db)) {
        $db->rollBack();
    }
    echo "❌ ERROR: " . $e->getMessage() . "\n";
    echo "Installation failed. Please check your configuration and try again.\n";
}
?>
