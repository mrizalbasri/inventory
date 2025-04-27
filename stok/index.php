<?php
// Start session for messages
session_start();

// Include database configuration
require_once "../config/database.php";

// Include Stock class
require_once "Stock.php"; // Adjust path as needed based on your file structure

// Create Database instance
$dbInstance = new Database();
$database = $dbInstance->getConnection();

// Create Stock instance
$stockManager = new Stock($database);

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Handle add new stock item
    if (isset($_POST['add_stock'])) {
        $produk_id = $_POST['produk_id'];
        $jumlah_stok = $_POST['jumlah_stok'];
        
        if ($stockManager->addStock($produk_id, $jumlah_stok)) {
            $_SESSION['success'] = "Stock added successfully!";
            header("Location: " . $_SERVER['PHP_SELF']);
            exit();
        } else {
            $_SESSION['error'] = "Error adding stock";
        }
    }
    
    // Handle update stock
    if (isset($_POST['update_stock'])) {
        $id = $_POST['id'];
        $jumlah_stok = $_POST['jumlah_stok'];
        
        if ($stockManager->updateStock($id, $jumlah_stok)) {
            $_SESSION['success'] = "Stock updated successfully!";
            header("Location: " . $_SERVER['PHP_SELF']);
            exit();
        } else {
            $_SESSION['error'] = "Error updating stock";
        }
    }
    
    // Handle delete stock
    if (isset($_POST['delete_stock'])) {
        $id = $_POST['id'];
        
        if ($stockManager->deleteStock($id)) {
            $_SESSION['success'] = "Stock deleted successfully!";
            header("Location: " . $_SERVER['PHP_SELF']);
            exit();
        } else {
            $_SESSION['error'] = "Error deleting stock";
        }
    }
}

// Get data for displaying
$stockItems = $stockManager->getAllStock();
$stockCount = $stockManager->getStockCount();
$productCount = $stockManager->getProductCount();
$lowStockCount = $stockManager->getLowStockCount();
$totalStockValue = $stockManager->getTotalStockValue();
$products = $stockManager->getAllProducts();

// Get flash messages
$success_message = isset($_SESSION['success']) ? $_SESSION['success'] : "";

// Clear session messages
unset($_SESSION['success']);
unset($_SESSION['error']);



// Apply filters if any
$search = isset($_GET['search']) ? $_GET['search'] : '';
$stockLevel = isset($_GET['stock_level']) ? $_GET['stock_level'] : '';
$filteredItems = $stockManager->filterStockItems($stockItems, $search, $stockLevel);

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Inventory System Dashboard</title>
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link href="../styles.css" rel="stylesheet">
</head >

<body class="stock">
    <div class="container-fluid">
        <div class="row">
            <!-- Sidebar -->
            <?php include '../include/sidebar.php'; ?>

            <!-- Main Content -->
            <main class="col-md-10 ms-sm-auto col-lg-10 px-md-4 main-content">
                <!-- Page Heading -->
                <div class="d-sm-flex align-items-center justify-content-between mb-4">
                    <h1 class="h3 mb-0 text-gray-800">Stock Management</h1>
                    <button type="button" class="btn btn-primary shadow-sm" data-bs-toggle="modal" data-bs-target="#addStockModal">
                        <i class="fas fa-plus fa-sm text-white-50 me-2"></i>Add New Stock
                    </button>
                </div>

                <!-- Success Alert -->
                <?php if ($success_message): ?>
                <div class="alert alert-success alert-dismissible fade show" role="alert">
                    <i class="fas fa-check-circle me-2"></i> <?= $success_message ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
                <?php endif; ?>
                
                <!-- Error Alert -->
    

                <!-- Information Cards -->
                <div class="row">
                    <div class="col-xl-3 col-md-6">
                        <div class="card dashboard-card shadow h-100 py-2 card-primary">
                            <div class="card-body">
                                <div class="row no-gutters align-items-center">
                                    <div class="col mr-2">
                                        <div class="text-xs font-weight-bold text-primary text-uppercase mb-1">
                                            Total Stock Items</div>
                                        <div class="h5 mb-0 font-weight-bold text-gray-800"><?= number_format($stockCount) ?></div>
                                    </div>
                                    <div class="col-auto">
                                        <i class="fas fa-boxes fa-2x card-icon"></i>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="col-xl-3 col-md-6">
                        <div class="card dashboard-card shadow h-100 py-2 card-success">
                            <div class="card-body">
                                <div class="row no-gutters align-items-center">
                                    <div class="col mr-2">
                                        <div class="text-xs font-weight-bold text-success text-uppercase mb-1">
                                            Total Products</div>
                                        <div class="h5 mb-0 font-weight-bold text-gray-800"><?= number_format($productCount) ?></div>
                                    </div>
                                    <div class="col-auto">
                                        <i class="fas fa-box fa-2x card-icon"></i>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="col-xl-3 col-md-6">
                        <div class="card dashboard-card shadow h-100 py-2 card-danger">
                            <div class="card-body">
                                <div class="row no-gutters align-items-center">
                                    <div class="col mr-2">
                                        <div class="text-xs font-weight-bold text-danger text-uppercase mb-1">
                                            Low Stock Items</div>
                                        <div class="h5 mb-0 font-weight-bold text-gray-800"><?= number_format($lowStockCount) ?></div>
                                    </div>
                                    <div class="col-auto">
                                        <i class="fas fa-exclamation-triangle fa-2x card-icon"></i>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="col-xl-3 col-md-6">
                        <div class="card dashboard-card shadow h-100 py-2 card-info">
                            <div class="card-body">
                                <div class="row no-gutters align-items-center">
                                    <div class="col mr-2">
                                        <div class="text-xs font-weight-bold text-info text-uppercase mb-1">
                                            Total Stock Value</div>
                                        <div class="h5 mb-0 font-weight-bold text-gray-800">Rp <?= number_format($totalStockValue, 0, ',', '.') ?></div>
                                    </div>
                                    <div class="col-auto">
                                        <i class="fas fa-coins fa-2x card-icon"></i>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Simple Filter -->
                <div class="card shadow mb-4 mt-4">
                    <div class="card-header py-3 d-flex flex-row align-items-center justify-content-between">
                        <h6 class="m-0 font-weight-bold text-primary">
                            <i class="fas fa-clipboard-list me-2"></i>Stock Inventory
                        </h6>
                        <div>
                            <a href="<?= $_SERVER['PHP_SELF'] ?>" class="btn btn-sm btn-outline-primary">
                                <i class="fas fa-sync-alt me-1"></i>Refresh
                            </a>
                        </div>
                    </div>
                    
                    <!-- Filter form - using GET method for simple filtering without JS -->
                    <form method="get" class="card-body pb-0">
                        <div class="row mb-3">
                            <div class="col-md-4 mb-2">
                                <div class="input-group">
                                    <span class="input-group-text"><i class="fas fa-search"></i></span>
                                    <input type="text" class="form-control" name="search" placeholder="Search products..." 
                                           value="<?= isset($_GET['search']) ? htmlspecialchars($_GET['search']) : '' ?>">
                                </div>
                            </div>
                            <div class="col-md-3 mb-2">
                                <select class="form-select" name="stock_level">
                                    <option value="">All Stock Levels</option>
                                    <option value="low" <?= (isset($_GET['stock_level']) && $_GET['stock_level'] == 'low') ? 'selected' : '' ?>>Low Stock</option>
                                    <option value="medium" <?= (isset($_GET['stock_level']) && $_GET['stock_level'] == 'medium') ? 'selected' : '' ?>>Medium Stock</option>
                                    <option value="good" <?= (isset($_GET['stock_level']) && $_GET['stock_level'] == 'good') ? 'selected' : '' ?>>Good Stock</option>
                                </select>
                            </div>
                            <div class="col-md-2 mb-2">
                                <button type="submit" class="btn btn-primary w-100">Filter</button>
                            </div>
                            <div class="col-md-3 mb-2 text-end">
                                <a href="<?= $_SERVER['PHP_SELF'] ?>" class="btn btn-outline-secondary">Clear Filters</a>
                            </div>
                        </div>
                    </form>
                    
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-hover stock-table">
                                <thead>
                                    <tr>
                                        <th>ID</th>
                                        <th>Product</th>
                                        <th>Quantity</th>
                                        <th>Status</th>
                                        <th>Last Updated</th>
                                        <th class="text-center">Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php if (empty($filteredItems)): ?>
                                        <tr>
                                            <td colspan="6" class="text-center py-4">
                                                <i class="fas fa-search fa-2x mb-3 text-muted"></i>
                                                <p class="text-muted">No stock items found. Try clearing filters or adding new stock.</p>
                                            </td>
                                        </tr>
                                    <?php else: ?>
                                        <?php foreach ($filteredItems as $item): ?>
                                            <tr>
                                                <td><?= $item['id'] ?></td>
                                                <td>
                                                    <span class="fw-bold"><?= $item['nama_produk'] ?></span>
                                                </td>
                                                <td>
                                                    <div class="d-flex align-items-center">
                                                        <?= $item['jumlah_stok'] ?>
                                                        <?php if ($item['jumlah_stok'] < 10): ?>
                                                            <div class="ms-2">
                                                                <div class="progress" style="width: 100px;">
                                                                    <div class="progress-bar bg-danger" role="progressbar" style="width: <?= ($item['jumlah_stok'] * 10) ?>%"></div>
                                                                </div>
                                                            </div>
                                                        <?php elseif ($item['jumlah_stok'] < 20): ?>
                                                            <div class="ms-2">
                                                                <div class="progress" style="width: 100px;">
                                                                    <div class="progress-bar bg-warning" role="progressbar" style="width: <?= ($item['jumlah_stok'] * 5) ?>%"></div>
                                                                </div>
                                                            </div>
                                                        <?php else: ?>
                                                            <div class="ms-2">
                                                                <div class="progress" style="width: 100px;">
                                                                    <div class="progress-bar bg-success" role="progressbar" style="width: 100%"></div>
                                                                </div>
                                                            </div>
                                                        <?php endif; ?>
                                                    </div>
                                                </td>
                                                <td>
                                                    <?php if ($item['jumlah_stok'] < 10): ?>
                                                        <span class="badge bg-danger stock-badge">
                                                            <i class="fas fa-exclamation-circle me-1"></i> Low Stock
                                                        </span>
                                                    <?php elseif ($item['jumlah_stok'] < 20): ?>
                                                        <span class="badge bg-warning text-dark stock-badge">
                                                            <i class="fas fa-exclamation-triangle me-1"></i> Medium
                                                        </span>
                                                    <?php else: ?>
                                                        <span class="badge bg-success stock-badge">
                                                            <i class="fas fa-check-circle me-1"></i> Good
                                                        </span>
                                                    <?php endif; ?>
                                                </td>
                                                <td>
                                                    <small class="text-muted">
                                                        <i class="far fa-clock me-1"></i>
                                                        <?= date('d M Y H:i', strtotime($item['updated_at'])) ?>
                                                    </small>
                                                </td>
                                                <td class="text-center">
                                                    <button class="btn btn-sm btn-warning" data-bs-toggle="modal" data-bs-target="#editStockModal<?= $item['id'] ?>" title="Edit">
                                                    <i class="bi bi-pencil"></i>
                                                    </button>
                                                    <button class="btn btn-danger btn-circle btn-sm" data-bs-toggle="modal" data-bs-target="#deleteStockModal<?= $item['id'] ?>" title="Delete">
                                                    <i class="bi bi-trash"></i>
                                                    </button>
                                                </td>
                                            </tr>

                                            <!-- Edit Modal for each item -->
                                            <div class="modal fade" id="editStockModal<?= $item['id'] ?>" tabindex="-1" aria-hidden="true">
                                                <div class="modal-dialog">
                                                    <div class="modal-content">
                                                        <div class="modal-header">
                                                            <h5 class="modal-title">
                                                                <i class="fas fa-edit me-2 text-primary"></i>Edit Stock
                                                            </h5>
                                                            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                                        </div>
                                                        <form method="post">
                                                            <div class="modal-body">
                                                                <input type="hidden" name="id" value="<?= $item['id'] ?>">
                                                                <div class="mb-3">
                                                                    <label for="editProductName" class="form-label">Product</label>
                                                                    <input type="text" class="form-control bg-light" id="editProductName" value="<?= $item['nama_produk'] ?>" disabled>
                                                                </div>
                                                                <div class="mb-3">
                                                                    <label for="editJumlahStok" class="form-label">Quantity</label>
                                                                    <div class="input-group">
                                                                        <input type="number" class="form-control" id="editJumlahStok" name="jumlah_stok" value="<?= $item['jumlah_stok'] ?>" required min="0">
                                                                        <span class="input-group-text">units</span>
                                                                    </div>
                                                                    <div class="form-text">
                                                                        <?php if ($item['jumlah_stok'] < 10): ?>
                                                                            <span class="text-danger"><i class="fas fa-exclamation-circle me-1"></i>Low stock, consider reordering</span>
                                                                        <?php endif; ?>
                                                                    </div>
                                                                </div>
                                                            </div>
                                                            <div class="modal-footer">
                                                                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                                                                <button type="submit" name="update_stock" class="btn btn-primary">
                                                                    <i class="fas fa-save me-1"></i>Update
                                                                </button>
                                                            </div>
                                                        </form>
                                                    </div>
                                                </div>
                                            </div>

                                            <!-- Delete Modal for each item -->
                                            <div class="modal fade" id="deleteStockModal<?= $item['id'] ?>" tabindex="-1" aria-hidden="true">
                                                <div class="modal-dialog">
                                                    <div class="modal-content">
                                                        <div class="modal-header bg-danger text-white">
                                                            <h5 class="modal-title">
                                                                <i class="fas fa-trash me-2"></i>Confirm Delete
                                                            </h5>
                                                            <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                                                        </div>
                                                        <form method="post">
                                                            <div class="modal-body">
                                                                <input type="hidden" name="id" value="<?= $item['id'] ?>">
                                                                <div class="text-center mb-3">
                                                                    <i class="fas fa-exclamation-triangle fa-4x text-warning mb-3"></i>
                                                                    <p>Are you sure you want to delete stock for:</p>
                                                                    <h5 class="font-weight-bold"><?= $item['nama_produk'] ?></h5>
                                                                    <p><small>This action cannot be undone.</small></p>
                                                                </div>
                                                            </div>
                                                            <div class="modal-footer">
                                                                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                                                                <button type="submit" name="delete_stock" class="btn btn-danger">
                                                                    <i class="fas fa-trash me-1"></i>Delete
                                                                </button>
                                                            </div>
                                                        </form>
                                                    </div>
                                                </div>
                                            </div>
                                        <?php endforeach; ?>
                                    <?php endif; ?>
                                </tbody>
                            </table>
                        </div>
                        
                        <!-- Simple pagination alternative (if many records) -->
                        <?php if (count($filteredItems) > 10): ?>
                        <nav aria-label="Page navigation" class="mt-4">
                            <ul class="pagination justify-content-center">
                                <li class="page-item disabled">
                                    <a class="page-link" href="#" tabindex="-1" aria-disabled="true">
                                        <i class="fas fa-angle-left"></i>
                                    </a>
                                </li>
                                <li class="page-item active"><a class="page-link" href="#">1</a></li>
                                <li class="page-item"><a class="page-link" href="#">2</a></li>
                                <li class="page-item"><a class="page-link" href="#">3</a></li>
                                <li class="page-item">
                                    <a class="page-link" href="#"><a class="page-link" href="#">
                                        <i class="fas fa-angle-right"></i>
                                    </a>
                                </li>
                            </ul>
                        </nav>
                        <?php endif; ?>
                    </div>
                </div>
                
                <!-- Footer -->
                <footer class="mt-5 pb-3">
                    <div class="container-fluid">
                        <div class="text-center">
                            <span>Copyright &copy; Inventory Management System 2025</span>
                        </div>
                    </div>
                </footer>
            </main>
        </div>
    </div>

    <!-- Add Stock Modal -->
    <div class="modal fade" id="addStockModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">
                        <i class="fas fa-plus me-2 text-primary"></i>Add New Stock
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <form method="post">
                    <div class="modal-body">
                        <div class="mb-3">
                            <label for="produk_id" class="form-label">Product</label>
                            <select class="form-select" id="produk_id" name="produk_id" required>
                                <option value="">Select a product</option>
                                <?php foreach($products as $product): ?>
                                    <option value="<?= $product['id'] ?>"><?= $product['nama_produk'] ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="mb-3">
                            <label for="jumlah_stok" class="form-label">Quantity</label>
                            <div class="input-group">
                                <input type="number" class="form-control" id="jumlah_stok" name="jumlah_stok" required min="0">
                                <span class="input-group-text">units</span>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" name="add_stock" class="btn btn-primary">
                            <i class="fas fa-save me-1"></i>Add Stock
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Bootstrap Bundle with Popper -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/js/bootstrap.bundle.min.js"></script>
    
</body>
</html>