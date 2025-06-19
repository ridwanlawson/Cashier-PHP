<?php
session_start();
require_once 'auth.php';

$auth = new Auth();
$auth->requireLogin();
$user = $auth->getUser();
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Kasir Digital</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link href="https://cdn.datatables.net/1.13.4/css/dataTables.bootstrap5.min.css" rel="stylesheet">
    <link href="https://cdn.datatables.net/buttons/2.3.6/css/buttons.bootstrap5.min.css" rel="stylesheet">
    <style>
        :root {
            --bg-primary: #0f1419;
            --bg-secondary: #1a202c;
            --bg-tertiary: #2d3748;
            --accent-primary: #00d4ff;
            --accent-secondary: #ff6b6b;
            --accent-success: #4ade80;
            --accent-warning: #fbbf24;
            --text-primary: #e2e8f0;
            --text-secondary: #a0aec0;
            --border-color: #374151;
            --shadow: 0 10px 25px rgba(0, 0, 0, 0.3);
        }

        * {
            transition: all 0.3s ease;
        }

        body {
            background: linear-gradient(135deg, var(--bg-primary) 0%, var(--bg-secondary) 100%);
            color: var(--text-primary);
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            min-height: 100vh;
        }

        .sidebar {
            background: linear-gradient(180deg, var(--bg-secondary) 0%, var(--bg-tertiary) 100%);
            border-right: 2px solid var(--border-color);
            box-shadow: var(--shadow);
            min-height: 100vh;
            position: relative;
            overflow: hidden;
        }

        .sidebar::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 4px;
            background: linear-gradient(90deg, var(--accent-primary), var(--accent-secondary));
        }

        .sidebar h4 {
            color: var(--accent-primary);
            text-shadow: 0 0 20px rgba(0, 212, 255, 0.3);
            border-bottom: 1px solid var(--border-color);
            padding-bottom: 1rem;
            margin-bottom: 2rem;
        }

        .main-content {
            background: var(--bg-primary);
            min-height: 100vh;
            position: relative;
        }

        .main-content::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: radial-gradient(circle at 20% 50%, rgba(0, 212, 255, 0.1) 0%, transparent 50%),
                        radial-gradient(circle at 80% 20%, rgba(255, 107, 107, 0.1) 0%, transparent 50%);
            pointer-events: none;
        }

        .card {
            background: var(--bg-secondary);
            border: 1px solid var(--border-color);
            box-shadow: var(--shadow);
            backdrop-filter: blur(10px);
            color: var(--text-primary);
        }

        .card:hover {
            transform: translateY(-2px);
            box-shadow: 0 15px 35px rgba(0, 0, 0, 0.4);
        }

        .card-header {
            background: var(--bg-tertiary);
            border-bottom: 1px solid var(--border-color);
            color: var(--accent-primary);
        }

        .nav-link {
            color: var(--text-secondary) !important;
            border-radius: 10px;
            margin: 5px 0;
            padding: 12px 20px !important;
            position: relative;
            overflow: hidden;
        }

        .nav-link::before {
            content: '';
            position: absolute;
            top: 0;
            left: -100%;
            width: 100%;
            height: 100%;
            background: linear-gradient(90deg, transparent, rgba(0, 212, 255, 0.2), transparent);
            transition: left 0.5s;
        }

        .nav-link:hover {
            background: rgba(0, 212, 255, 0.1);
            transform: translateX(10px);
            color: var(--accent-primary) !important;
        }

        .nav-link:hover::before {
            left: 100%;
        }

        .nav-link.active {
            background: linear-gradient(135deg, var(--accent-primary), var(--accent-secondary));
            color: white !important;
            transform: translateX(10px);
            box-shadow: 0 5px 15px rgba(0, 212, 255, 0.4);
        }

        .btn-primary {
            background: linear-gradient(135deg, var(--accent-primary), #0891b2);
            border: none;
            box-shadow: 0 5px 15px rgba(0, 212, 255, 0.3);
        }

        .btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 25px rgba(0, 212, 255, 0.5);
        }

        .btn-success {
            background: linear-gradient(135deg, var(--accent-success), #22c55e);
            border: none;
        }

        .btn-warning {
            background: linear-gradient(135deg, var(--accent-warning), #f59e0b);
            border: none;
        }

        .btn-danger {
            background: linear-gradient(135deg, var(--accent-secondary), #dc2626);
            border: none;
        }

        .btn-outline-light {
            border-color: var(--border-color);
            color: var(--text-primary);
        }

        .btn-outline-light:hover {
            background-color: var(--accent-primary);
            border-color: var(--accent-primary);
            color: white;
        }

        .form-control {
            background: var(--bg-tertiary);
            border: 1px solid var(--border-color);
            color: var(--text-primary);
        }

        .form-control:focus {
            background: var(--bg-tertiary);
            border-color: var(--accent-primary);
            box-shadow: 0 0 0 0.2rem rgba(0, 212, 255, 0.25);
            color: var(--text-primary);
        }

        .form-control::placeholder {
            color: var(--text-secondary);
        }

        .table-dark {
            background: var(--bg-secondary);
            border-color: var(--border-color);
        }

        .table-dark td, .table-dark th {
            border-color: var(--border-color);
        }

        .list-group-item {
            background: var(--bg-tertiary);
            border-color: var(--border-color);
            color: var(--text-primary);
        }

        .list-group-item:hover {
            background: rgba(0, 212, 255, 0.1);
        }

        .modal-content {
            background: var(--bg-secondary);
            border: 1px solid var(--border-color);
        }

        .modal-header {
            border-bottom: 1px solid var(--border-color);
        }

        .modal-footer {
            border-top: 1px solid var(--border-color);
        }

        .dashboard-card {
            border-radius: 15px;
            padding: 1.5rem;
            background: linear-gradient(135deg, var(--bg-secondary), var(--bg-tertiary));
            border: 1px solid var(--border-color);
            position: relative;
            overflow: hidden;
        }

        .dashboard-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 3px;
            background: linear-gradient(90deg, var(--accent-primary), var(--accent-secondary));
        }

        .dashboard-card.success::before {
            background: var(--accent-success);
        }

        .dashboard-card.warning::before {
            background: var(--accent-warning);
        }

        .dashboard-card.danger::before {
            background: var(--accent-secondary);
        }

        .cart-item {
            background: var(--bg-tertiary);
            border: 1px solid var(--border-color);
            border-radius: 10px;
        }

        .alert {
            border: none;
            border-radius: 10px;
        }

        .text-cyan {
            color: var(--accent-primary) !important;
        }

        .text-success {
            color: var(--accent-success) !important;
        }

        .text-warning {
            color: var(--accent-warning) !important;
        }

        .text-danger {
            color: var(--accent-secondary) !important;
        }

        .glow {
            box-shadow: 0 0 20px rgba(0, 212, 255, 0.3);
        }

        /* Light Mode Specific Styles */
        .light-mode {
            background: linear-gradient(135deg, #f8f9fa 0%, #e9ecef 100%) !important;
            color: #212529 !important;
        }

        .light-mode .btn-outline-light {
            border-color: #495057 !important;
            color: #495057 !important;
            background-color: transparent !important;
        }

        .light-mode .btn-outline-light:hover {
            background-color: var(--accent-primary) !important;
            border-color: var(--accent-primary) !important;
            color: white !important;
        }

        .light-mode .sidebar {
            background: linear-gradient(180deg, #ffffff 0%, #f8f9fa 100%) !important;
            border-right: 2px solid #dee2e6 !important;
            color: #212529 !important;
        }

        .light-mode .main-content {
            background: #f8f9fa !important;
            color: #212529 !important;
        }

        .light-mode .nav-link {
            color: #495057 !important;
        }

        .light-mode .nav-link:hover {
            color: var(--accent-primary) !important;
            background: rgba(0, 212, 255, 0.1) !important;
        }

        .light-mode .nav-link.active {
            background: linear-gradient(135deg, var(--accent-primary), var(--accent-secondary)) !important;
            color: white !important;
        }

        .light-mode .sidebar h4 {
            color: var(--accent-primary) !important;
            border-bottom: 1px solid #dee2e6 !important;
        }

        .light-mode .dashboard-card {
            background: linear-gradient(135deg, #ffffff, #f8f9fa) !important;
            border: 1px solid #dee2e6 !important;
            color: #212529 !important;
        }

        .light-mode .user-info-sidebar {
            background: #f8f9fa !important;
            border: 1px solid #dee2e6 !important;
            color: #212529 !important;
        }

        .light-mode .text-secondary {
            color: #6c757d !important;
        }

        .light-mode .mobile-header {
            background: #ffffff !important;
            border-bottom: 1px solid #dee2e6 !important;
            color: #212529 !important;
        }

        .light-mode .list-group-item {
            background: #ffffff !important;
            border-color: #dee2e6 !important;
            color: #212529 !important;
        }

        .light-mode .cart-item {
            background: #f8f9fa !important;
            border: 1px solid #dee2e6 !important;
            color: #212529 !important;
        }

        .light-mode .form-control {
            background: #ffffff;
            border: 1px solid #ced4da;
            color: #495057;
        }

        .light-mode .form-control:focus {
            background: #ffffff;
            border-color: var(--accent-primary);
            box-shadow: 0 0 0 0.2rem rgba(0, 212, 255, 0.25);
            color: #495057;
        }

        .modal-content {
            background: #ffffff;
            border: 1px solid #dee2e6;
        }

        .light-mode .card {
            background: #ffffff;
            border: 1px solid #dee2e6;
            color: #212529;
        }

        .light-mode .card-header {
            background: #f8f9fa;
            border-bottom: 1px solid #dee2e6;
            color: var(--accent-primary);
        }

        /* Mobile Optimizations */
        @media (max-width: 768px) {
            .sidebar {
                display: none;
                position: fixed;
                top: 0;
                left: 0;
                z-index: 1050;
                width: 280px;
                height: 100vh;
            }

            .sidebar.show {
                display: block;
            }

            .main-content {
                margin-left: 0 !important;
            }

            .mobile-header {
                background: var(--bg-secondary);
                border-bottom: 1px solid var(--border-color);
                padding: 1rem;
                position: sticky;
                top: 0;
                z-index: 1040;
            }

            .mobile-menu-btn {
                background: var(--accent-primary);
                border: none;
                color: white;
                padding: 0.5rem 1rem;
                border-radius: 8px;
            }

            .dashboard-card {
                margin-bottom: 1rem;
                padding: 1rem;
            }

            .dashboard-card h3 {
                font-size: 1.5rem;
            }

            .table-responsive {
                font-size: 0.875rem;
            }

            .btn {
                font-size: 0.875rem;
                padding: 0.5rem 1rem;
            }

            .card-body {
                padding: 1rem;
            }

            .modal-dialog {
                margin: 1rem;
            }

            .cart-item {
                margin-bottom: 0.5rem;
                padding: 0.75rem;
            }
        }

        /* Overlay for mobile menu */
        .mobile-overlay {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0, 0, 0, 0.5);
            z-index: 1040;
        }

        .mobile-overlay.show {
            display: block;
        }
    </style>
</head>
<body>
    <!-- Mobile Header -->
    <div class="mobile-header d-md-none">
        <div class="d-flex justify-content-between align-items-center">
            <button class="mobile-menu-btn" onclick="toggleMobileMenu()">
                <i class="fas fa-bars"></i> Menu
            </button>
            <h5 class="mb-0 text-cyan">
                <i class="fas fa-cash-register"></i> Kasir Digital
            </h5>
            <button class="btn btn-outline-light btn-sm" onclick="toggleTheme()" title="Toggle Dark/Light Mode">
                <i id="theme-icon" class="fas fa-sun"></i>
            </button>
        </div>
    </div>

    <!-- Mobile Overlay -->
    <div class="mobile-overlay" onclick="closeMobileMenu()"></div>

    <div class="container-fluid">
        <div class="row">
            <!-- Sidebar -->
            <div class="col-md-3 col-lg-2 sidebar p-3" id="sidebar">
                <div class="d-flex justify-content-between align-items-center mb-4 d-none d-md-flex">
                    <h4 class="mb-0">
                        <i class="fas fa-cash-register"></i> Kasir Digital
                    </h4>
                    <button class="btn btn-outline-light btn-sm" onclick="toggleTheme()" title="Toggle Dark/Light Mode">
                        <i id="theme-icon-desktop" class="fas fa-sun"></i>
                    </button>
                </div>

                <div class="user-info-sidebar mb-3 p-2" style="background: var(--bg-tertiary); border-radius: 8px; border: 1px solid var(--border-color);">
                    <div class="d-flex align-items-center mb-2">
                        <i class="fas fa-user-circle me-2 text-cyan"></i>
                        <small class="text-truncate"><?= htmlspecialchars($user['name']) ?></small>
                    </div>
                    <div class="d-flex justify-content-between align-items-center">
                        <small class="text-secondary"><?= ucfirst($user['role']) ?></small>
                        <a href="logout.php" class="btn btn-outline-danger btn-sm" title="Logout">
                            <i class="fas fa-sign-out-alt"></i>
                        </a>
                    </div>
                </div>
                <ul class="nav flex-column">
                    <li class="nav-item mb-2">
                        <a class="nav-link active" href="#" onclick="showPage('dashboard', this)">
                            <i class="fas fa-home me-2"></i> Dashboard
                        </a>
                    </li>
                    <li class="nav-item mb-2">
                        <a class="nav-link" href="#" onclick="showPage('products', this)">
                            <i class="fas fa-box me-2"></i> Kelola Produk
                        </a>
                    </li>
                    <li class="nav-item mb-2">
                        <a class="nav-link" href="#" onclick="showPage('cashier', this)">
                            <i class="fas fa-shopping-cart me-2"></i> Kasir
                        </a>
                    </li>
                    <li class="nav-item mb-2">
                        <a class="nav-link" href="#" onclick="showPage('transactions', this)">
                            <i class="fas fa-history me-2"></i> Riwayat Transaksi
                        </a>
                    </li>
                    <li class="nav-item mb-2">
                        <a class="nav-link" href="#" onclick="showPage('inventory', this)">
                            <i class="fas fa-warehouse me-2"></i> Barang Masuk
                        </a>
                    </li>
                    <?php if ($user['role'] === 'admin'): ?>
                    <li class="nav-item mb-2">
                        <a class="nav-link" href="#" onclick="showPage('users', this)">
                            <i class="fas fa-users me-2"></i> Kelola User
                        </a>
                    </li>
                    <?php endif; ?>
                </ul>
            </div>

            <!-- Main Content -->
            <div class="col-md-9 col-lg-10 main-content p-4">
                <!-- Dashboard Page -->
                <div id="dashboard" class="page-content">
                    <h2 class="mb-4 text-cyan">Dashboard</h2>
                    <div class="row">
                        <div class="col-md-3 col-sm-6 mb-3">
                            <div class="dashboard-card">
                                <div class="d-flex justify-content-between align-items-center">
                                    <div>
                                        <h6 class="text-secondary mb-1">Total Produk</h6>
                                        <h3 class="text-cyan mb-0" id="total-products">0</h3>
                                    </div>
                                    <i class="fas fa-box fa-2x text-cyan"></i>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-3 col-sm-6 mb-3">
                            <div class="dashboard-card success">
                                <div class="d-flex justify-content-between align-items-center">
                                    <div>
                                        <h6 class="text-secondary mb-1">Transaksi Hari Ini</h6>
                                        <h3 class="text-success mb-0" id="today-transactions">0</h3>
                                    </div>
                                    <i class="fas fa-shopping-cart fa-2x text-success"></i>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-3 col-sm-6 mb-3">
                            <div class="dashboard-card warning">
                                <div class="d-flex justify-content-between align-items-center">
                                    <div>
                                        <h6 class="text-secondary mb-1">Omzet Hari Ini</h6>
                                        <h3 class="text-warning mb-0" id="today-revenue">Rp 0</h3>
                                    </div>
                                    <i class="fas fa-money-bill fa-2x text-warning"></i>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-3 col-sm-6 mb-3">
                            <div class="dashboard-card danger">
                                <div class="d-flex justify-content-between align-items-center">
                                    <div>
                                        <h6 class="text-secondary mb-1">Stok Menipis</h6>
                                        <h3 class="text-danger mb-0" id="low-stock">0</h3>
                                    </div>
                                    <i class="fas fa-exclamation-triangle fa-2x text-danger"></i>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-3 col-sm-6 mb-3">
                            <div class="dashboard-card">
                                <div class="d-flex justify-content-between align-items-center">
                                    <div>
                                        <h6 class="text-secondary mb-1">Total Omzet Bulan Ini</h6>
                                        <h3 class="text-cyan mb-0" id="monthly-revenue">Rp 0</h3>
                                    </div>
                                    <i class="fas fa-chart-line fa-2x text-cyan"></i>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-3 col-sm-6 mb-3">
                            <div class="dashboard-card success">
                                <div class="d-flex justify-content-between align-items-center">
                                    <div>
                                        <h6 class="text-secondary mb-1">Transaksi Bulan Ini</h6>
                                        <h3 class="text-success mb-0" id="monthly-transactions">0</h3>
                                    </div>
                                    <i class="fas fa-calendar fa-2x text-success"></i>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-3 col-sm-6 mb-3">
                            <div class="dashboard-card warning">
                                <div class="d-flex justify-content-between align-items-center">
                                    <div>
                                        <h6 class="text-secondary mb-1">Rata-rata per Transaksi</h6>
                                        <h3 class="text-warning mb-0" id="avg-transaction">Rp 0</h3>
                                    </div>
                                    <i class="fas fa-calculator fa-2x text-warning"></i>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-3 col-sm-6 mb-3">
                            <div class="dashboard-card danger">
                                <div class="d-flex justify-content-between align-items-center">
                                    <div>
                                        <h6 class="text-secondary mb-1">Produk Terlaris</h6>
                                        <h3 class="text-danger mb-0" id="best-product">-</h3>
                                    </div>
                                    <i class="fas fa-star fa-2x text-danger"></i>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Recent Transactions -->
                    <div class="row mt-4">
                        <div class="col-md-6">
                            <div class="card">
                                <div class="card-header">
                                    <h5><i class="fas fa-clock"></i> Transaksi Terbaru</h5>
                                </div>
                                <div class="card-body">
                                    <div id="recent-transactions" class="list-group">
                                        <p class="text-center text-muted">Memuat data...</p>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="card">
                                <div class="card-header">
                                    <h5><i class="fas fa-exclamation-triangle"></i> Produk Stok Rendah</h5>
                                </div>
                                <div class="card-body">
                                    <div id="low-stock-products" class="list-group">
                                        <p class="text-center text-muted">Memuat data...</p>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Products Page -->
                <div id="products" class="page-content d-none">
                    <div class="d-flex justify-content-between align-items-center mb-4">
                        <h2 class="text-cyan">Kelola Produk</h2>
                        <button class="btn btn-primary glow" onclick="showAddProductModal()">
                            <i class="fas fa-plus"></i> <span class="d-none d-sm-inline">Tambah</span> Produk
                        </button>
                    </div>
                    <div class="card">
                        <div class="card-body">
                            <div class="table-responsive">
                                <table class="table table-dark table-striped" id="products-table">
                                    <thead>
                                        <tr>
                                            <th>Barcode</th>
                                            <th>Nama Produk</th>
                                            <th class="d-none d-md-table-cell">Kategori</th>
                                            <th>Harga</th>
                                            <th>Stok</th>
                                            <th>Aksi</th>
                                        </tr>
                                    </thead>
                                    <tbody id="products-tbody">
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Cashier Page -->
                <div id="cashier" class="page-content d-none">
                    <h2 class="mb-4 text-cyan">Kasir</h2>
                    <div class="row">
                        <div class="col-lg-8 col-md-12 mb-3">
                            <div class="card mb-3 glow">
                                <div class="card-header">
                                    <h5><i class="fas fa-search"></i> Pilih Produk</h5>
                                </div>
                                <div class="card-body">
                                    <div class="row mb-3">
                                        <div class="col-sm-6 mb-2">
                                            <input type="text" class="form-control" id="search-product" 
                                                   placeholder="Cari produk atau scan barcode...">
                                        </div>
                                        <div class="col-sm-2 col-4 mb-2">
                                            <input type="number" class="form-control" id="product-qty" 
                                                   placeholder="Qty" value="1" min="1">
                                        </div>
                                        <div class="col-sm-4 col-8 mb-2">
                            <button class="btn btn-success w-100" onclick="addToCart()">
                                <i class="fas fa-cart-plus"></i> <span class="d-none d-sm-inline">Tambah</span>
                            </button>
                        </div>
                                    </div>
                                    <div id="product-suggestions" class="list-group"></div>
                                </div>
                            </div>
                        </div>
                        <div class="col-lg-4 col-md-12">
                            <div class="card glow">
                                <div class="card-header">
                                    <h5><i class="fas fa-shopping-bag"></i> Keranjang Belanja</h5>
                                </div>
                                <div class="card-body">
                                    <div id="cart-items"></div>
                                    <hr>
                                    <div class="d-flex justify-content-between">
                                        <strong>Total: </strong>
                                        <strong id="cart-total" class="text-cyan">Rp 0</strong>
                                    </div>
                                    <div class="mt-3">
                                        <input type="number" class="form-control mb-2" id="payment-amount" 
                                               placeholder="Jumlah Bayar">
                                        <div class="d-flex justify-content-between mb-2">
                                            <span>Kembalian:</span>
                                            <span id="change-amount" class="text-success">Rp 0</span>
                                        </div>
                                        <button class="btn btn-primary w-100 mb-2" onclick="processTransaction()">
                                            <i class="fas fa-credit-card"></i> Proses Transaksi
                                        </button>
                                        <button class="btn btn-danger w-100" onclick="clearCart()">
                                            <i class="fas fa-trash"></i> Bersihkan
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Transactions Page -->
                <div id="transactions" class="page-content d-none">
                    <h2 class="mb-4 text-cyan">Riwayat Transaksi</h2>
                    <div class="card">
                        <div class="card-body">
                            <div class="table-responsive">
                                <table class="table table-dark table-striped" id="transactions-table">
                                    <thead>
                                        <tr>
                                            <th>ID</th>
                                            <th>Tanggal</th>
                                            <th>Total</th>
                                            <th>Aksi</th>
                                        </tr>
                                    </thead>
                                    <tbody id="transactions-tbody">
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Inventory Page -->
                <div id="inventory" class="page-content d-none">
                    <div class="d-flex justify-content-between align-items-center mb-4">
                        <h2 class="text-cyan">Barang Masuk</h2>
                        <button class="btn btn-primary glow" onclick="showAddStockModal()">
                            <i class="fas fa-plus"></i> <span class="d-none d-sm-inline">Tambah</span> Stok
                        </button>
                    </div>
                    <div class="card">
                        <div class="card-body">
                            <div class="table-responsive">
                                <table class="table table-dark table-striped" id="inventory-table">
                                    <thead>
                                        <tr>
                                            <th>Tanggal</th>
                                            <th>Produk</th>
                                            <th>Qty</th>
                                            <th>Stok Before</th>
                                            <th>Stok After</th>
                                            <th>Keterangan</th>
                                        </tr>
                                    </thead>
                                    <tbody id="inventory-tbody">
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Users Management Page (Admin Only) -->
                <?php if ($user['role'] === 'admin'): ?>
                <div id="users" class="page-content d-none">
                    <div class="d-flex justify-content-between align-items-center mb-4">
                        <h2 class="text-cyan">Kelola User</h2>
                        <button class="btn btn-primary glow" onclick="showAddUserModal()">
                            <i class="fas fa-plus"></i> <span class="d-none d-sm-inline">Tambah</span> User
                        </button>
                    </div>
                    <div class="card">
                        <div class="card-body">
<div class="table-responsive">
                                <table class="table table-dark table-striped" id="users-table">
                                    <thead>
                                        <tr>
                                            <th>Username</th>
                                            <th>Nama</th>
                                            <th>Role</th>
                                            <th>Dibuat</th>
                                            <th>Aksi</th>
                                        </tr>
                                    </thead>
                                    <tbody id="users-tbody">
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- Product Modal -->
    <div class="modal fade" id="productModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title text-cyan" id="productModalTitle">Tambah Produk</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <form id="productForm">
                        <input type="hidden" id="product-id">
                        <div class="mb-3">
                            <label for="product-name" class="form-label">Nama Produk</label>
                            <input type="text" class="form-control" id="product-name" required>
                        </div>
                        <div class="mb-3">
                            <label for="product-category" class="form-label">Kategori</label>
                            <input type="text" class="form-control" id="product-category" required>
                        </div>
                        <div class="mb-3">
                            <label for="product-price" class="form-label">Harga</label>
                            <input type="number" class="form-control" id="product-price" required>
                        </div>
                        <div class="mb-3">
                            <label for="product-stock" class="form-label">Stok</label>
                            <input type="number" class="form-control" id="product-stock" required>
                        </div>
                        <div class="mb-3">
                            <label for="product-barcode" class="form-label">Barcode</label>
                            <input type="text" class="form-control" id="product-barcode" required>
                        </div>
                    </form>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                    <button type="button" class="btn btn-primary" onclick="saveProduct()">Simpan</button>
                </div>
            </div>
        </div>
    </div>

    <!-- Transaction Detail Modal -->
    <div class="modal fade" id="transactionModal" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title text-cyan">Detail Transaksi</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div id="transaction-detail"></div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-primary" onclick="printReceipt()">
                        <i class="fas fa-print"></i> Cetak Struk
                    </button>
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Tutup</button>
                </div>
            </div>
        </div>
    </div>

    <!-- Stock Modal -->
    <div class="modal fade" id="stockModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title text-cyan">Tambah Stok Barang</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <form id="stockForm">
                        <div class="mb-3">
                            <label for="stock-product" class="form-label">Pilih Produk</label>
                            <select class="form-control" id="stock-product" required>
                                <option value="">-- Pilih Produk --</option>
                            </select>
                        </div>
                        <div class="mb-3">
                            <label for="stock-quantity" class="form-label">Jumlah Masuk</label>
                            <input type="number" class="form-control" id="stock-quantity" required min="1">
                        </div>
                        <div class="mb-3">
                            <label for="purchase-price" class="form-label">Harga Perolehan</label>
                            <input type="number" class="form-control" id="purchase-price" required min="0" step="0.01">
                        </div>
                        <div class="mb-3">
                            <label for="margin-type" class="form-label">Tipe Margin</label>
                            <select class="form-control" id="margin-type" onchange="updateMarginLabel()">
                                <option value="percentage">Persentase (%)</option>
                                <option value="fixed">Nominal (Rp)</option>
                            </select>
                        </div>
                        <div class="mb-3">
                            <label for="margin-value" class="form-label" id="margin-label">Margin (%)</label>
                            <input type="number" class="form-control" id="margin-value" min="0" step="0.01" value="0">
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Harga Jual (Otomatis)</label>
                            <input type="text" class="form-control" id="selling-price-display" readonly>
                        </div>
                        <div class="mb-3">
                            <label for="stock-notes" class="form-label">Keterangan</label>
                            <textarea class="form-control" id="stock-notes" rows="3" placeholder="Keterangan tambahan (opsional)"></textarea>
                        </div>
                    </form>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                    <button type="button" class="btn btn-primary" onclick="addStock()">Tambah Stok</button>
                </div>
            </div>
        </div>
    </div>

    <!-- User Modal (Admin Only) -->
    <?php if ($user['role'] === 'admin'): ?>
    <div class="modal fade" id="userModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title text-cyan" id="userModalTitle">Tambah User</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <form id="userForm">
                        <input type="hidden" id="user-id">
                        <div class="mb-3">
                            <label for="user-username" class="form-label">Username</label>
                            <input type="text" class="form-control" id="user-username" required>
                        </div>
                        <div class="mb-3">
                            <label for="user-name" class="form-label">Nama Lengkap</label>
                            <input type="text" class="form-control" id="user-name" required>
                        </div>
                        <div class="mb-3">
                            <label for="user-password" class="form-label">Password</label>
                            <input type="password" class="form-control" id="user-password" required>
                        </div>
                        <div class="mb-3">
                            <label for="user-role" class="form-label">Role</label>
                            <select class="form-control" id="user-role" required>
                                <option value="kasir">Kasir</option>
                                <option value="admin">Admin</option>
                            </select>
                        </div>
                    </form>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                    <button type="button" class="btn btn-primary" onclick="saveUser()">Simpan</button>
                </div>
            </div>
        </div>
    </div>
    <?php endif; ?>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://code.jquery.com/jquery-3.7.0.min.js"></script>
    <script src="https://cdn.datatables.net/1.13.4/js/jquery.dataTables.min.js"></script>
    <script src="https://cdn.datatables.net/1.13.4/js/dataTables.bootstrap5.min.js"></script>
    <script src="https://cdn.datatables.net/buttons/2.3.6/js/dataTables.buttons.min.js"></script>
    <script src="https://cdn.datatables.net/buttons/2.3.6/js/buttons.bootstrap5.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jszip/3.10.1/jszip.min.js"></script>
    <script src="https://cdn.datatables.net/buttons/2.3.6/js/buttons.html5.min.js"></script>
    <script src="https://cdn.datatables.net/buttons/2.3.6/js/buttons.print.min.js"></script>
    <script src="assets/script.js"></script>
    <script>
        // Mobile menu functions
        function toggleMobileMenu() {
            const sidebar = document.getElementById('sidebar');
            const overlay = document.querySelector('.mobile-overlay');
            sidebar.classList.add('show');
            overlay.classList.add('show');
        }

        function closeMobileMenu() {
            const sidebar = document.getElementById('sidebar');
            const overlay = document.querySelector('.mobile-overlay');
            sidebar.classList.remove('show');
            overlay.classList.remove('show');
        }

        // Close mobile menu when nav link is clicked
        document.addEventListener('DOMContentLoaded', function() {
            const navLinks = document.querySelectorAll('.nav-link');
            navLinks.forEach(link => {
                link.addEventListener('click', closeMobileMenu);
            });
        });
    </script>
</body>
</html>