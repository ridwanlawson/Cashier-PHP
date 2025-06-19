The code has been modified to fix tax calculation in receipt generation and showReceipt function by checking if tax_enabled is true or 1.
```
```replit_final_file
// Global variables
let products = [];
let cart = [];
let transactions = [];
let users = [];
let inventory = [];
let appSettings = {};

// API request helper
async function apiRequest(url, options = {}) {
    try {
        const response = await fetch(url, {
            headers: {
                'Content-Type': 'application/json',
                ...options.headers
            },
            ...options
        });

        if (!response.ok) {
            throw new Error(`HTTP error! status: ${response.status}`);
        }

        return await response.json();
    } catch (error) {
        console.error('API request failed:', error);
        throw error;
    }
}

// Show alert message
function showAlert(message, type = 'info') {
    // Create alert element
    const alertDiv = document.createElement('div');
    alertDiv.className = `alert alert-${type} alert-dismissible fade show position-fixed`;
    alertDiv.style.cssText = 'top: 20px; right: 20px; z-index: 9999; min-width: 300px;';
    alertDiv.innerHTML = `
        ${message}
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    `;

    document.body.appendChild(alertDiv);

    // Auto remove after 5 seconds
    setTimeout(() => {
        if (alertDiv.parentNode) {
            alertDiv.remove();
        }
    }, 5000);
}

// Page navigation
function showPage(pageId, linkElement) {
    // Hide all pages
    document.querySelectorAll('.page-content').forEach(page => {
        page.classList.add('d-none');
    });

    // Show selected page
    const targetPage = document.getElementById(pageId);
    if (targetPage) {
        targetPage.classList.remove('d-none');
    }

    // Update active nav link
    document.querySelectorAll('.nav-link').forEach(link => {
        link.classList.remove('active');
    });

    if (linkElement) {
        linkElement.classList.add('active');
    }

    // Load page specific data
    switch(pageId) {
        case 'products':
            loadProducts();
            break;
        case 'cashier':
            loadProducts();
            updateCart();
            break;
        case 'transactions':
            loadTransactions();
            break;
        case 'inventory':
            loadInventory();
            break;
        case 'users':
            loadUsers();
            break;
        case 'settings':
            loadSettings();
            break;
        case 'dashboard':
            loadDashboardStats();
            break;
    }

    // Close mobile menu if open
    closeMobileMenu();
}

// Theme toggle
function toggleTheme() {
    const body = document.body;
    const themeIcons = document.querySelectorAll('#theme-icon, #theme-icon-desktop');

    body.classList.toggle('light-mode');

    const isLight = body.classList.contains('light-mode');

    themeIcons.forEach(icon => {
        icon.className = isLight ? 'fas fa-moon' : 'fas fa-sun';
    });

    localStorage.setItem('theme', isLight ? 'light' : 'dark');
}

function applyTheme(theme) {
    const body = document.body;
    const themeIcons = document.querySelectorAll('#theme-icon, #theme-icon-desktop');

    if (theme === 'light') {
        body.classList.add('light-mode');
        themeIcons.forEach(icon => {
            icon.className = 'fas fa-moon';
        });
    } else {
        body.classList.remove('light-mode');
        themeIcons.forEach(icon => {
            icon.className = 'fas fa-sun';
        });
    }
}

// Load basic settings for all users
async function loadBasicSettings() {
    try {
        const settings = await apiRequest('api/settings.php');
        appSettings = settings;
        console.log('Basic settings loaded:', appSettings);
    } catch (error) {
        console.error('Failed to load basic settings:', error);
        // Set default settings if API fails
        appSettings = {
            app_name: 'Kasir Digital',
            currency: 'Rp',
            tax_enabled: false,
            tax_rate: 0
        };
    }
}

// Load settings (admin only)
async function loadSettings() {
    try {
        console.log('Loading app settings...');
        const settings = await apiRequest('api/settings.php');
        appSettings = settings;

        // Update form fields if they exist (only for admin users)
        const appNameField = document.getElementById('app-name');
        if (appNameField) {
            appNameField.value = settings.app_name || 'Kasir Digital';
            document.getElementById('store-name').value = settings.store_name || '';
            document.getElementById('store-address').value = settings.store_address || '';
            document.getElementById('store-phone').value = settings.store_phone || '';
            document.getElementById('store-email').value = settings.store_email || '';
            document.getElementById('store-website').value = settings.store_website || '';
            document.getElementById('store-social-media').value = settings.store_social_media || '';
            document.getElementById('receipt-footer').value = settings.receipt_footer || '';
            document.getElementById('receipt-header').value = settings.receipt_header || '';
            document.getElementById('currency').value = settings.currency || 'Rp';
            document.getElementById('logo-url').value = settings.logo_url || '';
            document.getElementById('tax-enabled').checked = settings.tax_enabled || false;
            document.getElementById('tax-rate').value = settings.tax_rate || 0;
        }
    } catch (error) {
        console.error('Error loading settings:', error);
        showAlert('Gagal memuat pengaturan', 'danger');
    }
}

// Save settings
async function saveSettings() {
    try {
        // Validate required fields
        const appName = document.getElementById('app-name');
        const storeName = document.getElementById('store-name');
        const receiptFooter = document.getElementById('receipt-footer');

        if (!appName || !appName.value.trim()) {
            showAlert('Nama aplikasi harus diisi!', 'warning');
            appName?.focus();
            return;
        }

        if (!storeName || !storeName.value.trim()) {
            showAlert('Nama toko harus diisi!', 'warning');
            storeName?.focus();
            return;
        }

        if (!receiptFooter || !receiptFooter.value.trim()) {
            showAlert('Footer struk harus diisi!', 'warning');
            receiptFooter?.focus();
            return;
        }

        const formData = {
            app_name: appName.value.trim(),
            store_name: storeName.value.trim(),
            store_address: document.getElementById('store-address')?.value.trim() || '',
            store_phone: document.getElementById('store-phone')?.value.trim() || '',
            store_email: document.getElementById('store-email')?.value.trim() || '',
            store_website: document.getElementById('store-website')?.value.trim() || '',
            store_social_media: document.getElementById('store-social-media')?.value.trim() || '',
            receipt_footer: receiptFooter.value.trim(),
            receipt_header: document.getElementById('receipt-header')?.value.trim() || '',
            currency: document.getElementById('currency')?.value.trim() || 'Rp',
            logo_url: document.getElementById('logo-url')?.value.trim() || '',
            tax_enabled: document.getElementById('tax-enabled')?.checked || false,
            tax_rate: parseFloat(document.getElementById('tax-rate')?.value) || 0
        };

        console.log('Saving settings...', formData);

        // Show loading state
        showAlert('Menyimpan pengaturan...', 'info');

        const response = await fetch('api/settings.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'Accept': 'application/json'
            },
            body: JSON.stringify(formData)
        });

        const result = await response.json();

        if (result.success) {
            showAlert('Pengaturan berhasil disimpan!', 'success');
            appSettings = { ...appSettings, ...formData };
        } else {
            throw new Error(result.error || 'Gagal menyimpan pengaturan');
        }
    } catch (error) {
        console.error('Error saving settings:', error);
        showAlert('Gagal menyimpan pengaturan: ' + error.message, 'danger');
    }
}

// Dashboard functions
async function loadDashboardStats() {
    try {
        const stats = await apiRequest('api/transactions.php?stats=1');

        // Update dashboard cards
        document.getElementById('total-products').textContent = stats.total_products || 0;
        document.getElementById('today-transactions').textContent = stats.today_transactions || 0;
        document.getElementById('today-revenue').textContent = `${appSettings.currency || 'Rp'} ${formatNumber(stats.today_revenue || 0)}`;
        document.getElementById('monthly-revenue').textContent = `${appSettings.currency || 'Rp'} ${formatNumber(stats.monthly_revenue || 0)}`;
        document.getElementById('monthly-transactions').textContent = stats.monthly_transactions || 0;
        document.getElementById('avg-transaction').textContent = `${appSettings.currency || 'Rp'} ${formatNumber(stats.avg_transaction || 0)}`;
        document.getElementById('best-product').textContent = stats.best_product || '-';
        document.getElementById('low-stock').textContent = stats.low_stock || 0;

        // Load recent transactions
        loadRecentTransactions();

        // Load low stock products
        loadLowStockProducts();

    } catch (error) {
        console.error('Error loading dashboard stats:', error);
        showAlert('Gagal memuat statistik dashboard', 'danger');
    }
}

async function loadRecentTransactions() {
    try {
        const recentTransactions = await apiRequest('api/transactions.php?recent=5');
        const container = document.getElementById('recent-transactions');

        if (!container) return;

        if (!recentTransactions || recentTransactions.length === 0) {
            container.innerHTML = '<p class="text-center text-muted">Belum ada transaksi</p>';
            return;
        }

        container.innerHTML = recentTransactions.map(transaction => `
            <div class="list-group-item d-flex justify-content-between align-items-center">
                <div>
                    <strong>Transaksi #${transaction.id}</strong><br>
                    <small class="text-muted">${new Date(transaction.transaction_date).toLocaleString('id-ID')}</small>
                </div>
                <span class="badge bg-primary">${appSettings.currency || 'Rp'} ${formatNumber(transaction.total)}</span>
            </div>
        `).join('');

    } catch (error) {
        console.error('Error loading recent transactions:', error);
    }
}

async function loadLowStockProducts() {
    try {
        const lowStockProducts = await apiRequest('api/products.php?lowstock=10');
        const container = document.getElementById('low-stock-products');

        if (!container) return;

        if (!lowStockProducts || lowStockProducts.length === 0) {
            container.innerHTML = '<p class="text-center text-muted">Semua produk stok aman</p>';
            return;
        }

        container.innerHTML = lowStockProducts.map(product => `
            <div class="list-group-item d-flex justify-content-between align-items-center">
                <div>
                    <strong>${product.name}</strong><br>
                    <small class="text-muted">${product.category}</small>
                </div>
                <span class="badge bg-warning text-dark">${product.stock}</span>
            </div>
        `).join('');

    } catch (error) {
        console.error('Error loading low stock products:', error);
    }
}

// Product functions
async function loadProducts() {
    try {
        products = await apiRequest('api/products.php');
        displayProducts();
        populateStockProductSelect();
    } catch (error) {
        console.error('Error loading products:', error);
        showAlert('Gagal memuat data produk', 'danger');
    }
}

function displayProducts() {
    const tbody = document.getElementById('products-tbody');
    if (!tbody) return;

    // Destroy existing DataTable if it exists
    if ($.fn.DataTable.isDataTable('#products-table')) {
        $('#products-table').DataTable().destroy();
    }

    tbody.innerHTML = '';

    if (!Array.isArray(products) || products.length === 0) {
        tbody.innerHTML = '<tr><td colspan="6" class="text-center">Tidak ada data produk</td></tr>';
        return;
    }

    products.forEach(product => {
        const row = document.createElement('tr');
        row.innerHTML = `
            <td>${product.barcode}</td>
            <td>${product.name}</td>
            <td class="d-none d-md-table-cell">${product.category}</td>
            <td>${appSettings.currency || 'Rp'} ${formatNumber(product.price)}</td>
            <td>${product.stock}</td>
            <td>
                <button class="btn btn-warning btn-sm me-1" onclick="editProduct(${product.id})">
                    <i class="fas fa-edit"></i>
                </button>
                <button class="btn btn-danger btn-sm" onclick="deleteProduct(${product.id})">
                    <i class="fas fa-trash"></i>
                </button>
            </td>
        `;
        tbody.appendChild(row);
    });

    // Initialize DataTable
    setTimeout(() => {
        $('#products-table').DataTable({
            language: {
                url: 'https://cdn.datatables.net/plug-ins/1.13.4/i18n/id.json'
            },
            dom: 'Bfrtip',
            buttons: [
                'copy', 'csv', 'excel', 'pdf', 'print'
            ],
            pageLength: 25,
            responsive: true
        });
    }, 100);
}

function showAddProductModal() {
    document.getElementById('productModalTitle').textContent = 'Tambah Produk';
    document.getElementById('productForm').reset();
    document.getElementById('product-id').value = '';
    new bootstrap.Modal(document.getElementById('productModal')).show();
}

function editProduct(id) {
    const product = products.find(p => p.id === id);
    if (!product) return;

    document.getElementById('productModalTitle').textContent = 'Edit Produk';
    document.getElementById('product-id').value = product.id;
    document.getElementById('product-name').value = product.name;
    document.getElementById('product-category').value = product.category;
    document.getElementById('product-price').value = product.price;
    document.getElementById('product-stock').value = product.stock;
    document.getElementById('product-barcode').value = product.barcode;

    new bootstrap.Modal(document.getElementById('productModal')).show();
}

async function saveProduct() {
    try {
        const id = document.getElementById('product-id').value;
        const productData = {
            name: document.getElementById('product-name').value.trim(),
            category: document.getElementById('product-category').value.trim(),
            price: parseFloat(document.getElementById('product-price').value),
            stock: parseInt(document.getElementById('product-stock').value),
            barcode: document.getElementById('product-barcode').value.trim()
        };

        // Validation
        if (!productData.name || !productData.category || !productData.barcode) {
            showAlert('Semua field harus diisi!', 'warning');
            return;
        }

        if (productData.price <= 0 || productData.stock < 0) {
            showAlert('Harga harus lebih dari 0 dan stok tidak boleh negatif!', 'warning');
            return;
        }

        let result;
        if (id) {
            // Update existing product
            productData.id = parseInt(id);
            result = await apiRequest('api/products.php', {
                method: 'PUT',
                body: JSON.stringify(productData)
            });
        } else {
            // Add new product
            result = await apiRequest('api/products.php', {
                method: 'POST',
                body: JSON.stringify(productData)
            });
        }

        if (result.success) {
            showAlert(result.message, 'success');
            bootstrap.Modal.getInstance(document.getElementById('productModal')).hide();
            loadProducts();
        } else {
            showAlert(result.error, 'danger');
        }
    } catch (error) {
        console.error('Error saving product:', error);
        showAlert('Gagal menyimpan produk: ' + error.message, 'danger');
    }
}

async function deleteProduct(id) {
    if (!confirm('Apakah Anda yakin ingin menghapus produk ini?')) return;

    try {
        const result = await apiRequest(`api/products.php?id=${id}`, {
            method: 'DELETE'
        });

        if (result.success) {
            showAlert(result.message, 'success');
            loadProducts();
        } else {
            showAlert(result.error, 'danger');
        }
    } catch (error) {
        console.error('Error deleting product:', error);
        showAlert('Gagal menghapus produk: ' + error.message, 'danger');
    }
}

// Search product by barcode and auto-add
async function searchProductByBarcode(barcode) {
    try {
        const product = await apiRequest(`api/products.php?barcode=${encodeURIComponent(barcode)}`);
        if (product) {
            selectProduct(product);
        } else {
            showAlert('Produk dengan barcode tersebut tidak ditemukan!', 'warning');
            document.getElementById('search-product').value = '';
        }
    } catch (error) {
        console.error('Error searching product by barcode:', error);
        showAlert('Error mencari produk!', 'danger');
    }
}

// Cashier functions
async function searchProducts() {
    const searchTerm = document.getElementById('search-product').value.trim();

    if (searchTerm.length < 2) {
        document.getElementById('product-suggestions').innerHTML = '';
        return;
    }

    try {
        const searchResults = await apiRequest(`api/products.php?search=${encodeURIComponent(searchTerm)}`);
        displayProductSuggestions(searchResults);
    } catch (error) {
        console.error('Error searching products:', error);
    }
}

function displayProductSuggestions(searchResults) {
    const container = document.getElementById('product-suggestions');
    if (!container) return;

    container.innerHTML = '';

    if (!searchResults || searchResults.length === 0) {
        container.innerHTML = '<div class="list-group-item">Produk tidak ditemukan</div>';
        return;
    }

    searchResults.forEach(product => {
        const item = document.createElement('div');
        item.className = 'list-group-item list-group-item-action cursor-pointer';
        item.innerHTML = `
            <div class="d-flex justify-content-between align-items-center">
                <div>
                    <strong>${product.name}</strong><br>
                    <small class="text-muted">${product.category} - Stok: ${product.stock}</small>
                </div>
                <span class="text-cyan">${appSettings.currency || 'Rp'} ${formatNumber(product.price)}</span>
            </div>
        `;
        item.onclick = () => selectProduct(product);
        container.appendChild(item);
    });
}

function selectProduct(product) {
    document.getElementById('search-product').value = product.name;
    document.getElementById('product-suggestions').innerHTML = '';

    // Auto add to cart with quantity 1
    const qty = 1;

    if (qty > product.stock) {
        showAlert('Stok tidak mencukupi!', 'warning');
        return;
    }

    // Check if product already in cart
    const existingItemIndex = cart.findIndex(item => item.product_id === product.id);

    if (existingItemIndex >= 0) {
        // Update quantity
        const newQty = cart[existingItemIndex].quantity + qty;
        if (newQty > product.stock) {
            showAlert('Stok tidak mencukupi!', 'warning');
            return;
        }
        cart[existingItemIndex].quantity = newQty;
        cart[existingItemIndex].subtotal = cart[existingItemIndex].price * newQty;
    } else {
        // Add new item
        cart.push({
            product_id: product.id,
            name: product.name,
            price: product.price,
            quantity: qty,
            subtotal: product.price * qty
        });
    }

    updateCart();

    // Clear search
    document.getElementById('search-product').value = '';
    document.getElementById('product-suggestions').innerHTML = '';

    showAlert('Produk ditambahkan ke keranjang!', 'success');
}

function addToCart() {
    const searchTerm = document.getElementById('search-product').value.trim();

    if (!searchTerm) {
        showAlert('Masukkan nama produk atau barcode!', 'warning');
        return;
    }

    // If it looks like a barcode, search by barcode
    if (/^\d+$/.test(searchTerm) && searchTerm.length >= 5) {
        searchProductByBarcode(searchTerm);
    } else {
        showAlert('Pilih produk dari daftar pencarian terlebih dahulu!', 'warning');
    }
}

function updateCart() {
    const cartContainer = document.getElementById('cart-items');
    const subtotalElement = document.getElementById('cart-subtotal');
    const taxLineElement = document.getElementById('tax-line');
    const taxElement = document.getElementById('cart-tax');
    const totalElement = document.getElementById('cart-total');

    if (!cartContainer) return;

    cartContainer.innerHTML = '';

    if (cart.length === 0) {
        cartContainer.innerHTML = '<p class="text-center text-muted">Keranjang kosong</p>';
        if (subtotalElement) subtotalElement.textContent = `${appSettings.currency || 'Rp'} 0`;
        if (totalElement) totalElement.textContent = `${appSettings.currency || 'Rp'} 0`;
        if (taxLineElement) taxLineElement.style.display = 'none';
        return;
    }

    cart.forEach((item, index) => {
        const cartItem = document.createElement('div');
        cartItem.className = 'cart-item p-2 mb-2';
        cartItem.innerHTML = `
            <div class="d-flex justify-content-between align-items-center">
                <div class="flex-grow-1">
                    <small><strong>${item.name}</strong></small><br>
                    <small class="text-muted">${appSettings.currency || 'Rp'} ${formatNumber(item.price)} x ${item.quantity}</small>
                </div>
                <div class="text-end">
                    <div class="text-cyan"><strong>${appSettings.currency || 'Rp'} ${formatNumber(item.subtotal)}</strong></div>
                    <div class="btn-group btn-group-sm mt-1">
                        <button class="btn btn-secondary" onclick="decreaseQty(${index})">-</button>
                        <button class="btn btn-info" onclick="increaseQty(${index})">+</button>
                        <button class="btn btn-danger" onclick="removeFromCart(${index})">
                            <i class="fas fa-trash"></i>
                        </button>
                    </div>
                </div>
            </div>
        `;
        cartContainer.appendChild(cartItem);
    });

    // Calculate totals
    const subtotal = cart.reduce((sum, item) => sum + item.subtotal, 0);
    const taxEnabled = appSettings.tax_enabled || false;
    const taxRate = parseFloat(appSettings.tax_rate) || 0;

    let taxAmount = 0;
    if (taxEnabled && taxRate > 0) {
        taxAmount = subtotal * (taxRate / 100);
        if (taxLineElement) {
            taxLineElement.style.display = 'flex';
            if (taxElement) taxElement.textContent = `${appSettings.currency || 'Rp'} ${formatNumber(taxAmount)}`;
        }
    } else {
        if (taxLineElement) taxLineElement.style.display = 'none';
    }

    const total = subtotal + taxAmount;

    if (subtotalElement) subtotalElement.textContent = `${appSettings.currency || 'Rp'} ${formatNumber(subtotal)}`;
    if (totalElement) totalElement.textContent = `${appSettings.currency || 'Rp'} ${formatNumber(total)}`;

    // Update change calculation
    calculateChange();
}

function increaseQty(index) {
    const item = cart[index];
    const product = products.find(p => p.id === item.product_id);

    if (product && item.quantity < product.stock) {
        cart[index].quantity += 1;
        cart[index].subtotal = cart[index].price * cart[index].quantity;
        updateCart();
    } else {
        showAlert('Stok tidak mencukupi!', 'warning');
    }
}

function decreaseQty(index) {
    if (cart[index].quantity > 1) {
        cart[index].quantity -= 1;
        cart[index].subtotal = cart[index].price * cart[index].quantity;
        updateCart();
    } else {
        removeFromCart(index);
    }
}

function removeFromCart(index) {
    cart.splice(index, 1);
    updateCart();
    showAlert('Item dihapus dari keranjang!', 'info');
}

function clearCart() {
    cart = [];
    updateCart();
    document.getElementById('payment-amount').value = '';
    document.getElementById('change-amount').textContent = `${appSettings.currency || 'Rp'} 0`;
}

function calculateChange() {
    const subtotal = cart.reduce((sum, item) => sum + item.subtotal, 0);
    const taxEnabled = appSettings.tax_enabled || false;
    const taxRate = parseFloat(appSettings.tax_rate) || 0;

    let taxAmount = 0;
    if (taxEnabled && taxRate > 0) {
        taxAmount = subtotal * (taxRate / 100);
    }

    const total = subtotal + taxAmount;
    const paymentInput = document.getElementById('payment-amount');
    const changeElement = document.getElementById('change-amount');

    if (paymentInput && changeElement) {
        const payment = parseFloat(paymentInput.value) || 0;
        const change = payment - total;
        changeElement.textContent = `${appSettings.currency || 'Rp'} ${formatNumber(Math.max(0, change))}`;
    }
}

async function processTransaction() {
    if (cart.length === 0) {
        showAlert('Keranjang masih kosong!', 'warning');
        return;
    }

    const subtotal = cart.reduce((sum, item) => sum + item.subtotal, 0);
    const taxEnabled = appSettings.tax_enabled || false;
    const taxRate = parseFloat(appSettings.tax_rate) || 0;

    let taxAmount = 0;
    if (taxEnabled && taxRate > 0) {
        taxAmount = subtotal * (taxRate / 100);
    }

    const total = subtotal + taxAmount;
    const paymentInput = document.getElementById('payment-amount');
    const payment = parseFloat(paymentInput ? paymentInput.value : 0) || 0;

    if (payment < total) {
        showAlert('Jumlah pembayaran kurang!', 'warning');
        return;
    }

    // Validate cart items against current stock
    for (let item of cart) {
        const currentProduct = products.find(p => p.id === item.product_id);
        if (!currentProduct) {
            showAlert(`Produk ${item.name} tidak ditemukan!`, 'danger');
            return;
        }
        if (currentProduct.stock < item.quantity) {
            showAlert(`Stok ${item.name} tidak mencukupi! (Tersedia: ${currentProduct.stock})`, 'warning');
            return;
        }
    }

    try {
        console.log('Processing transaction:', { total, items_count: cart.length });

        const transactionData = {
            subtotal: subtotal,
            tax_amount: taxAmount,
            total: total,
            items: cart.map(item => ({
                product_id: item.product_id,
                quantity: item.quantity,
                price: item.price,
                subtotal: item.subtotal
            }))
        };

        const result = await apiRequest('api/transactions.php', {
            method: 'POST',
            body: JSON.stringify(transactionData)
        });

        if (result.success) {
            showAlert('Transaksi berhasil!', 'success');

            // Show receipt
            showReceipt(result.transaction_id, cart, total, payment);

            // Clear cart
            clearCart();

            // Reload products to update stock
            loadProducts();

        } else {
            showAlert('Gagal memproses transaksi: ' + result.error, 'danger');
        }
    } catch (error) {
        console.error('Error processing transaction:', error);
        showAlert('Gagal memproses transaksi: ' + error.message, 'danger');
    }
}

function showReceipt(transactionId, cartItems, total, payment) {
    const change = payment - total;

    // Store current receipt data for printing
    window.currentReceiptData = {
        id: transactionId,
        items: cartItems,
        total: total,
        payment: payment,
        change: change,
        transaction_date: new Date().toISOString()
    };

    let receiptHTML = `
        <div class="text-center mb-3">
            ${appSettings.receipt_header ? `<div><small>${appSettings.receipt_header}</small></div>` : ''}
            <h6>${appSettings.store_name || 'Toko ABC'}</h6>
            ${appSettings.store_address ? `<small>${appSettings.store_address}</small><br>` : ''}
            ${appSettings.store_phone ? `<small>Tel: ${appSettings.store_phone}</small><br>` : ''}
            ${appSettings.store_email ? `<small>Email: ${appSettings.store_email}</small><br>` : ''}
            ${appSettings.store_website ? `<small>Web: ${appSettings.store_website}</small><br>` : ''}
            ${appSettings.store_social_media ? `<small>Social: ${appSettings.store_social_media}</small><br>` : ''}
        </div>
        <div class="border-top border-bottom py-2 mb-2">
            <div class="row">
                <div class="col-6"><strong>Transaksi #${transactionId}</strong></div>
                <div class="col-6 text-end"><small>${new Date().toLocaleString('id-ID')}</small></div>
            </div>
        </div>
        <table class="table table-sm">
    `;

    cartItems.forEach(item => {
        receiptHTML += `
            <tr>
                <td>${item.name}</td>
                <td class="text-end">${item.quantity} x ${formatNumber(item.price)}</td>
                <td class="text-end">${formatNumber(item.subtotal)}</td>
            </tr>
        `;
    });

    const subtotal = cartItems.reduce((sum, item) => sum + item.subtotal, 0);
    const taxEnabled = appSettings.tax_enabled === true || appSettings.tax_enabled === 1;
    const taxRate = parseFloat(appSettings.tax_rate) || 0;
    const taxAmount = taxEnabled && taxRate > 0 ? subtotal * (taxRate / 100) : 0;

    receiptHTML += `
        </table>
        <div class="border-top pt-2">
            <div class="row">
                <div class="col-6">Subtotal:</div>
                <div class="col-6 text-end">${appSettings.currency || 'Rp'} ${formatNumber(subtotal)}</div>
            </div>
    `;

    if (taxEnabled && taxAmount > 0) {
        receiptHTML += `
            <div class="row">
                <div class="col-6">Pajak (${taxRate}%):</div>
                <div class="col-6 text-end">${appSettings.currency || 'Rp'} ${formatNumber(taxAmount)}</div>
            </div>
        `;
    }

    receiptHTML += `
            <div class="row">
                <div class="col-6"><strong>Total:</strong></div>
                <div class="col-6 text-end"><strong>${appSettings.currency || 'Rp'} ${formatNumber(total)}</strong></div>
            </div>
            <div class="row">
                <div class="col-6">Bayar:</div>
                <div class="col-6 text-end">${appSettings.currency || 'Rp'} ${formatNumber(payment)}</div>
            </div>
            <div class="row">
                <div class="col-6">Kembalian:</div>
                <div class="col-6 text-end">${appSettings.currency || 'Rp'} ${formatNumber(change)}</div>
            </div>
        </div>
        <div class="text-center mt-3">
            <small>${appSettings.receipt_footer || 'Terima kasih atas kunjungan Anda'}</small>
        </div>
    `;

    // Show receipt in modal
    const receiptModal = new bootstrap.Modal(document.getElementById('transactionModal'));
    document.getElementById('transaction-detail').innerHTML = receiptHTML;
    receiptModal.show();
}

// Transaction functions
async function loadTransactions() {
    try {
        transactions = await apiRequest('api/transactions.php');
        displayTransactions();
    } catch (error) {
        console.error('Error loading transactions:', error);
        showAlert('Gagal memuat data transaksi', 'danger');
    }
}

function displayTransactions() {
    const tbody = document.getElementById('transactions-tbody');
    if (!tbody) return;

    // Destroy existing DataTable if it exists
    if ($.fn.DataTable.isDataTable('#transactions-table')) {
        $('#transactions-table').DataTable().destroy();
    }

    tbody.innerHTML = '';

    if (!Array.isArray(transactions) || transactions.length === 0) {
        tbody.innerHTML = '<tr><td colspan="4" class="text-center">Tidak ada data transaksi</td></tr>';
        return;
    }

    transactions.forEach(transaction => {
        const row = document.createElement('tr');
        row.innerHTML = `
            <td>#${transaction.id}</td>
            <td>${new Date(transaction.transaction_date).toLocaleString('id-ID')}</td>
            <td>${appSettings.currency || 'Rp'} ${formatNumber(transaction.total)}</td>
            <td>
                <button class="btn btn-info btn-sm" onclick="viewTransactionDetail(${transaction.id})">
                    <i class="fas fa-eye"></i> Detail
                </button>
            </td>
        `;
        tbody.appendChild(row);
    });

    // Initialize DataTable
    setTimeout(() => {
        $('#transactions-table').DataTable({
            language: {
                url: 'https://cdn.datatables.net/plug-ins/1.13.4/i18n/id.json'
            },
            dom: 'Bfrtip',
            buttons: [
                'copy', 'csv', 'excel', 'pdf', 'print'
            ],
            pageLength: 25,
            responsive: true,
            order: [[0, 'desc']]
        });
    }, 100);
}

async function viewTransactionDetail(transactionId) {
    try {
        const transaction = await apiRequest(`api/transactions.php?id=${transactionId}`);

        if (!transaction) {
            showAlert('Transaksi tidak ditemukan', 'danger');
            return;
        }

        let tableHTML = `
            <div class="mb-3">
                <h6>Transaksi #${transaction.id}</h6>
                <p class="text-muted">${new Date(transaction.transaction_date).toLocaleString('id-ID')}</p>
            </div>
            <table class="table table-dark">
                <thead>
                    <tr>
                        <th>Produk</th>
                        <th>Qty</th>
                        <th>Harga</th>
                        <th>Subtotal</th>
                    </tr>
                </thead>
                <tbody>
        `;

        transaction.items.forEach(item => {
            tableHTML += `
                <tr>
                    <td>${item.product_name}</td>
                    <td>${item.quantity}</td>
                    <td>${appSettings.currency || 'Rp'} ${formatNumber(item.price)}</td>
                    <td>${appSettings.currency || 'Rp'} ${formatNumber(item.subtotal)}</td>
                </tr>
            `;
        });

        tableHTML += `
                </tbody>
            </table>
            <div class="border-top pt-2">
                <div class="row">
                    <div class="col-6"><strong>Total:</strong></div>
                    <div class="col-6 text-end"><strong>${appSettings.currency || 'Rp'} ${formatNumber(transaction.total)}</strong></div>
                </div>
            </div>
            <div class="mt-3">
                <button class="btn btn-primary" onclick="printTransactionReceipt(${transactionId})">
                    <i class="fas fa-print"></i> Cetak Struk
                </button>
            </div>
        `;

        // Show modal with table data
        document.getElementById('transaction-detail').innerHTML = tableHTML;
        new bootstrap.Modal(document.getElementById('transactionModal')).show();

    } catch (error) {
        console.error('Error viewing transaction detail:', error);
        showAlert('Gagal mengambil detail transaksi', 'danger');
    }
}

async function printTransactionReceipt(transactionId) {
    try {
        const transaction = await apiRequest(`api/transactions.php?id=${transactionId}`);

        if (!transaction) {
            showAlert('Transaksi tidak ditemukan', 'danger');
            return;
        }

        const receiptHTML = generateReceiptHTML(transaction.items, transaction);

        // Create print window
        const printWindow = window.open('', '_blank');
        printWindow.document.write(`
            <!DOCTYPE html>
            <html>
            <head>
                <title>Struk Transaksi #${transaction.id}</title>
                <style>
                    body { 
                        font-family: 'Courier New', monospace; 
                        font-size: 12px; 
                        line-height: 1.4; 
                        max-width: 300px; 
                        margin: 0 auto; 
                        padding: 10px;
                        background: white;
                        color: black;
                    }
                    .text-center { text-align: center; }
                    .text-left { text-align: left; }
                    .text-right { text-align: right; }
                    .separator { border-top: 1px dashed #000; margin: 8px 0; }
                    .receipt-header { margin-bottom: 15px; }
                    .receipt-body { margin: 15px 0; }
                    .receipt-footer { margin-top: 15px; }
                    table { width: 100%; border-collapse: collapse; }
                    td { padding: 2px 0; }
                    .item-line { display: flex; justify-content: space-between; }
                    .bold { font-weight: bold; }
                    @media print {
                        body { margin: 0; padding: 5px; }
                    }
                </style>
            </head>
            <body>
                ${receiptHTML}
                <script>
                    window.onload = function() {
                        window.print();
                        window.onafterprint = function() {
                            window.close();
                        }
                    }
                </script>
            </body>
            </html>
        `);
        printWindow.document.close();

    } catch (error) {
        console.error('Error printing receipt:', error);
        showAlert('Gagal mencetak struk', 'danger');
    }
}

function generateReceiptHTML(cartItems, transactionData = null) {
    const currentDate = transactionData ? new Date(transactionData.transaction_date) : new Date();

    let receiptHTML = `
        <div class="text-center receipt-header">
            ${appSettings.receipt_header ? `<div class="bold">${appSettings.receipt_header}</div>` : ''}
            <div class="bold" style="font-size: 14px;">${appSettings.store_name || 'Toko ABC'}</div>
            <div>${appSettings.store_address || 'Alamat Toko'}</div>
            <div>Tel: ${appSettings.store_phone || '021-12345678'}</div>
            ${appSettings.store_email ? `<div>Email: ${appSettings.store_email}</div>` : ''}
            ${appSettings.store_website ? `<div>Web: ${appSettings.store_website}</div>` : ''}
            ${appSettings.store_social_media ? `<div>Social: ${appSettings.store_social_media}</div>` : ''}
        </div>

        <div class="separator"></div>

        <div class="receipt-body">
            <div style="display: flex; justify-content: space-between;">
                <span>Tanggal:</span>
                <span>${currentDate.toLocaleString('id-ID')}</span>
            </div>
            ${transactionData ? `<div style="display: flex; justify-content: space-between;">
                <span>No. Transaksi:</span>
                <span>#${transactionData.id}</span>
            </div>` : ''}
        </div>

        <div class="separator"></div>`;

    // Items list
    cartItems.forEach(item => {
        const itemName = item.product_name || item.name || 'Unknown';
        const price = item.price;
        const qty = item.quantity;
        const subtotal = item.subtotal;

        receiptHTML += `
            <div style="margin: 5px 0;">
                <div>${itemName}</div>
                <div style="display: flex; justify-content: space-between;">
                    <span>${qty} x ${appSettings.currency || 'Rp'} ${formatNumber(price)}</span>
                    <span>${appSettings.currency || 'Rp'} ${formatNumber(subtotal)}</span>
                </div>
            </div>
        `;
    });

    const subtotal = cartItems.reduce((sum, item) => sum + item.subtotal, 0);
    const taxEnabled = appSettings.tax_enabled === true || appSettings.tax_enabled === 1;
    const taxRate = parseFloat(appSettings.tax_rate) || 0;
    const taxAmount = taxEnabled && taxRate > 0 ? subtotal * (taxRate / 100) : 0;

    receiptHTML += `
        <div class="separator"></div>

        <div style="margin: 10px 0;">
            <div style="display: flex; justify-content: space-between;">
                <span>Subtotal:</span>
                <span>${appSettings.currency || 'Rp'} ${formatNumber(subtotal)}</span>
            </div>
    `;

    if (taxEnabled && taxAmount > 0) {
        receiptHTML += `
            <div style="display: flex; justify-content: space-between;">
                <span>Pajak (${taxRate}%):</span>
                <span>${appSettings.currency || 'Rp'} ${formatNumber(taxAmount)}</span>
            </div>
        `;
    }

    const total = transactionData ? transactionData.total : (subtotal + taxAmount);

    receiptHTML += `
            <div style="display: flex; justify-content: space-between; font-weight: bold; font-size: 14px; margin-top: 5px; padding-top: 5px; border-top: 1px solid #000;">
                <span>TOTAL:</span>
                <span>${appSettings.currency || 'Rp'} ${formatNumber(total)}</span>
            </div>
        </div>

        <div class="separator"></div>

        <div class="text-center receipt-footer">
            <div style="margin: 10px 0;">
                ${appSettings.receipt_footer || 'Terima kasih atas kunjungan Anda'}
            </div>
            <div style="font-size: 10px;">
                Powered by ${appSettings.app_name || 'Kasir Digital'}
            </div>
        </div>
    `;

    return receiptHTML;
}

// Inventory functions
async function loadInventory() {
    try {
        inventory = await apiRequest('api/inventory.php');
        displayInventoryData(inventory);
    } catch (error) {
        console.error('Error loading inventory:', error);
        showAlert('Gagal memuat data inventory', 'danger');
    }
}

function displayInventoryData(inventoryData) {
    const tbody = document.getElementById('inventory-tbody');
    if (!tbody) return;

    // Destroy existing DataTable if it exists
    if ($.fn.DataTable.isDataTable('#inventory-table')) {
        $('#inventory-table').DataTable().destroy();
    }

    tbody.innerHTML = '';

    if (!Array.isArray(inventoryData) || inventoryData.length === 0) {
        tbody.innerHTML = '<tr><td colspan="6" class="text-center">Tidak ada data inventory</td></tr>';
        return;
    }

    inventoryData.forEach(item => {
        const row = document.createElement('tr');
        row.innerHTML = `
            <td>${new Date(item.created_at).toLocaleDateString('id-ID')}</td>
            <td>${item.product_name || ''}</td>
            <td>${item.quantity || 0}</td>
            <td>${item.stock_before || 0}</td>
            <td>${item.stock_after || 0}</td>
            <td>${item.notes || '-'}</td>
        `;
        tbody.appendChild(row);
    });

    // Reinitialize DataTable
    setTimeout(() => {
        $('#inventory-table').DataTable({
            language: {
                url: 'https://cdn.datatables.net/plug-ins/1.13.4/i18n/id.json'
            },
            dom: 'Bfrtip',
            buttons: [
                'copy', 'csv', 'excel', 'pdf', 'print'
            ],
            pageLength: 25,
            responsive: true,
            order: [[0, 'desc']]
        });
    }, 100);
}

function showAddStockModal() {
    document.getElementById('stockForm').reset();
    populateStockProductSelect();
    new bootstrap.Modal(document.getElementById('stockModal')).show();
}

function populateStockProductSelect() {
    const select = document.getElementById('stock-product');
    if (!select) return;

    select.innerHTML = '<option value="">-- Pilih Produk --</option>';

    products.forEach(product => {
        const option = document.createElement('option');
        option.value = product.id;
        option.textContent = `${product.name} (Stok: ${product.stock})`;
        select.appendChild(option);
    });
}

function updateMarginLabel() {
    const marginType = document.getElementById('margin-type').value;
    const marginLabel = document.getElementById('margin-label');

    if (marginLabel) {
        marginLabel.textContent = marginType === 'percentage' ? 'Margin (%)' : 'Margin (Rp)';
    }

    calculateSellingPrice();
}

function calculateSellingPrice() {
    const purchasePrice = parseFloat(document.getElementById('purchase-price').value) || 0;
    const marginType = document.getElementById('margin-type').value;
    const marginValue = parseFloat(document.getElementById('margin-value').value) || 0;

    let sellingPrice = purchasePrice;

    if (marginValue > 0 && purchasePrice > 0) {
        if (marginType === 'percentage') {
            sellingPrice = purchasePrice + (purchasePrice * marginValue / 100);
        } else {
            sellingPrice = purchasePrice + marginValue;
        }
    }

    const display = document.getElementById('selling-price-display');
    if (display) {
        display.value = `${appSettings.currency || 'Rp'} ${formatNumber(sellingPrice)}`;
    }
}

async function addStock() {
    try {
        const productId = document.getElementById('stock-product').value;
        const quantity = parseInt(document.getElementById('stock-quantity').value);
        const purchasePrice = parseFloat(document.getElementById('purchase-price').value);
        const marginType = document.getElementById('margin-type').value;
        const marginValue = parseFloat(document.getElementById('margin-value').value) || 0;
        const notes = document.getElementById('stock-notes').value.trim();

        if (!productId || !quantity || !purchasePrice) {
            showAlert('Semua field wajib harus diisi!', 'warning');
            return;
        }

        if (quantity <= 0 || purchasePrice <= 0) {
            showAlert('Quantity dan harga harus lebih dari 0!', 'warning');
            return;
        }

        const stockData = {
            product_id: parseInt(productId),
            quantity: quantity,
            purchase_price: purchasePrice,
            margin_type: marginType,
            margin_value: marginValue,
            notes: notes
        };

        const result = await apiRequest('api/inventory.php', {
            method: 'POST',
            body: JSON.stringify(stockData)
        });

        if (result.success) {
            showAlert('Stok berhasil ditambahkan!', 'success');
            bootstrap.Modal.getInstance(document.getElementById('stockModal')).hide();
            loadInventory();
            loadProducts(); // Reload products to update stock
        } else {
            showAlert('Gagal menambah stok: ' + result.error, 'danger');
        }
    } catch (error) {
        console.error('Error adding stock:', error);
        showAlert('Gagal menambah stok: ' + error.message, 'danger');
    }
}

// User management functions (admin only)
async function loadUsers() {
    try {
        users = await apiRequest('api/users.php');
        displayUsers();
    } catch (error) {
        console.error('Error loading users:', error);
        showAlert('Gagal memuat data user', 'danger');
    }
}

function displayUsers() {
    const tbody = document.getElementById('users-tbody');
    if (!tbody) return;

    // Destroy existing DataTable if it exists
    if ($.fn.DataTable.isDataTable('#users-table')) {
        $('#users-table').DataTable().destroy();
    }

    tbody.innerHTML = '';

    if (!Array.isArray(users) || users.length === 0) {
        tbody.innerHTML = '<tr><td colspan="5" class="text-center">Tidak ada data user</td></tr>';
        return;
    }

    users.forEach(user => {
        const row = document.createElement('tr');
        row.innerHTML = `
            <td>${user.username}</td>
            <td>${user.name}</td>
            <td><span class="badge bg-${user.role === 'admin' ? 'primary' : 'secondary'}">${user.role}</span></td>
            <td>${new Date(user.created_at).toLocaleDateString('id-ID')}</td>
            <td>
                <button class="btn btn-warning btn-sm me-1" onclick="editUser(${user.id})">
                    <i class="fas fa-edit"></i>
                </button>
                <button class="btn btn-danger btn-sm" onclick="deleteUser(${user.id})">
                    <i class="fas fa-trash"></i>
                </button>
            </td>
        `;
        tbody.appendChild(row);
    });

    // Initialize DataTable
    setTimeout(() => {
        $('#users-table').DataTable({
            language: {
                url: 'https://cdn.datatables.net/plug-ins/1.13.4/i18n/id.json'
            },
            pageLength: 25,
            responsive: true
        });
    }, 100);
}

function showAddUserModal() {
    document.getElementById('userModalTitle').textContent = 'Tambah User';
    document.getElementById('userForm').reset();
    document.getElementById('user-id').value = '';
    new bootstrap.Modal(document.getElementById('userModal')).show();
}

function editUser(id) {
    const user = users.find(u => u.id === id);
    if (!user) return;

    document.getElementById('userModalTitle').textContent = 'Edit User';
    document.getElementById('user-id').value = user.id;
    document.getElementById('user-username').value = user.username;
    document.getElementById('user-name').value = user.name;
    document.getElementById('user-password').value = '';
    document.getElementById('user-role').value = user.role;

    new bootstrap.Modal(document.getElementById('userModal')).show();
}

async function saveUser() {
    try {
        const id = document.getElementById('user-id').value;
        const userData = {
            username: document.getElementById('user-username').value.trim(),
            name: document.getElementById('user-name').value.trim(),
            password: document.getElementById('user-password').value,
            role: document.getElementById('user-role').value
        };

        if (!userData.username || !userData.name || !userData.role) {
            showAlert('Username, nama, dan role harus diisi!', 'warning');
            return;
        }

        if (!id && !userData.password) {
            showAlert('Password harus diisi untuk user baru!', 'warning');
            return;
        }

        let result;
        if (id) {
            userData.id = parseInt(id);
            result = await apiRequest('api/users.php', {
                method: 'PUT',
                body: JSON.stringify(userData)
            });
        } else {
            result = await apiRequest('api/users.php', {
                method: 'POST',
                body: JSON.stringify(userData)
            });
        }

        if (result.success) {
            showAlert(result.message, 'success');
            bootstrap.Modal.getInstance(document.getElementById('userModal')).hide();
            loadUsers();
        } else {
            showAlert(result.error, 'danger');
        }
    } catch (error) {
        console.error('Error saving user:', error);
        showAlert('Gagal menyimpan user: ' + error.message, 'danger');
    }
}

async function deleteUser(id) {
    if (!confirm('Apakah Anda yakin ingin menghapus user ini?')) return;

    try {
        const result = await apiRequest(`api/users.php?id=${id}`, {
            method: 'DELETE'
        });

        if (result.success) {
            showAlert(result.message, 'success');
            loadUsers();
        } else {
            showAlert(result.error, 'danger');
        }
    } catch (error) {
        console.error('Error deleting user:', error);
        showAlert('Gagal menghapus user: ' + error.message, 'danger');
    }
}

// Print current receipt (for kasir modal)
function printCurrentReceipt() {
    if (window.currentReceiptData) {
        const receiptHTML = generateReceiptHTML(window.currentReceiptData.items, window.currentReceiptData);

        // Create print window
        const printWindow = window.open('', '_blank');
        printWindow.document.write(`
            <!DOCTYPE html>
            <html>
            <head>
                <title>Struk Transaksi #${window.currentReceiptData.id}</title>
                <style>
                    body { 
                        font-family: 'Courier New', monospace; 
                        font-size: 12px; 
                        line-height: 1.4; 
                        max-width: 300px; 
                        margin: 0 auto; 
                        padding: 10px;
                        background: white;
                        color: black;
                    }
                    .text-center { text-align: center; }
                    .text-left { text-align: left; }
                    .text-right { text-align: right; }
                    .separator { border-top: 1px dashed #000; margin: 8px 0; }
                    .receipt-header { margin-bottom: 15px; }
                    .receipt-body { margin: 15px 0; }
                    .receipt-footer { margin-top: 15px; }
                    table { width: 100%; border-collapse: collapse; }
                    td { padding: 2px 0; }
                    .item-line { display: flex; justify-content: space-between; }
                    .bold { font-weight: bold; }
                    @media print {
                        body { margin: 0; padding: 5px; }
                    }
                </style>
            </head>
            <body>
                ${receiptHTML}
                <script>
                    window.onload = function() {
                        window.print();
                        window.onafterprint = function() {
                            window.close();
                        }
                    }
                </script>
            </body>
            </html>
        `);
        printWindow.document.close();
    } else {
        showAlert('Data struk tidak tersedia', 'warning');
    }
}

// Utility functions
function formatNumber(number) {
    return new Intl.NumberFormat('id-ID').format(number);
}

// Legacy print function - redirects to current receipt
function printReceipt() {
    printCurrentReceipt();
}

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

// Event listeners
document.addEventListener('DOMContentLoaded', function() {
    console.log('Page loaded, initializing...');

    // Apply saved theme
    const savedTheme = localStorage.getItem('theme') || 'dark';
    applyTheme(savedTheme);

    // Load basic settings first for all users
    loadBasicSettings().then(() => {
        // Load dashboard data
        loadDashboardStats();
    }).catch(error => {
        console.error('Failed to load basic settings:', error);
        // Load dashboard anyway
        loadDashboardStats();
    });

    // Add event listeners
    const searchProduct = document.getElementById('search-product');
    if (searchProduct) {
        searchProduct.addEventListener('input', searchProducts);
        searchProduct.addEventListener('keypress', function(e) {
            if (e.key === 'Enter') {
                e.preventDefault();
                // Check if it's a barcode (numeric and length > 5)
                const searchTerm = this.value.trim();
                if (/^\d+$/.test(searchTerm) && searchTerm.length >= 5) {
                    // Search product by barcode and auto-add
                    searchProductByBarcode(searchTerm);
                } else {
                    // Manual add to cart
                    addToCart();
                }
            }
        });
    }

    const paymentAmount = document.getElementById('payment-amount');
    if (paymentAmount) {
        paymentAmount.addEventListener('input', calculateChange);
    }

    const purchasePrice = document.getElementById('purchase-price');
    if (purchasePrice) {
        purchasePrice.addEventListener('input', calculateSellingPrice);
    }

    const marginValue = document.getElementById('margin-value');
    if (marginValue) {
        marginValue.addEventListener('input', calculateSellingPrice);
    }

    // Close mobile menu when nav link is clicked
    const navLinks = document.querySelectorAll('.nav-link');
    navLinks.forEach(link => {
        link.addEventListener('click', closeMobileMenu);
    });

    console.log('Initialization complete');
});