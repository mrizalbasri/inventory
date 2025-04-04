<?php
// Database connection using PDO
include "database.php";

// Function to get all stock items
function getAllStock($database) {
    try {
        $stmt = $database->prepare("SELECT s.id, s.produk_id, s.jumlah_stok, s.updated_at, p.nama_produk 
                              FROM stok s 
                              JOIN produk p ON s.produk_id = p.id");
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

// Get data for displaying
$stockItems = getAllStock($database);
$stockCount = getStockCount($database);
$productCount = getProductCount($database);
$lowStockCount = getLowStockCount($database);

// Handle add new stock item
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_stock'])) {
    $produk_id = $_POST['produk_id'];
    $jumlah_stok = $_POST['jumlah_stok'];
    
    try {
        $stmt = $database->prepare("INSERT INTO stok (produk_id, jumlah_stok) VALUES (?, ?)");
        $stmt->execute([$produk_id, $jumlah_stok]);
        // Redirect to refresh the page
        header("Location: " . $_SERVER['PHP_SELF']);
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
        header("Location: " . $_SERVER['PHP_SELF']);
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
        header("Location: " . $_SERVER['PHP_SELF']);
        exit();
    } catch(PDOException $e) {
        $error_message = "Error deleting stock: " . $e->getMessage();
    }
}

// Get all products for dropdown
function getAllProducts($database) {
    try {
        $stmt = $database->prepare("SELECT id, nama_produk FROM produk");
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch(PDOException $e) {
        return [];
    }
}
$products = getAllProducts($database);
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
        .dashboard-card {
            transition: transform 0.3s;
        }
        .dashboard-card:hover {
            transform: translateY(-5px);
        }

    </style>
</head>
<body>
    <div class="container-fluid">
        <div class="row">
            <!-- Sidebar -->
            <?php include 'sidebar.php'; ?>

            <!-- Main Content -->
            <div class="col-md-10 ms-sm-auto col-lg-10 px-md-4 py-4">
                <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
                    <h1 class="h2">Dashboard</h1>
                    <div class="btn-toolbar mb-2 mb-md-0">
                        <button type="button" class="btn btn-sm btn-primary" data-bs-toggle="modal" data-bs-target="#addStockModal">
                            <i class="fas fa-plus"></i> Add New Stock
                        </button>
                    </div>
                </div>

                <!-- Information Cards -->
                <div class="row mb-4">
                    <div class="col-md-4">
                        <div class="card dashboard-card text-white bg-primary">
                            <div class="card-body">
                                <div class="d-flex justify-content-between align-items-center">
                                    <div>
                                        <h6 class="card-title">Total Stock Items</h6>
                                        <h2 class="card-text"><?= $stockCount ?></h2>
                                    </div>
                                    <i class="fas fa-cubes fa-3x opacity-50"></i>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="card dashboard-card text-white bg-success">
                            <div class="card-body">
                                <div class="d-flex justify-content-between align-items-center">
                                    <div>
                                        <h6 class="card-title">Total Products</h6>
                                        <h2 class="card-text"><?= $productCount ?></h2>
                                    </div>
                                    <i class="fas fa-box fa-3x opacity-50"></i>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="card dashboard-card text-white bg-danger">
                            <div class="card-body">
                                <div class="d-flex justify-content-between align-items-center">
                                    <div>
                                        <h6 class="card-title">Low Stock Items</h6>
                                        <h2 class="card-text"><?= $lowStockCount ?></h2>
                                    </div>
                                    <i class="fas fa-exclamation-triangle fa-3x opacity-50"></i>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Stock Table -->
                <div class="card mb-4">
                    <div class="card-header">
                        <i class="fas fa-table me-1"></i>
                        Stock Management
                    </div>
                    <div class="card-body">
                        <?php if (isset($error_message)): ?>
                            <div class="alert alert-danger"><?= $error_message ?></div>
                        <?php endif; ?>
                        <div class="table-responsive">
                            <table class="table table-bordered table-hover" id="stockTable">
                                <thead class="table-dark">
                                    <tr>
                                        <th>ID</th>
                                        <th>Product</th>
                                        <th>Quantity</th>
                                        <th>Last Updated</th>
                                        <th>Status</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($stockItems as $item): ?>
                                        <tr>
                                            <td><?= $item['id'] ?></td>
                                            <td><?= $item['nama_produk'] ?></td>
                                            <td><?= $item['jumlah_stok'] ?></td>
                                            <td><?= $item['updated_at'] ?></td>
                                            <td>
                                                <?php if ($item['jumlah_stok'] < 10): ?>
                                                    <span class="badge bg-danger">Low Stock</span>
                                                <?php elseif ($item['jumlah_stok'] < 20): ?>
                                                    <span class="badge bg-warning">Medium</span>
                                                <?php else: ?>
                                                    <span class="badge bg-success">Good</span>
                                                <?php endif; ?>
                                            </td>
                                            <td>
                                                <button class="btn btn-sm btn-primary" data-bs-toggle="modal" data-bs-target="#editStockModal<?= $item['id'] ?>">
                                                    <i class="fas fa-edit"></i>
                                                </button>
                                                <button class="btn btn-sm btn-danger" data-bs-toggle="modal" data-bs-target="#deleteStockModal<?= $item['id'] ?>">
                                                    <i class="fas fa-trash"></i>
                                                </button>
                                            </td>
                                        </tr>

                                        <!-- Edit Modal for each item -->
                                        <div class="modal fade" id="editStockModal<?= $item['id'] ?>" tabindex="-1" aria-hidden="true">
                                            <div class="modal-dialog">
                                                <div class="modal-content">
                                                    <div class="modal-header">
                                                        <h5 class="modal-title">Edit Stock</h5>
                                                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                                    </div>
                                                    <form method="post">
                                                        <div class="modal-body">
                                                            <input type="hidden" name="id" value="<?= $item['id'] ?>">
                                                            <div class="mb-3">
                                                                <label for="editProductName" class="form-label">Product</label>
                                                                <input type="text" class="form-control" id="editProductName" value="<?= $item['nama_produk'] ?>" disabled>
                                                            </div>
                                                            <div class="mb-3">
                                                                <label for="editJumlahStok" class="form-label">Quantity</label>
                                                                <input type="number" class="form-control" id="editJumlahStok" name="jumlah_stok" value="<?= $item['jumlah_stok'] ?>" required>
                                                            </div>
                                                        </div>
                                                        <div class="modal-footer">
                                                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                                                            <button type="submit" name="update_stock" class="btn btn-primary">Update</button>
                                                        </div>
                                                    </form>
                                                </div>
                                            </div>
                                        </div>

                                        <!-- Delete Modal for each item -->
                                        <div class="modal fade" id="deleteStockModal<?= $item['id'] ?>" tabindex="-1" aria-hidden="true">
                                            <div class="modal-dialog">
                                                <div class="modal-content">
                                                    <div class="modal-header">
                                                        <h5 class="modal-title">Confirm Delete</h5>
                                                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                                    </div>
                                                    <form method="post">
                                                        <div class="modal-body">
                                                            <input type="hidden" name="id" value="<?= $item['id'] ?>">
                                                            <p>Are you sure you want to delete stock for <strong><?= $item['nama_produk'] ?></strong>?</p>
                                                        </div>
                                                        <div class="modal-footer">
                                                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                                                            <button type="submit" name="delete_stock" class="btn btn-danger">Delete</button>
                                                        </div>
                                                    </form>
                                                </div>
                                            </div>
                                        </div>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
                
                <!-- Footer -->
                <footer class="bg-light text-center text-lg-start mt-auto">
                    <div class="text-center p-3">
                        © 2025 Inventory Management System
                    </div>
                </footer>
            </div>
        </div>
    </div>

    <!-- Add Stock Modal -->
    <div class="modal fade" id="addStockModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Add New Stock</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
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