<?php
session_start();
require_once '../auth.php';
require_once '../config/database.php';

// Simple PDF class (basic implementation)
class SimplePDF {
    private $content = '';
    private $width = 210; // A4 width in mm
    private $height = 297; // A4 height in mm
    
    public function addText($text, $size = 12, $bold = false) {
        $this->content .= ($bold ? '<b>' : '') . htmlspecialchars($text) . ($bold ? '</b>' : '') . '<br>';
    }
    
    public function addLine() {
        $this->content .= '<hr style="border: 1px dashed #000;">';
    }
    
    public function output($filename) {
        $html = '
        <!DOCTYPE html>
        <html>
        <head>
            <meta charset="UTF-8">
            <style>
                body { font-family: monospace; font-size: 12px; max-width: 300px; margin: 0 auto; }
                .center { text-align: center; }
                hr { border: 1px dashed #000; margin: 10px 0; }
            </style>
        </head>
        <body>' . $this->content . '</body>
        </html>';
        
        header('Content-Type: application/pdf');
        header('Content-Disposition: attachment; filename="' . $filename . '"');
        
        // For production, use a proper PDF library like TCPDF or FPDF
        // This is a simple HTML to PDF conversion
        echo $html;
    }
}

$auth = new Auth();
$auth->requireLogin();

try {
    if (!isset($_GET['id'])) {
        throw new Exception('Transaction ID required');
    }
    
    $database = new Database();
    $db = $database->getConnection();
    
    // Get transaction details
    $query = "SELECT t.*, u.name as cashier_name, m.name as member_name, m.points as member_points
              FROM transactions t 
              LEFT JOIN users u ON t.cashier_id = u.id
              LEFT JOIN members m ON t.member_id = m.id
              WHERE t.id = ?";
    $stmt = $db->prepare($query);
    $stmt->execute([$_GET['id']]);
    $transaction = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$transaction) {
        throw new Exception('Transaction not found');
    }
    
    // Get transaction items
    $query = "SELECT ti.*, p.name as product_name 
              FROM transaction_items ti 
              LEFT JOIN products p ON ti.product_id = p.id 
              WHERE ti.transaction_id = ?";
    $stmt = $db->prepare($query);
    $stmt->execute([$_GET['id']]);
    $items = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Get settings
    $query = "SELECT * FROM app_settings LIMIT 1";
    $stmt = $db->prepare($query);
    $stmt->execute();
    $settings = $stmt->fetch(PDO::FETCH_ASSOC);
    
    // Generate PDF
    $pdf = new SimplePDF();
    
    $pdf->addText($settings['store_name'] ?? 'Toko ABC', 14, true);
    $pdf->addText($settings['store_address'] ?? '');
    $pdf->addText('Tel: ' . ($settings['store_phone'] ?? ''));
    $pdf->addLine();
    
    $pdf->addText('Transaksi #' . $transaction['id']);
    $pdf->addText('Tanggal: ' . date('d/m/Y H:i', strtotime($transaction['transaction_date'])));
    $pdf->addText('Kasir: ' . ($transaction['cashier_name'] ?? 'Admin'));
    
    if ($transaction['member_name']) {
        $pdf->addText('Member: ' . $transaction['member_name']);
        $pdf->addText('Points: ' . $transaction['member_points']);
    }
    
    $pdf->addLine();
    
    foreach ($items as $item) {
        $pdf->addText($item['product_name']);
        $pdf->addText($item['quantity'] . ' x ' . number_format($item['price']) . ' = ' . number_format($item['subtotal']));
    }
    
    $pdf->addLine();
    $pdf->addText('Total: Rp ' . number_format($transaction['total']), 12, true);
    $pdf->addText('Pembayaran: ' . ucfirst($transaction['payment_method'] ?? 'cash'));
    $pdf->addLine();
    $pdf->addText($settings['receipt_footer'] ?? 'Terima kasih');
    
    $pdf->output('receipt_' . $transaction['id'] . '.pdf');
    
} catch (Exception $e) {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}
?>
