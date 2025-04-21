<?php

// Start session for messages
session_start();
// Database connection using PDO
require_once "../config/database.php";

// Function to get all stock items
function getAllStock($database) {
    try {
        $stmt = $database->prepare("SELECT s.id, s.produk_id, s.jumlah_stok, s.updated_at, p.nama_produk 
                              FROM stok s 
                              JOIN produk p ON s.produk_id = p.id
                              ORDER BY s.jumlah_stok ASC"); // Order by stock quantity to prioritize low stock
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch(PDOException $e) {
        echo "Error: " . $e->getMessage();
        return [];
    }
}

// Function to get stock count
function getStockCount($database) {
    try {
        $stmt = $database->prepare("SELECT COUNT(*) as count FROM stok");
        $stmt->execute();
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        return $result['count'];
    } catch(PDOException $e) {
        return 0;
    }
}

// Function to get total products
function getProductCount($database) {
    try {
        $stmt = $database->prepare("SELECT COUNT(*) as count FROM produk");
        $stmt->execute();
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        return $result['count'];
    } catch(PDOException $e) {
        return 0;
    }
}

// Function to get low stock items (less than 10)
function getLowStockCount($database) {
    try {
        $stmt = $database->prepare("SELECT COUNT(*) as count FROM stok WHERE jumlah_stok < 10");
        $stmt->execute();
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        return $result['count'];
    } catch(PDOException $e) {
        return 0;
    }
}

// Function to get total stock value
function getTotalStockValue($database) {
    try {
        $stmt = $database->prepare("SELECT SUM(s.jumlah_stok * p.harga) as total_value 
                               FROM stok s 
                               JOIN produk p ON s.produk_id = p.id");
        $stmt->execute();
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        return $result['total_value'] ?: 0;
    } catch(PDOException $e) {
        return 0;
    }
}

// Get data for displaying
$stockItems = getAllStock($database);
$stockCount = getStockCount($database);
$productCount = getProductCount($database);
$lowStockCount = getLowStockCount($database);
$totalStockValue = getTotalStockValue($database);

// Handle add new stock item
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_stock'])) {
    $produk_id = $_POST['produk_id'];
    $jumlah_stok = $_POST['jumlah_stok'];
    
    try {
        $stmt = $database->prepare("INSERT INTO stok (produk_id, jumlah_stok) VALUES (?, ?)");
        $stmt->execute([$produk_id, $jumlah_stok]);
        $success_message = "Stock added successfully!";
        // Redirect to refresh the page
        header("Location: " . $_SERVER['PHP_SELF'] . "?success=added");
        exit();
    } catch(PDOException $e) {
        $error_message = "Error adding stock: " . $e->getMessage();
    }
}

// Handle update stock
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_stock'])) {
    $id = $_POST['id'];
    $jumlah_stok = $_POST['jumlah_stok'];
    
    try {
        $stmt = $database->prepare("UPDATE stok SET jumlah_stok = ? WHERE id = ?");
        $stmt->execute([$jumlah_stok, $id]);
        // Redirect to refresh the page
        header("Location: " . $_SERVER['PHP_SELF'] . "?success=updated");
        exit();
    } catch(PDOException $e) {
        $error_message = "Error updating stock: " . $e->getMessage();
    }
}

// Handle delete stock
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_stock'])) {
    $id = $_POST['id'];
    
    try {
        $stmt = $database->prepare("DELETE FROM stok WHERE id = ?");
        $stmt->execute([$id]);
        // Redirect to refresh the page
        header("Location: " . $_SERVER['PHP_SELF'] . "?success=deleted");
        exit();
    } catch(PDOException $e) {
        $error_message = "Error deleting stock: " . $e->getMessage();
    }
}

// Get all products for dropdown
function getAllProducts($database) {
    try {
        $stmt = $database->prepare("SELECT id, nama_produk FROM produk ORDER BY nama_produk ASC");
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch(PDOException $e) {
        return [];
    }
}
$products = getAllProducts($database);

// Check for success message from query parameters
$success_message = "";
if (isset($_GET['success'])) {
    switch ($_GET['success']) {
        case 'added':
            $success_message = "Stock added successfully!";
            break;
        case 'updated':
            $success_message = "Stock updated successfully!";
            break;
        case 'deleted':
            $success_message = "Stock deleted successfully!";
            break;
    }
}
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
    <style>
        :root {
            --primary-color: #4e73df;
            --secondary-color: #858796;
            --success-color: #1cc88a;
            --info-color: #36b9cc;
            --warning-color: #f6c23e;
            --danger-color: #e74a3b;
        }
        
        body {
            font-family: 'Nunito', sans-serif;
            background-color: #f8f9fc;
        }
        
        .dashboard-card {
            border-radius: 0.5rem;
            border-left: 0.25rem solid;
            box-shadow: 0 0.15rem 1.75rem 0 rgba(58, 59, 69, 0.15);
            margin-bottom: 1.5rem;
            transition: transform 0.3s, box-shadow 0.3s;
        }
        
        .dashboard-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 0.5rem 2rem 0 rgba(58, 59, 69, 0.2);
        }
        
        .card-primary {
            border-left-color: var(--primary-color);
        }
        
        .card-success {
            border-left-color: var(--success-color);
        }
        
        .card-warning {
            border-left-color: var(--warning-color);
        }
        
        .card-danger {
            border-left-color: var(--danger-color);
        }
        
        .card-info {
            border-left-color: var(--info-color);
        }
        
        .card-icon {
            color: #dddfeb;
        }
        
        .card-header {
            background-color: #f8f9fc;
            border-bottom: 1px solid #e3e6f0;
        }
        
        .main-content {
            padding: 1.5rem;
        }
        
        .table-container {
            background-color: white;
            border-radius: 0.35rem;
            box-shadow: 0 0.15rem 1.75rem 0 rgba(58, 59, 69, 0.15);
        }
        
        .stock-table thead th {
            font-weight: 600;
            background-color: #f8f9fc;
        }
        
        .btn-circle {
            border-radius: 100%;
            width: 2.5rem;
            height: 2.5rem;
            display: inline-flex;
            align-items: center;
            justify-content: center;
        }
        
        .btn-circle.btn-sm {
            width: 2rem;
            height: 2rem;
        }
        
        .alert-dismissible {
            position: fixed;
            top: 20px;
            right: 20px;
            z-index: 9999;
        }
        
        .stock-badge {
            font-size: 85%;
            font-weight: 600;
            padding: 0.35em 0.65em;
        }
        
        .progress {
            height: 0.5rem;
        }
        
        footer {
            font-size: 0.8rem;
            color: var(--secondary-color);
        }
        
        /* Filter and search elements */
        .filter-row {
            background-color: #fff;
            border-radius: 0.5rem;
            margin-bottom: 1rem;
            padding: 1rem;
            box-shadow: 0 0.15rem 1.75rem 0 rgba(58, 59, 69, 0.15);
        }
        
        
    </style>
</head>
<body>
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
                <?php if (isset($error_message)): ?>
                <div class="alert alert-danger alert-dismissible fade show" role="alert">
                    <i class="fas fa-exclamation-circle me-2"></i> <?= $error_message ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
                <?php endif; ?>

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
                                    <?php 
                                    // Apply simple filtering logic
                                    $filteredItems = $stockItems;
                                    
                                    // Filter by search term
                                    if (isset($_GET['search']) && !empty($_GET['search'])) {
                                        $search = strtolower($_GET['search']);
                                        $filteredItems = array_filter($filteredItems, function($item) use ($search) {
                                            return strpos(strtolower($item['nama_produk']), $search) !== false;
                                        });
                                    }
                                    
                                    // Filter by stock level
                                    if (isset($_GET['stock_level']) && !empty($_GET['stock_level'])) {
                                        $stockLevel = $_GET['stock_level'];
                                        $filteredItems = array_filter($filteredItems, function($item) use ($stockLevel) {
                                            if ($stockLevel == 'low') {
                                                return $item['jumlah_stok'] < 10;
                                            } elseif ($stockLevel == 'medium') {
                                                return $item['jumlah_stok'] >= 10 && $item['jumlah_stok'] < 20;
                                            } elseif ($stockLevel == 'good') {
                                                return $item['jumlah_stok'] >= 20;
                                            }
                                            return true;
                                        });
                                    }
                                    
                                    if (empty($filteredItems)): 
                                    ?>
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
                                    <a class="page-link" href="#">
                                        <i class="fas fa-angle-right"></i>
                                    </a>
                                </li>
                            </ul>
                        </nav>
                        <?php endif; ?>
                    </div>
                </div>
                
                <!-- Footer -->
                <footer class="text-center py-4 mt-auto">
                    <div>
                        <span>&copy; 2025 Inventory Management System</span>
                    </div>
                </footer>
            </main>
        </div>
    </div>

    <!-- Add Stock Modal -->

    <!-- Add Stock Modal -->
    <div class="modal fade" id="addStockModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header bg-primary text-white">
                    <h5 class="modal-title">
                        <i class="fas fa-plus-circle me-2"></i>Add New Stock
                    </h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <form method="post">
                    <div class="modal-body">
                        <div class="mb-3">
                            <label for="produkId" class="form-label">Product</label>
                            <select class="form-select" id="produkId" name="produk_id" required>
                                <option value="">-- Select Product --</option>
                                <?php foreach ($products as $product): ?>
                                    <option value="<?= $product['id'] ?>"><?= $product['nama_produk'] ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="mb-3">
                            <label for="jumlahStok" class="form-label">Quantity</label>
                            <div class="input-group">
                                <input type="number" class="form-control" id="jumlahStok" name="jumlah_stok" required min="1" value="1">
                                <span class="input-group-text">units</span>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" name="add_stock" class="btn btn-primary">
                            <i class="fas fa-plus me-1"></i>Add Stock
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Bootstrap JS Bundle with Popper -->
</body>
</html>