<?php
include_once 'config/database.php';

$database = new Database();
$db = $database->getConnection();

if ($db) {
    echo "Installing sample data...\n";
    
    // Sample products
    $sampleProducts = [
        ['Nasi Gudeg', 'Makanan', 15000, 50, 'FOOD001'],
        ['Es Teh Manis', 'Minuman', 5000, 100, 'DRINK001'],
        ['Keripik Singkong', 'Snack', 8000, 75, 'SNACK001'],
        ['Kopi Hitam', 'Minuman', 7000, 80, 'DRINK002'],
        ['Ayam Goreng', 'Makanan', 25000, 30, 'FOOD002'],
        ['Sate Ayam', 'Makanan', 20000, 40, 'FOOD003'],
        ['Jus Jeruk', 'Minuman', 8000, 60, 'DRINK003'],
        ['Bakso', 'Makanan', 12000, 45, 'FOOD004'],
        ['Gado-Gado', 'Makanan', 13000, 35, 'FOOD005'],
        ['Es Campur', 'Minuman', 10000, 25, 'DRINK004']
    ];
    
    try {
        foreach ($sampleProducts as $product) {
            $query = "INSERT INTO products (name, category, price, stock, barcode) VALUES (?, ?, ?, ?, ?)";
            $stmt = $db->prepare($query);
            $stmt->execute($product);
        }
        
        echo "Sample data installed successfully!\n";
        echo "Added " . count($sampleProducts) . " sample products.\n";
        
    } catch (Exception $e) {
        echo "Error installing sample data: " . $e->getMessage() . "\n";
    }
} else {
    echo "Database connection failed!\n";
}
?>
