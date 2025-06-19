// Global variables
let products = [];
let cart = [];
let selectedProduct = null;

// Initialize application
document.addEventListener('DOMContentLoaded', function() {
    console.log('DOM loaded, initializing app...');
    loadDashboardStats();
    loadProducts();

    // Add event listeners
    const searchProduct = document.getElementById('search-product');
    const paymentAmount = document.getElementById('payment-amount');
    const purchasePrice = document.getElementById('purchase-price');
    const marginValue = document.getElementById('margin-value');

    if (searchProduct) {
        searchProduct.addEventListener('input', debounce(searchProducts, 300));
        searchProduct.addEventListener('keypress', handleSearchKeyPress);
    }
    if (paymentAmount) {
        paymentAmount.addEventListener('input', calculateChange);
    }
    if (purchasePrice) {
        purchasePrice.addEventListener('input', calculateSellingPrice);
    }
    if (marginValue) {
        marginValue.addEventListener('input', calculateSellingPrice);
    }

    // Initialize DataTables after a short delay to ensure DOM is ready
    setTimeout(initializeDataTables, 1000);
});

// Initialize DataTables for all tables
function initializeDataTables() {
    try {
        console.log('DataTables will be initialized when data is loaded');
    } catch (error) {
        console.error('Error in DataTables initialization:', error);
    }
}

// Users Management Functions
async function loadUsers() {
    try {
        console.log('Loading users...');
        const users = await apiRequest('api/users.php');

        if (Array.isArray(users)) {
            displayUsers(users);
            console.log('Users loaded successfully');
        } else if (users.success === false) {
            throw new Error(users.error || 'Unknown error from server');
        } else {
            throw new Error('Unexpected response format');
        }
    } catch (error) {
        console.error('Error loading users:', error);
        showAlert('Error loading users: ' + error.message, 'danger');
    }
}

function displayUsers(users) {
    const tbody = document.getElementById('users-tbody');
    if (!tbody) return;

    // Destroy existing DataTable if it exists
    if ($.fn.DataTable.isDataTable('#users-table')) {
        $('#users-table').DataTable().destroy();
    }

    tbody.innerHTML = '';

    if (!Array.isArray(users) || users.length === 0) {
        tbody.innerHTML = '<tr><td colspan="5" class="text-center">Tidak ada pengguna</td></tr>';
        return;
    }

    users.forEach(user => {
        const row = document.createElement('tr');
        row.innerHTML = `
            <td>${user.username}</td>
            <td>${user.name}</td>
            <td><span class="badge bg-${user.role === 'admin' ? 'danger' : 'info'}">${user.role.toUpperCase()}</span></td>
            <td>${new Date(user.created_at).toLocaleDateString('id-ID')}</td>
            <td>
                <button class="btn btn-warning btn-sm me-1" onclick="editUser(${user.id})">
                    <i class="fas fa-edit"></i> <span class="d-none d-sm-inline">Edit</span>
                </button>
                <button class="btn btn-danger btn-sm" onclick="deleteUser(${user.id})">
                    <i class="fas fa-trash"></i> <span class="d-none d-sm-inline">Hapus</span>
                </button>
            </td>
        `;
        tbody.appendChild(row);
    });

    // Initialize DataTable
    try {
        $('#users-table').DataTable({
            language: {
                url: 'https://cdn.datatables.net/plug-ins/1.13.4/i18n/id.json'
            },
            dom: 'Bfrtip',
            buttons: [
                'copy', 'csv', 'excel', 'pdf', 'print'
            ],
            pageLength: 25,
            responsive: true,
            order: [[3, 'desc']]
        });
    } catch (error) {
        console.error('Error initializing users DataTable:', error);
    }
}

function showAddUserModal() {
    document.getElementById('userModalTitle').textContent = 'Tambah User';
    document.getElementById('userForm').reset();
    document.getElementById('user-id').value = '';
    const modal = new bootstrap.Modal(document.getElementById('userModal'));
    modal.show();
}

async function editUser(id) {
    try {
        const users = await apiRequest('api/users.php');
        const user = users.find(u => u.id == id);

        if (user) {
            document.getElementById('userModalTitle').textContent = 'Edit User';
            document.getElementById('user-id').value = user.id;
            document.getElementById('user-username').value = user.username;
            document.getElementById('user-name').value = user.name;
            document.getElementById('user-password').value = '';
            document.getElementById('user-role').value = user.role;

            const modal = new bootstrap.Modal(document.getElementById('userModal'));
            modal.show();
        }
    } catch (error) {
        console.error('Error loading user for edit:', error);
        showAlert('Error loading user data: ' + error.message, 'danger');
    }
}

async function saveUser() {
    try {
        const id = document.getElementById('user-id').value;
        const userData = {
            username: document.getElementById('user-username').value,
            name: document.getElementById('user-name').value,
            password: document.getElementById('user-password').value,
            role: document.getElementById('user-role').value
        };

        let response;
        if (id) {
            userData.id = id;
            response = await apiRequest('api/users.php', {
                method: 'PUT',
                body: JSON.stringify(userData)
            });
        } else {
            response = await apiRequest('api/users.php', {
                method: 'POST',
                body: JSON.stringify(userData)
            });
        }

        if (response.success) {
            showAlert(response.message, 'success');
            bootstrap.Modal.getInstance(document.getElementById('userModal')).hide();
            loadUsers();
        } else {
            throw new Error(response.error || 'Unknown error');
        }
    } catch (error) {
        console.error('Error saving user:', error);
        showAlert('Error saving user: ' + error.message, 'danger');
    }
}

async function deleteUser(id) {
    if (confirm('Apakah Anda yakin ingin menghapus user ini?')) {
        try {
            const response = await apiRequest(`api/users.php?id=${id}`, {
                method: 'DELETE'
            });

            if (response.success) {
                showAlert(response.message, 'success');
                loadUsers();
            } else {
                throw new Error(response.error || 'Unknown error');
            }
        } catch (error) {
            console.error('Error deleting user:', error);
            showAlert('Error deleting user: ' + error.message, 'danger');
        }
    }
}

// Debounce function to limit API calls
function debounce(func, wait) {
    let timeout;
    return function executedFunction(...args) {
        const later = () => {
            clearTimeout(timeout);
            func(...args);
        };
        clearTimeout(timeout);
        timeout = setTimeout(later, wait);
    };
}

// Page Navigation
function showPage(pageId, element) {
    console.log('Showing page:', pageId);

    // Hide all pages
    document.querySelectorAll('.page-content').forEach(page => {
        page.classList.add('d-none');
    });

    // Show selected page
    const targetPage = document.getElementById(pageId);
    if (targetPage) {
        targetPage.classList.remove('d-none');
    }

    // Update navigation
    document.querySelectorAll('.nav-link').forEach(link => {
        link.classList.remove('active');
    });
    if (element) {
        element.classList.add('active');
    }

    // Load page-specific data
    switch(pageId) {
        case 'dashboard':
            loadDashboardStats();
            break;
        case 'products':
            loadProducts();
            break;
        case 'cashier':
            loadProducts();
            break;
        case 'transactions':
            loadTransactions();
            break;
        case 'inventory':
            loadInventoryData();
            break;
        case 'users':
            loadUsers();
            break;
        case 'settings':
            loadSettings();
            break;
    }
}

// API Helper function
async function apiRequest(url, options = {}) {
    const defaultOptions = {
        headers: {
            'Content-Type': 'application/json'
        }
    };

    const finalOptions = { ...defaultOptions, ...options };

    try {
        const response = await fetch(url, finalOptions);

        if (!response.ok) {
            throw new Error(`HTTP ${response.status}: ${response.statusText}`);
        }

        const text = await response.text();

        if (!text.trim()) {
            throw new Error('Empty response from server');
        }

        try {
            return JSON.parse(text);
        } catch (jsonError) {
            console.error('JSON Parse Error:', jsonError);
            console.error('Response text:', text);
            throw new Error('Invalid JSON response from server');
        }
    } catch (error) {
        console.error('API Request Error:', error);
        throw error;
    }
}

// Dashboard Functions
async function loadDashboardStats() {
    try {
        console.log('Loading dashboard stats...');
        const stats = await apiRequest('api/transactions.php?stats=1');

        if (stats.success === false) {
            throw new Error(stats.error || 'Unknown error from server');
        }

        // Update basic stats
        const totalProducts = document.getElementById('total-products');
        const todayTransactions = document.getElementById('today-transactions');
        const todayRevenue = document.getElementById('today-revenue');
        const lowStock = document.getElementById('low-stock');
        const monthlyRevenue = document.getElementById('monthly-revenue');
        const monthlyTransactions = document.getElementById('monthly-transactions');
        const avgTransaction = document.getElementById('avg-transaction');
        const bestProduct = document.getElementById('best-product');

        if (totalProducts) totalProducts.textContent = stats.total_products || 0;
        if (todayTransactions) todayTransactions.textContent = stats.today_transactions || 0;
        if (todayRevenue) todayRevenue.textContent = 'Rp ' + formatNumber(stats.today_revenue || 0);
        if (lowStock) lowStock.textContent = stats.low_stock || 0;
        if (monthlyRevenue) monthlyRevenue.textContent = 'Rp ' + formatNumber(stats.monthly_revenue || 0);
        if (monthlyTransactions) monthlyTransactions.textContent = stats.monthly_transactions || 0;
        if (avgTransaction) avgTransaction.textContent = 'Rp ' + formatNumber(stats.avg_transaction || 0);
        if (bestProduct) bestProduct.textContent = stats.best_product || '-';

        // Load recent transactions
        loadRecentTransactions();

        // Load low stock products
        loadLowStockProducts();

        console.log('Dashboard stats loaded successfully');
    } catch (error) {
        console.error('Error loading stats:', error);
        showAlert('Error loading dashboard stats: ' + error.message, 'danger');
    }
}

// Load recent transactions for dashboard
async function loadRecentTransactions() {
    try {
        const transactions = await apiRequest('api/transactions.php?recent=5');
        const container = document.getElementById('recent-transactions');

        if (!container) return;

        if (!Array.isArray(transactions) || transactions.length === 0) {
            container.innerHTML = '<p class="text-center text-muted">Tidak ada transaksi terbaru</p>';
            return;
        }

        container.innerHTML = '';
        transactions.forEach(transaction => {
            const item = document.createElement('div');
            item.className = 'list-group-item list-group-item-action';
            item.innerHTML = `
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <h6 class="mb-1">Transaksi #${transaction.id}</h6>
                        <small class="text-muted">${new Date(transaction.transaction_date).toLocaleString('id-ID')}</small>
                    </div>
                    <span class="text-success">Rp ${formatNumber(transaction.total)}</span>
                </div>
            `;
            container.appendChild(item);
        });
    } catch (error) {
        console.error('Error loading recent transactions:', error);
        const container = document.getElementById('recent-transactions');
        if (container) {
            container.innerHTML = '<p class="text-center text-danger">Error memuat data</p>';
        }
    }
}

// Load low stock products for dashboard
async function loadLowStockProducts() {
    try {
        const products = await apiRequest('api/products.php?lowstock=5');
        const container = document.getElementById('low-stock-products');

        if (!container) return;

        if (!Array.isArray(products) || products.length === 0) {
            container.innerHTML = '<p class="text-center text-muted">Semua produk stok aman</p>';
            return;
        }

        container.innerHTML = '';
        products.forEach(product => {
            const item = document.createElement('div');
            item.className = 'list-group-item list-group-item-action';
            item.innerHTML = `
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <h6 class="mb-1 text-warning">${product.name}</h6>
                        <small class="text-muted">${product.category}</small>
                    </div>
                    <span class="badge bg-danger">${product.stock}</span>
                </div>
            `;
            container.appendChild(item);
        });
    } catch (error) {
        console.error('Error loading low stock products:', error);
        const container = document.getElementById('low-stock-products');
        if (container) {
            container.innerHTML = '<p class="text-center text-danger">Error memuat data</p>';
        }
    }
}

// Products Management
async function loadProducts() {
    try {
        console.log('Loading products...');
        const result = await apiRequest('api/products.php');

        if (Array.isArray(result)) {
            products = result;
            displayProducts();
            console.log('Products loaded successfully');
        } else if (result && result.success === false) {
            throw new Error(result.error || 'Unknown error from server');
        } else if (result === null || result === undefined) {
            // Handle empty response
            products = [];
            displayProducts();
            console.log('No products found');
        } else {
            throw new Error('Unexpected response format');
        }
    } catch (error) {
        console.error('Error loading products:', error);
        showAlert('Error loading products: ' + error.message, 'danger');
        products = []; // Set to empty array to prevent further errors
        displayProducts(); // Still display empty table
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
        tbody.innerHTML = '<tr><td colspan="6" class="text-center">Tidak ada produk</td></tr>';
        return;
    }

    products.forEach(product => {
        const row = document.createElement('tr');
        row.innerHTML = `
            <td>${product.barcode || ''}</td>
            <td>${product.name || ''}</td>
            <td>${product.category || ''}</td>
            <td>Rp ${formatNumber(product.price || 0)}</td>
            <td>${product.stock || 0}</td>
            <td>
                <button class="btn btn-warning btn-sm me-1 mb-1" onclick="editProduct(${product.id})">
                    <i class="fas fa-edit"></i>
                </button>
                <button class="btn btn-danger btn-sm mb-1" onclick="deleteProduct(${product.id})">
                    <i class="fas fa-trash"></i>
                </button>
            </td>
        `;
        tbody.appendChild(row);
    });

    // Reinitialize DataTable with proper column configuration
    setTimeout(() => {
        try {
            $('#products-table').DataTable({
                language: {
                    url: 'https://cdn.datatables.net/plug-ins/1.13.4/i18n/id.json'
                },
                dom: 'Bfrtip',
                buttons: [
                    'copy', 'csv', 'excel', 'pdf', 'print'
                ],
                pageLength: 25,
                responsive: true,
                order: [[1, 'asc']],
                columnDefs: [
                    { targets: [5], orderable: false } // Disable ordering for action column
                ],
                autoWidth: false
            });
        } catch (error) {
            console.error('Error initializing products DataTable:', error);
        }
    }, 100);
}

function displayTransactions(transactions) {
    const tbody = document.getElementById('transactions-tbody');
    if (!tbody) return;

    // Destroy existing DataTable if it exists
    if ($.fn.DataTable.isDataTable('#transactions-table')) {
        $('#transactions-table').DataTable().destroy();
    }

    tbody.innerHTML = '';

    if (!Array.isArray(transactions) || transactions.length === 0) {
        tbody.innerHTML = '<tr><td colspan="4" class="text-center">Tidak ada transaksi</td></tr>';
        return;
    }

    transactions.forEach(transaction => {
        const row = document.createElement('tr');
        row.innerHTML = `
            <td>${transaction.id}</td>
            <td>${new Date(transaction.transaction_date).toLocaleString('id-ID')}</td>
            <td>Rp ${formatNumber(transaction.total)}</td>
            <td>
                <button class="btn btn-info btn-sm me-1" onclick="viewTransactionDetail(${transaction.id})">
                    <i class="fas fa-eye"></i> <span class="d-none d-sm-inline">Detail</span>
                </button>
                <button class="btn btn-primary btn-sm" onclick="printTransactionReceipt(${transaction.id})">
                    <i class="fas fa-print"></i> <span class="d-none d-sm-inline">Cetak</span>
                </button>
            </td>
        `;
        tbody.appendChild(row);
    });

    // Reinitialize DataTable
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
            order: [[1, 'desc']]
        });
    }, 100);
}

function showAddProductModal() {
    const modalTitle = document.getElementById('productModalTitle');
    const productForm = document.getElementById('productForm');
    const productId = document.getElementById('product-id');

    if (modalTitle) modalTitle.textContent = 'Tambah Produk';
    if (productForm) productForm.reset();
    if (productId) productId.value = '';

    const modal = new bootstrap.Modal(document.getElementById('productModal'));
    modal.show();
}

function editProduct(id) {
    const product = products.find(p => p.id == id);
    if (!product) return;

    const modalTitle = document.getElementById('productModalTitle');
    const productId = document.getElementById('product-id');
    const productName = document.getElementById('product-name');
    const productCategory = document.getElementById('product-category');
    const productPrice = document.getElementById('product-price');
    const productStock = document.getElementById('product-stock');
    const productBarcode = document.getElementById('product-barcode');

    if (modalTitle) modalTitle.textContent = 'Edit Produk';
    if (productId) productId.value = product.id;
    if (productName) productName.value = product.name;
    if (productCategory) productCategory.value = product.category;
    if (productPrice) productPrice.value = product.price;
    if (productStock) productStock.value = product.stock;
    if (productBarcode) productBarcode.value = product.barcode;

    const modal = new bootstrap.Modal(document.getElementById('productModal'));
    modal.show();
}

async function saveProduct() {
    const id = document.getElementById('product-id').value;
    const name = document.getElementById('product-name').value.trim();
    const category = document.getElementById('product-category').value.trim();
    const price = document.getElementById('product-price').value;
    const stock = document.getElementById('product-stock').value;
    const barcode = document.getElementById('product-barcode').value.trim();

    if (!name || !category || !price || !stock || !barcode) {
        showAlert('Semua field harus diisi!', 'warning');
        return;
    }

    if (parseFloat(price) <= 0) {
        showAlert('Harga harus lebih dari 0!', 'warning');
        return;
    }

    if (parseInt(stock) < 0) {
        showAlert('Stok tidak boleh negatif!', 'warning');
        return;
    }

    const productData = {
        name: name,
        category: category,
        price: parseFloat(price),
        stock: parseInt(stock),
        barcode: barcode
    };

    if (id) {
        productData.id = parseInt(id);
    }

    try {
        console.log('Saving product:', productData);
        const result = await apiRequest('api/products.php', {
            method: id ? 'PUT' : 'POST',
            body: JSON.stringify(productData)
        });

        if (result.success) {
            loadProducts();
            const modal = bootstrap.Modal.getInstance(document.getElementById('productModal'));
            if (modal) modal.hide();
            showAlert(id ? 'Produk berhasil diupdate!' : 'Produk berhasil ditambahkan!', 'success');
        } else {
            showAlert('Error: ' + (result.error || 'Unknown error'), 'danger');
        }
    } catch (error) {
        console.error('Error saving product:', error);
        showAlert('Error saving product: ' + error.message, 'danger');
    }
}

async function deleteProduct(id) {
    if (confirm('Apakah Anda yakin ingin menghapus produk ini?')) {
        try {
            const result = await apiRequest(`api/products.php?id=${id}`, {
                method: 'DELETE'
            });

            if (result.success) {
                loadProducts();
                showAlert('Produk berhasil dihapus!', 'success');
            } else {
                showAlert('Error: ' + (result.error || 'Unknown error'), 'danger');
            }
        } catch (error) {
            console.error('Error deleting product:', error);
            showAlert('Error deleting product: ' + error.message, 'danger');
        }
    }
}

// Cashier Functions
async function searchProducts() {
    const searchTerm = document.getElementById('search-product').value;
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

    if (!Array.isArray(searchResults) || searchResults.length === 0) {
        return;
    }

    searchResults.forEach(product => {
        const item = document.createElement('div');
        item.className = 'list-group-item list-group-item-action';
        item.innerHTML = `
            <div class="d-flex justify-content-between align-items-center">
                <div>
                    <h6 class="mb-1">${product.name}</h6>
                    <small>Stok: ${product.stock} | Rp ${formatNumber(product.price)}</small>
                </div>
                <button class="btn btn-primary btn-sm" onclick="selectProduct(${product.id})">Pilih</button>
            </div>
        `;
        container.appendChild(item);
    });
}

function selectProduct(id) {
    selectedProduct = products.find(p => p.id == id);
    if (selectedProduct) {
        addToCartDirectly();
    }
}

function addToCart() {
    if (!selectedProduct) {
        showAlert('Pilih produk terlebih dahulu!', 'warning');
        return;
    }

    addToCartDirectly();
}

function addToCartDirectly() {
    if (!selectedProduct) return;

    const qtyInput = document.getElementById('product-qty');
    const qty = parseInt(qtyInput ? qtyInput.value : 1) || 1;

    if (qty > selectedProduct.stock) {
        showAlert('Stok tidak mencukupi!', 'warning');
        return;
    }

    const existingItem = cart.find(item => item.product_id === selectedProduct.id);

    if (existingItem) {
        const newQty = existingItem.quantity + qty;
        if (newQty > selectedProduct.stock) {
            showAlert('Stok tidak mencukupi!', 'warning');
            return;
        }
        existingItem.quantity = newQty;
        existingItem.subtotal = existingItem.quantity * existingItem.price;
    } else {
        cart.push({
            product_id: selectedProduct.id,
            name: selectedProduct.name,
            price: selectedProduct.price,
            quantity: qty,
            subtotal: selectedProduct.price * qty
        });
    }

    displayCart();

    const searchInput = document.getElementById('search-product');
    const qtyInputField = document.getElementById('product-qty');
    const suggestions = document.getElementById('product-suggestions');

    if (searchInput) searchInput.value = '';
    if (qtyInputField) qtyInputField.value = '1';
    if (suggestions) suggestions.innerHTML = '';
    selectedProduct = null;

    showAlert('Produk ditambahkan ke keranjang!', 'success');
}

// Handle search input with Enter key
function handleSearchKeyPress(event) {
    if (event.key === 'Enter') {
        event.preventDefault();
        quickAddToCart();
    }
}

// Quick add to cart function
async function quickAddToCart() {
    const searchTerm = document.getElementById('search-product').value.trim();
    if (!searchTerm) {
        showAlert('Masukkan nama produk atau barcode!', 'warning');
        return;
    }

    try {
        // Search for exact barcode match first
        let product = await apiRequest(`api/products.php?barcode=${encodeURIComponent(searchTerm)}`);

        // If no exact barcode match, search by name
        if (!product) {
            const searchResults = await apiRequest(`api/products.php?search=${encodeURIComponent(searchTerm)}`);
            if (Array.isArray(searchResults) && searchResults.length > 0) {
                product = searchResults[0]; // Take first result
            }
        }

        if (product && product.id) {
            selectedProduct = product;
            addToCartDirectly();

            // Clear suggestions
            const suggestions = document.getElementById('product-suggestions');
            if (suggestions) suggestions.innerHTML = '';
        } else {
            showAlert('Produk tidak ditemukan!', 'warning');
        }
    } catch (error) {
        console.error('Error in quick add:', error);
        showAlert('Error mencari produk: ' + error.message, 'danger');
    }
}

function displayCart() {
    const container = document.getElementById('cart-items');
    if (!container) return;

    container.innerHTML = '';

    if (cart.length === 0) {
        container.innerHTML = '<p class="text-center text-muted">Keranjang kosong</p>';
        updateCartTotal();
        return;
    }

    cart.forEach((item, index) => {
        const cartItem = document.createElement('div');
        cartItem.className = 'cart-item p-2 mb-2';
        cartItem.innerHTML = `
            <div class="d-flex justify-content-between align-items-center mb-2">
                <strong class="text-truncate me-2">${item.name}</strong>
                <button class="btn btn-danger btn-sm" onclick="removeFromCart(${index})">
                    <i class="fas fa-times"></i>
                </button>
            </div>
            <div class="d-flex justify-content-between align-items-center mb-1">
                <div class="d-flex align-items-center">
                    <button class="btn btn-outline-light btn-sm me-1" onclick="changeCartQuantity(${index}, -1)">
                        <i class="fas fa-minus"></i>
                    </button>
                    <input type="number" class="form-control form-control-sm text-center me-1" 
                           style="width: 60px;" value="${item.quantity}" 
                           onchange="updateCartQuantity(${index}, this.value)" min="1">
                    <button class="btn btn-outline-light btn-sm" onclick="changeCartQuantity(${index}, 1)">
                        <i class="fas fa-plus"></i>
                    </button>
                </div>
                <span>Rp ${formatNumber(item.price)}</span>
            </div>
            <div class="d-flex justify-content-between">
                <span>Subtotal:</span>
                <strong>Rp ${formatNumber(item.subtotal)}</strong>
            </div>
        `;
        container.appendChild(cartItem);
    });

    updateCartTotal();
}

function removeFromCart(index) {
    cart.splice(index, 1);
    displayCart();
    showAlert('Item dihapus dari keranjang!', 'info');
}

function changeCartQuantity(index, change) {
    const item = cart[index];
    const newQuantity = item.quantity + change;

    if (newQuantity <= 0) {
        removeFromCart(index);
        return;
    }

    // Check stock availability
    const product = products.find(p => p.id === item.product_id);
    if (product && newQuantity > product.stock) {
        showAlert('Stok tidak mencukupi!', 'warning');
        return;
    }

    item.quantity = newQuantity;
    item.subtotal = item.quantity * item.price;
    displayCart();
}

function updateCartQuantity(index, newQuantity) {
    const qty = parseInt(newQuantity);
    if (isNaN(qty) || qty <= 0) {
        removeFromCart(index);
        return;
    }

    const item = cart[index];

    // Check stock availability
    const product = products.find(p => p.id === item.product_id);
    if (product && qty > product.stock) {
        showAlert('Stok tidak mencukupi!', 'warning');
        // Reset to current quantity
        displayCart();
        return;
    }

    item.quantity = qty;
    item.subtotal = item.quantity * item.price;
    displayCart();
}

function updateCartTotal() {
    const subtotal = cart.reduce((sum, item) => sum + item.subtotal, 0);
    const taxEnabled = appSettings.tax_enabled || false;
    const taxRate = parseFloat(appSettings.tax_rate) || 0;
    
    let taxAmount = 0;
    if (taxEnabled && taxRate > 0) {
        taxAmount = subtotal * (taxRate / 100);
    }
    
    const total = subtotal + taxAmount;
    
    const cartSubtotal = document.getElementById('cart-subtotal');
    const cartTax = document.getElementById('cart-tax');
    const cartTotal = document.getElementById('cart-total');
    
    if (cartSubtotal) {
        cartSubtotal.textContent = `Rp ${formatNumber(subtotal)}`;
    }
    if (cartTax) {
        cartTax.textContent = `Rp ${formatNumber(taxAmount)}`;
        cartTax.parentNode.style.display = taxEnabled ? 'flex' : 'none';
    }
    if (cartTotal) {
        cartTotal.textContent = `Rp ${formatNumber(total)}`;
    }
    calculateChange();
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
        changeElement.textContent = `Rp ${formatNumber(Math.max(0, change))}`;
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

            // Reload inventory data if inventory page is currently visible
            if (!document.getElementById('inventory').classList.contains('d-none')) {
                loadInventoryData();
            }
        } else {
            showAlert('Error: ' + (result.error || 'Unknown error'), 'danger');
        }
    } catch (error) {
        console.error('Error processing transaction:', error);
        showAlert('Error processing transaction: ' + error.message, 'danger');
    }
}

function clearCart() {
    cart = [];
    displayCart();
    const paymentInput = document.getElementById('payment-amount');
    if (paymentInput) paymentInput.value = '';
    selectedProduct = null;
}

function printReceipt() {
    const transactionDetail = document.getElementById('transaction-detail').innerHTML;

    const printWindow = window.open('', '_blank');
    printWindow.document.write(`
        <html>
        <head>
            <title>Struk Transaksi</title>
            <style>
                body { 
                    font-family: 'Courier New', monospace; 
                    font-size: 12px; 
                    margin: 0; 
                    padding: 20px; 
                    background: white;
                    color: black;
                }
                .receipt { 
                    max-width: 300px; 
                    margin: 0 auto; 
                    background: white;
                    color: black;
                }
                .center { text-align: center; }
                .line { border-bottom: 1px dashed #000; margin: 10px 0; }
                table { width: 100%; border-collapse: collapse; }
                td { padding: 2px 0; vertical-align: top; }
                .right { text-align: right; }
                .bold { font-weight: bold; }
                .total-row { border-top: 1px solid #000; font-weight: bold; }
                .store-info { margin-bottom: 15px; }
                .thank-you { margin-top: 15px; font-size: 10px; }
                @media print {
                    body { margin: 0; padding: 10px; }
                    .receipt { max-width: none; }
                }
            </style>
        </head>
        <body>
            <div class="receipt">
                <div class="store-info center">
                    <div class="bold" style="font-size: 14px;">KASIR DIGITAL</div>
                    <div>Jl. Contoh No. 123</div>
                    <div>Telp: (021) 123-4567</div>
                </div>
                <div class="line"></div>
                ${transactionDetail}
                <div class="line"></div>
                <div class="thank-you center">
                    <div>Terima kasih atas kunjungan Anda!</div>
                    <div>Selamat berbelanja kembali</div>
                    <div style="margin-top: 10px;">Dicetak: ${new Date().toLocaleString('id-ID')}</div>
                </div>
            </div>
            <script>
                window.print();
                window.onafterprint = function() { window.close(); }
            </script>
        </body>
        </html>
    `);
    printWindow.document.close();
}

// Function to show receipt after transaction
function showReceipt(transactionId, cartItems, total, payment) {
    const change = payment - total;
    const now = new Date();
    
    // Ensure appSettings is available
    const settings = appSettings || {};
    const appName = settings.app_name || 'Kasir Digital';
    const storeName = settings.store_name || 'Toko ABC';
    
    const subtotal = cartItems.reduce((sum, item) => sum + item.subtotal, 0);
    const taxEnabled = settings.tax_enabled || false;
    const taxRate = parseFloat(settings.tax_rate) || 0;
    const taxAmount = taxEnabled && taxRate > 0 ? subtotal * (taxRate / 100) : 0;

    let receiptHTML = `
        <div class="transaction-info" style="margin-bottom: 15px;">
            <div style="display: flex; justify-content: space-between; margin-bottom: 5px;">
                <span>No. Transaksi:</span>
                <span><strong>#${transactionId}</strong></span>
            </div>
            <div style="display: flex; justify-content: space-between; margin-bottom: 5px;">
                <span>Tanggal:</span>
                <span>${now.toLocaleDateString('id-ID')}</span>
            </div>
            <div style="display: flex; justify-content: space-between;">
                <span>Waktu:</span>
                <span>${now.toLocaleTimeString('id-ID')}</span>
            </div>
        </div>

        <div class="items-section" style="margin-bottom: 15px;">
            <div style="font-weight: bold; margin-bottom: 10px; text-align: center; border-bottom: 1px dashed #666; padding-bottom: 5px;">DAFTAR BELANJA</div>
            <table style="width: 100%; border-collapse: collapse;">
    `;

    cartItems.forEach(item => {
        receiptHTML += `
            <tr>
                <td colspan="3" style="padding: 3px 0; font-weight: bold; border-bottom: 1px solid #333;">${item.name}</td>
            </tr>
            <tr style="font-size: 0.9em;">
                <td style="padding: 2px 0; width: 15%; text-align: center;">${item.quantity}</td>
                <td style="padding: 2px 0; width: 45%; text-align: center;">× Rp ${formatNumber(item.price)}</td>
                <td style="padding: 2px 0; width: 40%; text-align: right; font-weight: bold;">Rp ${formatNumber(item.subtotal)}</td>
            </tr>
        `;
    });

    receiptHTML += `
            </table>
        </div>

        <div class="totals-section" style="border-top: 1px dashed #666; padding-top: 10px;">
            <div style="display: flex; justify-content: space-between; margin-bottom: 5px;">
                <span>Subtotal:</span>
                <span>Rp ${formatNumber(subtotal)}</span>
            </div>
    `;
    
    if (taxEnabled && taxAmount > 0) {
        receiptHTML += `
            <div style="display: flex; justify-content: space-between; margin-bottom: 5px;">
                <span>Pajak (${taxRate}%):</span>
                <span>Rp ${formatNumber(taxAmount)}</span>
            </div>
        `;
    }
    
    receiptHTML += `
            <div style="display: flex; justify-content: space-between; margin-bottom: 10px; font-weight: bold; font-size: 1.1em; border-top: 1px solid #666; border-bottom: 1px solid #666; padding: 8px 0;">
                <span>TOTAL BELANJA:</span>
                <span>Rp ${formatNumber(total)}</span>
            </div>
            <div class="payment-info">
                <div style="display: flex; justify-content: space-between; margin-bottom: 5px;">
                    <span>Tunai:</span>
                    <span>Rp ${formatNumber(payment)}</span>
                </div>
                <div style="display: flex; justify-content: space-between; font-weight: bold;">
                    <span>Kembalian:</span>
                    <span>Rp ${formatNumber(change)}</span>
                </div>
            </div>
        </div>
    `;

    // Show receipt in modal first
    document.getElementById('transaction-detail').innerHTML = receiptHTML;
    const modal = new bootstrap.Modal(document.getElementById('transactionModal'));
    modal.show();

    // Auto print after 1 second
    setTimeout(() => {
        printReceipt();
    }, 1000);
}

// Transactions Functions
async function loadTransactions() {
    try {
        console.log('Loading transactions...');
        const transactions = await apiRequest('api/transactions.php');

        if (Array.isArray(transactions)) {
            displayTransactions(transactions);
            console.log('Transactions loaded successfully');
        } else if (transactions.success === false) {
            throw new Error(transactions.error || 'Unknown error from server');
        } else {
            throw new Error('Unexpected response format');
        }
    } catch (error) {
        console.error('Error loading transactions:', error);
        showAlert('Error loading transactions: ' + error.message, 'danger');
    }
}



async function viewTransactionDetail(id) {
    try {
        const transaction = await apiRequest(`api/transactions.php?id=${id}`);

        if (transaction && transaction.id) {
            const detailContent = `
                <div class="text-center mb-3">
                    <h5>Detail Transaksi #${transaction.id}</h5>
                    <p>Tanggal: ${new Date(transaction.transaction_date).toLocaleString('id-ID')}</p>
                </div>
                <div class="table-responsive">
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
                            ${transaction.items.map(item => `
                                <tr>
                                    <td>${item.product_name}</td>
                                    <td>${item.quantity}</td>
                                    <td>Rp ${formatNumber(item.price)}</td>
                                    <td>Rp ${formatNumber(item.subtotal)}</td>
                                </tr>
                            `).join('')}
                        </tbody>
                    </table>
                </div>
                <div class="text-end">
                    <h5>Total: Rp ${formatNumber(transaction.total)}</h5>
                </div>
            `;

            const transactionDetail = document.getElementById('transaction-detail');
            if (transactionDetail) {
                transactionDetail.innerHTML = detailContent;
                const modal = new bootstrap.Modal(document.getElementById('transactionModal'));
                modal.show();
            }
        } else {
            showAlert('Transaksi tidak ditemukan!', 'warning');
        }
    } catch (error) {
        console.error('Error loading transaction detail:', error);
        showAlert('Error loading transaction detail: ' + error.message, 'danger');
    }
}

function printReceipt() {
    const content = document.getElementById('transaction-detail').innerHTML;
    
    // Ensure appSettings is loaded, if not use defaults
    const settings = appSettings || {};
    const appName = settings.app_name || 'Kasir Digital';
    const storeName = settings.store_name || 'Toko ABC';
    const storeAddress = settings.store_address || 'Jl. Contoh No. 123, Kota, Provinsi';
    const storePhone = settings.store_phone || '021-12345678';
    const storeEmail = settings.store_email || '';
    const storeWebsite = settings.store_website || '';
    const storeSocialMedia = settings.store_social_media || '';
    const receiptFooter = settings.receipt_footer || 'Terima kasih atas kunjungan Anda!';
    const receiptHeader = settings.receipt_header || '';
    const currency = settings.currency || 'Rp';
    
    // Create display title for receipt
    const receiptTitle = storeName && storeName !== 'Toko ABC' ? `${appName} - ${storeName}` : appName;

    const printWindow = window.open('', '_blank');
    printWindow.document.write(`
        <html>
            <head>
                <title>Struk Pembayaran - ${storeName}</title>
                <style>
                    @page {
                        size: 80mm auto;
                        margin: 5mm;
                    }
                    * {
                        box-sizing: border-box;
                    }
                    body { 
                        font-family: 'Courier New', monospace;
                        margin: 0;
                        padding: 5mm;
                        font-size: 11pt;
                        line-height: 1.3;
                        color: #000;
                        background: #fff;
                        width: 80mm;
                    }
                    .receipt-header {
                        text-align: center;
                        border-bottom: 2px solid #000;
                        padding-bottom: 10px;
                        margin-bottom: 15px;
                    }
                    .store-name {
                        font-size: 16pt;
                        font-weight: bold;
                        margin-bottom: 5px;
                    }
                    .store-info {
                        font-size: 9pt;
                        margin-bottom: 3px;
                    }
                    .custom-header {
                        font-size: 10pt;
                        margin-bottom: 10px;
                        font-style: italic;
                    }
                    .transaction-info {
                        margin-bottom: 15px;
                        font-size: 10pt;
                    }
                    .items-section {
                        border-bottom: 1px dashed #000;
                        padding-bottom: 10px;
                        margin-bottom: 10px;
                    }
                    .item-line {
                        display: flex;
                        justify-content: space-between;
                        margin-bottom: 3px;
                        font-size: 10pt;
                    }
                    .item-name {
                        flex: 1;
                        text-align: left;
                    }
                    .item-qty-price {
                        margin: 2px 0;
                        font-size: 9pt;
                        color: #666;
                    }
                    .item-total {
                        text-align: right;
                        font-weight: bold;
                    }
                    .totals-section {
                        font-size: 11pt;
                    }
                    .total-line {
                        display: flex;
                        justify-content: space-between;
                        margin-bottom: 5px;
                        padding: 2px 0;
                    }
                    .grand-total {
                        border-top: 2px solid #000;
                        border-bottom: 2px solid #000;
                        font-weight: bold;
                        font-size: 12pt;
                        padding: 8px 0;
                        margin: 10px 0;
                    }
                    .payment-info {
                        margin: 10px 0;
                    }
                    .footer {
                        text-align: center;
                        margin-top: 20px;
                        font-size: 9pt;
                        border-top: 1px dashed #000;
                        padding-top: 10px;
                    }
                    .thank-you {
                        font-weight: bold;
                        margin: 10px 0;
                    }
                    @media print {
                        body { 
                            -webkit-print-color-adjust: exact;
                            print-color-adjust: exact;
                        }
                        .receipt-container { 
                            page-break-inside: avoid; 
                        }
                    }
                </style>
            </head>
            <body>
                <div class="receipt-container">
                    <div class="receipt-header">
                        <div class="store-name">${receiptTitle.toUpperCase()}</div>
                        <div class="store-info">${storeAddress}</div>
                        ${storePhone && storePhone !== '021-12345678' ? `<div class="store-info">Telp: ${storePhone}</div>` : ''}
                        ${storeEmail ? `<div class="store-info">Email: ${storeEmail}</div>` : ''}
                        ${storeWebsite ? `<div class="store-info">${storeWebsite}</div>` : ''}
                        ${storeSocialMedia ? `<div class="store-info">${storeSocialMedia}</div>` : ''}
                        ${receiptHeader ? `<div class="custom-header">${receiptHeader}</div>` : ''}
                    </div>
                    ${content}
                    <div class="footer">
                        <div class="thank-you">*** ${receiptFooter.toUpperCase()} ***</div>
                        <div>Barang yang sudah dibeli</div>
                        <div>tidak dapat dikembalikan</div>
                        <div style="margin-top: 10px;">Dicetak: ${new Date().toLocaleString('id-ID')}</div>
                    </div>
                </div>
                <script>
                    window.onload = function() {
                        setTimeout(function() {
                            window.print();
                        }, 500);
                    }
                </script>
            </body>
        </html>
    `);
    printWindow.document.close();
}

// Utility Functions
function formatNumber(number) {
    return new Intl.NumberFormat('id-ID').format(number);
}

function showAlert(message, type = 'info') {
    const alertDiv = document.createElement('div');
    alertDiv.className = `alert alert-${type} alert-dismissible fade show position-fixed`;
    alertDiv.style.cssText = 'top: 80px; right: 20px; z-index: 9999; min-width: 300px; max-width: 400px;';
    alertDiv.innerHTML = `
        ${message}
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    `;

    document.body.appendChild(alertDiv);

    // Auto remove after 5 seconds
    setTimeout(() => {
        if (alertDiv.parentNode) {
            alertDiv.parentNode.removeChild(alertDiv);
        }
    }, 5000);
}

// Export to Excel function
function exportToExcel() {
    // Create CSV content
    let csvContent = "No,Tanggal,Total\n";

    // Get transaction data
    apiRequest('api/transactions.php')
        .then(transactions => {
            transactions.forEach((transaction, index) => {
                const date = new Date(transaction.transaction_date).toLocaleDateString('id-ID');
                csvContent += `${index + 1},"${date}","Rp ${formatNumber(transaction.total)}"\n`;
            });

            // Create and download file
            const blob = new Blob([csvContent], { type: 'text/csv;charset=utf-8;' });
            const link = document.createElement('a');
            const url = URL.createObjectURL(blob);
            link.setAttribute('href', url);
            link.setAttribute('download', `riwayat_transaksi_${new Date().toISOString().split('T')[0]}.csv`);
            link.style.visibility = 'hidden';
            document.body.appendChild(link);
            link.click();
            document.body.removeChild(link);

            showAlert('Data berhasil diekspor ke Excel!', 'success');
        })
        .catch(error => {
            console.error('Error exporting data:', error);
            showAlert('Error mengekspor data: ' + error.message, 'danger');
        });
}

// Inventory Management Functions
function showAddStockModal() {
    // Populate product dropdown
    const productSelect = document.getElementById('stock-product');
    if (productSelect) {
        productSelect.innerHTML = '<option value="">-- Pilih Produk --</option>';
        products.forEach(product => {
            const option = document.createElement('option');
            option.value = product.id;
            option.textContent = `${product.name} - ${product.category} (Stok: ${product.stock}) - Rp ${formatNumber(product.price)}`;
            productSelect.appendChild(option);
        });

        // Initialize Select2
        $('#stock-product').select2({
            theme: 'bootstrap-5',
            placeholder: '-- Cari dan Pilih Produk --',
            allowClear: true,
            dropdownParent: $('#stockModal'),
            width: '100%',
            language: {
                noResults: function() {
                    return "Produk tidak ditemukan";
                },
                searching: function() {
                    return "Mencari...";
                }
            }
        });
    }

    // Reset form
    const stockForm = document.getElementById('stockForm');
    if (stockForm) stockForm.reset();

    const modal = new bootstrap.Modal(document.getElementById('stockModal'));
    modal.show();
}

// Load inventory data function
async function loadInventoryData() {
    try {
        console.log('Loading inventory data...');
        const result = await apiRequest('api/inventory.php');

        if (Array.isArray(result)) {
            displayInventoryData(result);
            console.log('Inventory data loaded successfully');
        } else if (result.success === false) {
            throw new Error(result.error || 'Unknown error from server');
        } else {
            throw new Error('Unexpected response format');
        }
    } catch (error) {
        console.error('Error loading inventory:', error);
        showAlert('Error loading inventory: ' + error.message, 'danger');
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

// Update margin label and calculate selling price
function updateMarginLabel() {
    const marginType = document.getElementById('margin-type').value;
    const marginLabel = document.getElementById('margin-label');
    if (marginLabel) {
        marginLabel.textContent = marginType === 'percentage' ? 'Margin (%)' : 'Margin (Rp)';
    }
    calculateSellingPrice();
}

// Calculate selling price based on purchase price and margin
function calculateSellingPrice() {
    const purchasePriceElement = document.getElementById('purchase-price');
    const marginTypeElement = document.getElementById('margin-type');
    const marginValueElement = document.getElementById('margin-value');
    const sellingPriceDisplayElement = document.getElementById('selling-price-display');

    if (!purchasePriceElement || !marginTypeElement || !marginValueElement || !sellingPriceDisplayElement) {
        return;
    }

    const purchasePrice = parseFloat(purchasePriceElement.value) || 0;
    const marginType = marginTypeElement.value;
    const marginValue = parseFloat(marginValueElement.value) || 0;

    let sellingPrice = purchasePrice;
    if (marginValue > 0) {
        if (marginType === 'percentage') {
            sellingPrice = purchasePrice + (purchasePrice * marginValue / 100);
        } else {
            sellingPrice = purchasePrice + marginValue;
        }
    }

    sellingPriceDisplayElement.value = 'Rp ' + formatNumber(sellingPrice);
}

async function addStock() {
    const productId = document.getElementById('stock-product').value;
    const quantity = document.getElementById('stock-quantity').value;
    const purchasePrice = document.getElementById('purchase-price').value;
    const marginType = document.getElementById('margin-type').value;
    const marginValue = document.getElementById('margin-value').value;
    const notes = document.getElementById('stock-notes').value;

    if (!productId || !quantity || quantity <= 0 || !purchasePrice || purchasePrice <= 0) {
        showAlert('Harap isi semua field yang diperlukan dengan nilai yang valid!', 'warning');
        return;
    }

    try {
        console.log('Adding stock:', { productId, quantity, purchasePrice, marginType, marginValue, notes });
        const sellingPrice = parseFloat(document.getElementById('selling-price-display').value.replace('Rp ', '').replace(/\./g, '')) || 0;

        const result = await apiRequest('api/inventory.php', {
            method: 'POST',
            body: JSON.stringify({
                product_id: parseInt(productId),
                quantity: parseInt(quantity),
                purchase_price: parseFloat(purchasePrice),
                selling_price: sellingPrice,
                notes: notes.trim()
            })
        });

        if (result.success) {
            showAlert('Stok berhasil ditambahkan!', 'success');

            // Close modal
            const modal = bootstrap.Modal.getInstance(document.getElementById('stockModal'));
            modal.hide();

            // Reset form and Select2
            document.getElementById('stockForm').reset();
            document.getElementById('selling-price-display').value = '';
            $('#stock-product').val(null).trigger('change');

            // Reload inventory data
            loadInventoryData();

            // Reload products to show updated stock and price
            loadProducts();
        } else {
            showAlert('Error: ' + (result.error || 'Unknown error'), 'danger');
        }
    } catch (error) {
        console.error('Error adding stock:', error);
        showAlert('Error adding stock: ' + error.message, 'danger');
    }
}

// Settings Management Functions
let appSettings = {};

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

        // Always update app title regardless of user role
        updateAppTitle();

        console.log('Settings loaded successfully');
    } catch (error) {
        console.error('Error loading settings:', error);
        // For non-admin users, silently use default settings
        if (error.message.includes('403') || error.message.includes('Access denied')) {
            console.log('Using default settings for non-admin user');
            appSettings = {
                app_name: 'Kasir Digital',
                store_name: 'Toko ABC',
                store_address: 'Jl. Contoh No. 123, Kota, Provinsi',
                store_phone: '021-12345678',
                store_email: '',
                store_website: '',
                store_social_media: '',
                receipt_footer: 'Terima kasih atas kunjungan Anda!',
                receipt_header: '',
                currency: 'Rp',
                logo_url: '',
                tax_enabled: false,
                tax_rate: 0
            };
            updateAppTitle();
        } else {
            showAlert('Error loading settings: ' + error.message, 'danger');
        }
    }
}</async>

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

        console.log('Response status:', response.status);
        
        if (!response.ok) {
            throw new Error(`HTTP ${response.status}: ${response.statusText}`);
        }

        const responseText = await response.text();
        console.log('Raw response:', responseText);
        
        let result;
        try {
            result = JSON.parse(responseText);
        } catch (jsonError) {
            console.error('JSON parse error:', jsonError);
            throw new Error('Server response is not valid JSON: ' + responseText);
        }

        console.log('Parsed result:', result);

        if (result && result.success) {
            appSettings = { ...formData };
            showAlert('Pengaturan berhasil disimpan!', 'success');

            // Update app title dynamically
            updateAppTitle();
            
            // Reload settings to ensure UI is updated
            setTimeout(() => {
                loadSettings();
            }, 1000);
        } else {
            const errorMsg = result?.error || result?.message || 'Failed to save settings';
            throw new Error(errorMsg);
        }
    } catch (error) {
        console.error('Error saving settings:', error);
        showAlert('Error menyimpan pengaturan: ' + error.message, 'danger');
    }
}

function updateAppTitle() {
    // Update page title and headers
    const settings = appSettings || {};
    const appName = settings.app_name || 'Kasir Digital';
    const storeName = settings.store_name || '';
    
    // Only show store name if it's not the default
    const displayTitle = (storeName && storeName !== 'Toko ABC') ? `${appName} - ${storeName}` : appName;
    
    document.title = displayTitle;

    // Update sidebar title
    const sidebarTitles = document.querySelectorAll('.sidebar h4');
    sidebarTitles.forEach(title => {
        title.innerHTML = `<i class="fas fa-cash-register"></i> ${displayTitle}`;
    });

    // Update mobile header
    const mobileTitle = document.querySelector('.mobile-header h5');
    if (mobileTitle) {
        mobileTitle.innerHTML = `<i class="fas fa-cash-register"></i> ${displayTitle}`;
    }
}

// Dark/Light Mode Toggle Functions
function toggleTheme() {
    const currentTheme = localStorage.getItem('theme') || 'dark';
    const newTheme = currentTheme === 'dark' ? 'light' : 'dark';

    localStorage.setItem('theme', newTheme);
    applyTheme(newTheme);

    // Update button icons
    const themeIcon = document.getElementById('theme-icon');
    const themeIconDesktop = document.getElementById('theme-icon-desktop');
    const iconClass = newTheme === 'dark' ? 'fas fa-sun' : 'fas fa-moon';

    if (themeIcon) {
        themeIcon.className = iconClass;
    }
    if (themeIconDesktop) {
        themeIconDesktop.className = iconClass;
    }
}

function applyTheme(theme) {
    const root = document.documentElement;

    if (theme === 'light') {
        document.body.classList.add('light-mode');
        document.body.classList.remove('dark-mode');

        root.style.setProperty('--bg-primary', '#f8f9fa');
        root.style.setProperty('--bg-secondary', '#ffffff');
        root.style.setProperty('--bg-tertiary', '#f8f9fa');
        root.style.setProperty('--text-primary', '#212529');
        root.style.setProperty('--text-secondary', '#6c757d');
        root.style.setProperty('--border-color', '#dee2e6');
        root.style.setProperty('--shadow', '0 2px 10px rgba(0, 0, 0, 0.1)');

        // Update table classes
        document.querySelectorAll('.table-dark').forEach(table => {
            table.classList.remove('table-dark');
            table.classList.add('table-light');
        });

        // Force update body background
        document.body.style.setProperty('background', 'linear-gradient(135deg, #f8f9fa 0%, #e9ecef 100%)', 'important');
        document.body.style.setProperty('color', '#212529', 'important');

        // Update sidebar and main content
        const sidebar = document.querySelector('.sidebar');
        const mainContent = document.querySelector('.main-content');

        if (sidebar) {
            sidebar.style.setProperty('background', 'linear-gradient(180deg, #ffffff 0%, #f8f9fa 100%)', 'important');
            sidebar.style.setProperty('border-right', '2px solid #dee2e6', 'important');
        }

        if (mainContent) {
            mainContent.style.setProperty('background', '#f8f9fa', 'important');
        }

    } else {
        document.body.classList.add('dark-mode');
        document.body.classList.remove('light-mode');

        root.style.setProperty('--bg-primary', '#0f1419');
        root.style.setProperty('--bg-secondary', '#1a202c');
        root.style.setProperty('--bg-tertiary', '#2d3748');
        root.style.setProperty('--text-primary', '#e2e8f0');
        root.style.setProperty('--text-secondary', '#a0aec0');
        root.style.setProperty('--border-color', '#374151');
        root.style.setProperty('--shadow', '0 10px 25px rgba(0, 0, 0, 0.3)');

        // Update table classes
        document.querySelectorAll('.table-light').forEach(table => {
            table.classList.remove('table-light');
            table.classList.add('table-dark');
        });

        // Force update body background
        document.body.style.setProperty('background', 'linear-gradient(135deg, #0f1419 0%, #1a202c 100%)', 'important');
        document.body.style.setProperty('color', '#e2e8f0', 'important');

        // Reset sidebar and main content styles
        const sidebar = document.querySelector('.sidebar');
        const mainContent = document.querySelector('.main-content');

        if (sidebar) {
            sidebar.style.removeProperty('background');
            sidebar.style.removeProperty('border-right');
        }

        if (mainContent) {
            mainContent.style.removeProperty('background');
        }
    }
}

// Function to load basic settings for all users
async function loadBasicSettings() {
    try {
        console.log('Loading basic app settings...');
        const response = await fetch('api/settings.php');
        
        if (response.status === 403) {
            // User doesn't have permission, use defaults
            console.log('Using default settings for non-admin user');
            appSettings = {
                app_name: 'Kasir Digital',
                store_name: 'Toko ABC',
                store_address: 'Jl. Contoh No. 123, Kota, Provinsi',
                store_phone: '021-12345678',
                store_email: '',
                store_website: '',
                store_social_media: '',
                receipt_footer: 'Terima kasih atas kunjungan Anda!',
                receipt_header: '',
                currency: 'Rp',
                logo_url: '',
                tax_enabled: false,
                tax_rate: 0
            };
        } else if (response.ok) {
            const settings = await response.json();
            appSettings = settings;
            console.log('Basic settings loaded successfully');
        } else {
            throw new Error(`HTTP ${response.status}: ${response.statusText}`);
        }
        
        // Update app title for all users
        updateAppTitle();
        
    } catch (error) {
        console.error('Error loading basic settings:', error);
        // Use defaults if everything fails
        appSettings = {
            app_name: 'Kasir Digital',
            store_name: 'Toko ABC',
            store_address: 'Jl. Contoh No. 123, Kota, Provinsi',
            store_phone: '021-12345678',
            store_email: '',
            store_website: '',
            store_social_media: '',
            receipt_footer: 'Terima kasih atas kunjungan Anda!',
            receipt_header: '',
            currency: 'Rp',
            logo_url: '',
            tax_enabled: false,
            tax_rate: 0
        };
        updateAppTitle();
    }
}

// Initialize theme on page load
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

    console.log('Initialization complete');
});

// Print receipt for specific transaction
async function printTransactionReceipt(transactionId) {
    try {
        const transaction = await apiRequest(`api/transactions.php?id=${transactionId}`);

        if (transaction && transaction.id) {
            let receiptHTML = `
                <div class="center">
                    <h6>STRUK TRANSAKSI #${transaction.id}</h6>
                    <small>${new Date(transaction.transaction_date).toLocaleString('id-ID')}</small>
                </div>
                <div class="line"></div>
                <table>
            `;

            transaction.items.forEach(item => {
                receiptHTML += `
                    <tr>
                        <td colspan="2">${item.product_name}</td>
                    </tr>
                    <tr>
                        <td>${item.quantity} x Rp ${formatNumber(item.price)}</td>
                        <td class="right">Rp ${formatNumber(item.subtotal)}</td>
                    </tr>
                `;
            });

            receiptHTML += `
                </table>
                <div class="line"></div>
                <table>
                    <tr class="total-row">
                        <td><strong>TOTAL:</strong></td>
                        <td class="right"><strong>Rp ${formatNumber(transaction.total)}</strong></td>
                    </tr>
                </table>
            `;

            // Show in modal first
            document.getElementById('transaction-detail').innerHTML = receiptHTML;
            const modal = new bootstrap.Modal(document.getElementById('transactionModal'));
            modal.show();
        } else {
            showAlert('Transaksi tidak ditemukan!', 'warning');
        }
    } catch (error) {
        console.error('Error loading transaction for print:', error);
        showAlert('Error loading transaction: ' + error.message, 'danger');
    }
}

// Make functions available globally (required for onclick handlers)
window.showPage = showPage;
window.loadInventoryData = loadInventoryData;
window.showAddProductModal = showAddProductModal;
window.editProduct = editProduct;
window.saveProduct = saveProduct;
window.deleteProduct = deleteProduct;
window.selectProduct = selectProduct;
window.addToCart = addToCart;
window.removeFromCart = removeFromCart;
window.changeCartQuantity = changeCartQuantity;
window.updateCartQuantity = updateCartQuantity;
window.processTransaction = processTransaction;
window.clearCart = clearCart;
window.viewTransactionDetail = viewTransactionDetail;
window.printReceipt = printReceipt;
window.printTransactionReceipt = printTransactionReceipt;
window.searchProducts = searchProducts;
window.calculateChange = calculateChange;
window.handleSearchKeyPress = handleSearchKeyPress;
window.quickAddToCart = quickAddToCart;
window.exportToExcel = exportToExcel;
window.showAddStockModal = showAddStockModal;
window.addStock = addStock;
window.toggleTheme = toggleTheme;
window.updateMarginLabel = updateMarginLabel;
window.calculateSellingPrice = calculateSellingPrice;
window.loadSettings = loadSettings;
window.saveSettings = saveSettings;