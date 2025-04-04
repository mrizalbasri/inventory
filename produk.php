<?php
// Include your database connection file
require_once 'database.php';

// Function to add a new product
function addProduct($database) {
    if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['add_product'])) {
        $id = $_POST['id'];
        $nama_produk = $_POST['nama_produk'];
        $deskripsi = $_POST['deskripsi'];
        $harga = $_POST['harga'];
        $kategori = $_POST['kategori'];

        $errors = [];
        if (empty($id)) $errors[] = "ID is required";
        if (empty($nama_produk)) $errors[] = "Product name is required";
        if (empty($harga)) $errors[] = "Price is required";

        if (empty($errors)) {
            $stmt = $database->prepare("INSERT INTO produk (id, nama_produk, deskripsi, harga, kategori) VALUES (?, ?, ?, ?, ?)");
            
            try {
                $result = $stmt->execute([$id, $nama_produk, $deskripsi, $harga, $kategori]);
                
                if ($result) {
                    $_SESSION['success_message'] = "Product added successfully!";
                } else {
                    $_SESSION['error_message'] = "Failed to add product.";
                }
                header("Location: ".$_SERVER['PHP_SELF']);
                exit();
            } catch(PDOException $e) {
                $_SESSION['error_message'] = "Error: " . $e->getMessage();
            }
        }
    }
}

// Function to retrieve all products
function getAllProducts($database) {
    $stmt = $database->prepare("SELECT * FROM produk ORDER BY created_at DESC");
    $stmt->execute();
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

// Function to get product by ID for editing
function getProductById($database, $id) {
    $stmt = $database->prepare("SELECT * FROM produk WHERE id = ?");
    $stmt->execute([$id]);
    return $stmt->fetch(PDO::FETCH_ASSOC);
}

// Function to update a product
function updateProduct($database) {
    if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['update_product'])) {
        $id = $_POST['id'];
        $nama_produk = $_POST['nama_produk'];
        $deskripsi = $_POST['deskripsi'];
        $harga = $_POST['harga'];
        $kategori = $_POST['kategori'];

        $stmt = $database->prepare("UPDATE produk SET nama_produk = ?, deskripsi = ?, harga = ?, kategori = ? WHERE id = ?");
        
        try {
            $result = $stmt->execute([$nama_produk, $deskripsi, $harga, $kategori, $id]);
            
            if ($result) {
                $_SESSION['success_message'] = "Product updated successfully!";
            } else {
                $_SESSION['error_message'] = "Failed to update product.";
            }
            header("Location: ".$_SERVER['PHP_SELF']);
            exit();
        } catch(PDOException $e) {
            $_SESSION['error_message'] = "Error: " . $e->getMessage();
        }
    }
}

// Function to delete a product
function deleteProduct($database) {
    if (isset($_GET['delete_id'])) {
        $id = $_GET['delete_id'];
        
        $stmt = $database->prepare("DELETE FROM produk WHERE id = ?");
        
        try {
            $result = $stmt->execute([$id]);
            
            if ($result) {
                $_SESSION['success_message'] = "Product deleted successfully!";
            } else {
                $_SESSION['error_message'] = "Failed to delete product.";
            }
            header("Location: ".$_SERVER['PHP_SELF']);
            exit();
        } catch(PDOException $e) {
            $_SESSION['error_message'] = "Error: " . $e->getMessage();
        }
    }
}

// Start session for messages
session_start();

// Process operations
addProduct($database);
updateProduct($database);
deleteProduct($database);

// Retrieve products
$products = getAllProducts($database);

// Check if we're in edit mode
$edit_product = null;
if (isset($_GET['edit_id'])) {
    $edit_product = getProductById($database, $_GET['edit_id']);
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Product Management</title>
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.2.3/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>
<div class="container-fluid">
    <div class="row">
        <!-- Sidebar (assume it's already included) -->

            <?php include 'sidebar.php'; ?>
     
        
        <!-- Main Content -->
        <div class="col-md-10 mt-4">
            <div class="card">
                <div class="card-header bg-primary text-white">
                    <h2 class="mb-0">Product Management</h2>
                </div>
                <div class="card-body">
                    <!-- Success/Error Messages -->
                    <?php if(isset($_SESSION['success_message'])): ?>
                        <div class="alert alert-success alert-dismissible fade show" role="alert">
                            <?php 
                            echo $_SESSION['success_message']; 
                            unset($_SESSION['success_message']);
                            ?>
                            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                        </div>
                    <?php endif; ?>

                    <?php if(isset($_SESSION['error_message'])): ?>
                        <div class="alert alert-danger alert-dismissible fade show" role="alert">
                            <?php 
                            echo $_SESSION['error_message']; 
                            unset($_SESSION['error_message']);
                            ?>
                            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                        </div>
                    <?php endif; ?>

                    <!-- Product Form -->
                    <div class="row">
                        <div class="col-md-4">
                            <div class="card mb-4">
                                <div class="card-header">
                                    <?php echo $edit_product ? 'Edit Product' : 'Add New Product'; ?>
                                </div>
                                <div class="card-body">
                                    <form method="POST" action="">
                                        <div class="mb-3">
                                            <label class="form-label">Product ID</label>
                                            <input type="text" name="id" class="form-control" 
                                                   value="<?php echo $edit_product ? htmlspecialchars($edit_product['id']) : ''; ?>" 
                                                   <?php echo $edit_product ? 'readonly' : 'required'; ?>>
                                        </div>
                                        <div class="mb-3">
                                            <label class="form-label">Product Name</label>
                                            <input type="text" name="nama_produk" class="form-control" 
                                                   value="<?php echo $edit_product ? htmlspecialchars($edit_product['nama_produk']) : ''; ?>" 
                                                   required>
                                        </div>
                                        <div class="mb-3">
                                            <label class="form-label">Description</label>
                                            <textarea name="deskripsi" class="form-control"><?php echo $edit_product ? htmlspecialchars($edit_product['deskripsi']) : ''; ?></textarea>
                                        </div>
                                        <div class="mb-3">
                                            <label class="form-label">Price</label>
                                            <input type="number" step="0.01" name="harga" class="form-control" 
                                                   value="<?php echo $edit_product ? htmlspecialchars($edit_product['harga']) : ''; ?>" 
                                                   required>
                                        </div>
                                        <div class="mb-3">
                                            <label class="form-label">Category</label>
                                            <input type="text" name="kategori" class="form-control" 
                                                   value="<?php echo $edit_product ? htmlspecialchars($edit_product['kategori']) : ''; ?>">
                                        </div>
                                        
                                        <?php if($edit_product): ?>
                                            <button type="submit" name="update_product" class="btn btn-primary">Update Product</button>
                                            <a href="<?php echo $_SERVER['PHP_SELF']; ?>" class="btn btn-secondary">Cancel</a>
                                        <?php else: ?>
                                            <button type="submit" name="add_product" class="btn btn-success">Add Product</button>
                                        <?php endif; ?>
                                    </form>
                                </div>
                            </div>
                        </div>
                        
                        <!-- Product List -->
                        <div class="col-md-8">
                            <div class="card">
                                <div class="card-header">Product List</div>
                                <div class="card-body">
                                    <div class="table-responsive">
                                        <table class="table table-striped table-hover">
                                            <thead class="table-dark">
                                                <tr>
                                                    <th>ID</th>
                                                    <th>Name</th>
                                                    <th>Description</th>
                                                    <th>Price</th>
                                                    <th>Category</th>
                                                    <th>Actions</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                <?php foreach($products as $product): ?>
                                                <tr>
                                                    <td><?php echo htmlspecialchars($product['id']); ?></td>
                                                    <td><?php echo htmlspecialchars($product['nama_produk']); ?></td>
                                                    <td><?php echo htmlspecialchars($product['deskripsi'] ?? '-'); ?></td>
                                                    <td>Rp. <?php echo number_format($product['harga'], 2); ?></td>
                                                    <td><?php echo htmlspecialchars($product['kategori'] ?? '-'); ?></td>
                                                    <td>
                                                        <div class="btn-group" role="group">
                                                            <a href="?edit_id=<?php echo $product['id']; ?>" class="btn btn-sm btn-warning">
                                                                <i class="bi bi-pencil"></i>
                                                            </a>
                                                            <a href="?delete_id=<?php echo $product['id']; ?>" class="btn btn-sm btn-danger" onclick="return confirm('Are you sure?')">
                                                                <i class="bi bi-trash"></i>
                                                            </a>
                                                        </div>
                                                    </td>
                                                </tr>
                                                <?php endforeach; ?>
                                            </tbody>
                                        </table>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Bootstrap JS and Popper.js -->
<script src="https://cdn.jsdelivr.net/npm/@popperjs/core@2.11.6/dist/umd/popper.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.2.3/dist/js/bootstrap.min.js"></script>
<!-- Bootstrap Icons -->
<link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css" rel="stylesheet">
</body>
</html>