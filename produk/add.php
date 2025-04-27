<?php

// Start session for messages
session_start();

// Include your database connection file
require_once '../config/database.php';

// Include your Product class file
require_once 'Product.php';

$productManager = new Product();
$categories = $productManager->getCategories();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nama_produk = $_POST['nama_produk'];
    $deskripsi = $_POST['deskripsi'];
    $harga = (float)$_POST['harga'];
    $kategori = $_POST['kategori'];
    
    $errors = $productManager->validateProductData($nama_produk, $harga);
    
    if (empty($errors)) {
        if ($productManager->addProduct($nama_produk, $deskripsi, $harga, $kategori)) {
            header("Location: index.php");
            exit;
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Tambah Produk</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.2.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css">
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
            background-color: #f8f9fc;
            color: #5a5c69;
            font-family: 'Nunito', -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, 'Helvetica Neue', Arial, sans-serif;
        }
        
        .sidebar {
            height: 100vh;
            position: fixed;
            left: 0;
            top: 0;
            width: 250px;
            z-index: 100;
        }
        
        .main-content {
            margin-left: 250px;
            padding: 25px;
        }
        
        .card {
            border: none;
            border-radius: 0.35rem;
            box-shadow: 0 0.15rem 1.75rem 0 rgba(58, 59, 69, 0.15);
        }
        
        .card-header {
            background-color: #f8f9fc;
            border-bottom: 1px solid #e3e6f0;
        }
        
        .btn-primary {
            background-color: var(--primary-color);
            border-color: var(--primary-color);
        }
        
        .btn-secondary {
            background-color: var(--secondary-color);
            border-color: var(--secondary-color);
        }
        
        .btn-success {
            background-color: var(--success-color);
            border-color: var(--success-color);
        }
        
        .form-control:focus {
            border-color: #bac8f3;
            box-shadow: 0 0 0 0.2rem rgba(78, 115, 223, 0.25);
        }
        
        /* Mobile responsive */
        @media (max-width: 768px) {
            .sidebar {
                display: none;
            }
            
            .main-content {
                margin-left: 0;
            }
            
            .mobile-only {
                display: block !important;
            }
        }
        
        .mobile-only {
            display: none;
        }
    </style>
</head>
<body>
    <!-- Mobile Menu Toggle -->
    <div class="mobile-only mt-3 ms-3">
        <a href="#" class="btn btn-primary">
            <i class="bi bi-list"></i> Menu
        </a>
    </div>

    <div class="container-fluid">
        <div class="row">
            <!-- Sidebar -->
            <?php include '../include/sidebar.php'; ?>
            
            <!-- Main Content -->
            <main class="col main-content">
                <div class="d-sm-flex align-items-center justify-content-between mb-4">
                    <h1 class="h3 mb-0">Tambah Produk</h1>
                    <a href="index.php" class="btn btn-secondary">
                        <i class="bi bi-arrow-left me-1"></i> Kembali
                    </a>
                </div>

                <!-- Error Messages -->
                <?php if(!empty($errors)): ?>
                    <div class="alert alert-danger alert-dismissible fade show" role="alert">
                        <ul class="mb-0">
                            <?php foreach($errors as $error): ?>
                                <li><?php echo $error; ?></li>
                            <?php endforeach; ?>
                        </ul>
                        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                    </div>
                <?php endif; ?>

                <!-- Add Product Form -->
                <div class="card shadow mb-4">
                    <div class="card-header py-3">
                        <h6 class="m-0 font-weight-bold text-primary"><i class="bi bi-plus-circle me-1"></i> Form Tambah Produk</h6>
                    </div>
                    <div class="card-body">
                        <form method="POST" action="">
                            <div class="mb-3">
                                <label for="nama_produk" class="form-label">Nama Produk <span class="text-danger">*</span></label>
                                <input type="text" class="form-control" id="nama_produk" name="nama_produk" value="<?php echo isset($_POST['nama_produk']) ? htmlspecialchars($_POST['nama_produk']) : ''; ?>" required>
                            </div>
                            
                            <div class="mb-3">
                                <label for="deskripsi" class="form-label">Deskripsi</label>
                                <textarea class="form-control" id="deskripsi" name="deskripsi" rows="4"><?php echo isset($_POST['deskripsi']) ? htmlspecialchars($_POST['deskripsi']) : ''; ?></textarea>
                            </div>
                            
                            <div class="mb-3">
                                <label for="harga" class="form-label">Harga (Rp) <span class="text-danger">*</span></label>
                                <input type="number" class="form-control" id="harga" name="harga" min="0" step="1000" value="<?php echo isset($_POST['harga']) ? htmlspecialchars($_POST['harga']) : '0'; ?>" required>
                            </div>
                            
                            <div class="mb-3">
                                <label for="kategori" class="form-label">Kategori</label>
                                <select class="form-select" id="kategori" name="kategori">
                                    <option value="">-- Pilih Kategori --</option>
                                    <?php foreach ($categories as $category): ?>
                                        <option value="<?php echo htmlspecialchars($category); ?>" <?php echo (isset($_POST['kategori']) && $_POST['kategori'] === $category) ? 'selected' : ''; ?>>
                                            <?php echo htmlspecialchars($category); ?>
                                        </option>
                                    <?php endforeach; ?>
                                    <option value="lainnya" <?php echo (isset($_POST['kategori']) && $_POST['kategori'] === 'lainnya') ? 'selected' : ''; ?>>Lainnya</option>
                                </select>
                            </div>
                            
                            <div class="mb-3" id="other_category" style="display: none;">
                                <label for="custom_category" class="form-label">Kategori Lainnya</label>
                                <input type="text" class="form-control" id="custom_category" name="custom_category" value="<?php echo isset($_POST['custom_category']) ? htmlspecialchars($_POST['custom_category']) : ''; ?>">
                            </div>
                            
                            <div class="d-grid gap-2 d-md-flex justify-content-md-end">
                                <button type="submit" class="btn btn-primary">
                                    <i class="bi bi-save me-1"></i> Simpan Produk
                                </button>
                                <a href="index.php" class="btn btn-secondary">
                                    <i class="bi bi-x-circle me-1"></i> Batal
                                </a>
                            </div>
                        </form>
                    </div>
                </div>
            </main>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.2.3/dist/js/bootstrap.bundle.min.js"></script>
   
</body>
</html>