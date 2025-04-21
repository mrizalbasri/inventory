<?php
// Include your database connection file
require_once '../config/database.php';

// Start session for messages
session_start();

// Function to get all categories for dropdown
function getCategories($database) {
    $stmt = $database->prepare("SELECT DISTINCT kategori FROM produk WHERE kategori IS NOT NULL AND kategori != '' ORDER BY kategori");
    $stmt->execute();
    return $stmt->fetchAll(PDO::FETCH_COLUMN);
}

// Check if ID is provided
if (!isset($_GET['id']) || empty($_GET['id'])) {
    $_SESSION['error_message'] = "ID produk tidak valid.";
    header("Location: index.php");
    exit;
}

$id = intval($_GET['id']);

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Get form data
    $nama_produk = trim($_POST['nama_produk']);
    $deskripsi = trim($_POST['deskripsi']);
    $harga = floatval($_POST['harga']);
    $kategori = trim($_POST['kategori']);
    
    // Validate input
    $errors = [];
    
    if (empty($nama_produk)) {
        $errors[] = "Nama produk harus diisi.";
    }
    
    if ($harga <= 0) {
        $errors[] = "Harga harus lebih besar dari 0.";
    }
    
    // If no errors, proceed with update
    if (empty($errors)) {
        try {
            $stmt = $database->prepare("UPDATE produk SET nama_produk = ?, deskripsi = ?, harga = ?, kategori = ?, updated_at = NOW() WHERE id = ?");
            
            $result = $stmt->execute([$nama_produk, $deskripsi, $harga, $kategori, $id]);
            
            if ($result) {
                $_SESSION['success_message'] = "Produk berhasil diperbarui!";
                header("Location: index.php");
                exit;
            } else {
                $errors[] = "Gagal memperbarui produk.";
            }
        } catch (PDOException $e) {
            $errors[] = "Error: " . $e->getMessage();
        }
    }
}

// Get product data for editing
try {
    $stmt = $database->prepare("SELECT * FROM produk WHERE id = ?");
    $stmt->execute([$id]);
    $product = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$product) {
        $_SESSION['error_message'] = "Produk tidak ditemukan.";
        header("Location: index.php");
        exit;
    }
} catch (PDOException $e) {
    $_SESSION['error_message'] = "Error: " . $e->getMessage();
    header("Location: index.php");
    exit;
}

// Get categories for dropdown
$categories = getCategories($database);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Produk</title>
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
                    <h1 class="h3 mb-0">Edit Produk</h1>
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

                <!-- Edit Product Form -->
                <div class="card shadow mb-4">
                    <div class="card-header py-3">
                        <h6 class="m-0 font-weight-bold text-primary"><i class="bi bi-pencil me-1"></i> Form Edit Produk</h6>
                    </div>
                    <div class="card-body">
                        <form method="POST" action="">
                            <div class="mb-3">
                                <label for="nama_produk" class="form-label">Nama Produk <span class="text-danger">*</span></label>
                                <input type="text" class="form-control" id="nama_produk" name="nama_produk" value="<?php echo htmlspecialchars($product['nama_produk']); ?>" required>
                            </div>
                            
                            <div class="mb-3">
                                <label for="deskripsi" class="form-label">Deskripsi</label>
                                <textarea class="form-control" id="deskripsi" name="deskripsi" rows="4"><?php echo htmlspecialchars($product['deskripsi']); ?></textarea>
                            </div>
                            
                            <div class="mb-3">
                                <label for="harga" class="form-label">Harga (Rp) <span class="text-danger">*</span></label>
                                <input type="number" class="form-control" id="harga" name="harga" min="0" step="1000" value="<?php echo htmlspecialchars($product['harga']); ?>" required>
                            </div>
                            
                            <div class="mb-3">
                                <label for="kategori" class="form-label">Kategori</label>
                                <select class="form-select" id="kategori" name="kategori">
                                    <option value="">-- Pilih Kategori --</option>
                                    <?php foreach ($categories as $category): ?>
                                        <option value="<?php echo htmlspecialchars($category); ?>" <?php echo ($product['kategori'] === $category) ? 'selected' : ''; ?>>
                                            <?php echo htmlspecialchars($category); ?>
                                        </option>
                                    <?php endforeach; ?>
                                    <option value="lainnya" <?php echo (!in_array($product['kategori'], $categories) && !empty($product['kategori'])) ? 'selected' : ''; ?>>Lainnya</option>
                                </select>
                            </div>
                            
                            <div class="mb-3" id="other_category" style="display: none;">
                                <label for="custom_category" class="form-label">Kategori Lainnya</label>
                                <input type="text" class="form-control" id="custom_category" name="custom_category">
                            </div>
                            
                            <div class="d-grid gap-2 d-md-flex justify-content-md-end">
                                <button type="submit" class="btn btn-primary">
                                    <i class="bi bi-save me-1"></i> Simpan Perubahan
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
    <script>
        // Show/hide other category field
        document.getElementById('kategori').addEventListener('change', function() {
            var otherCategory = document.getElementById('other_category');
            if (this.value === 'lainnya') {
                otherCategory.style.display = 'block';
            } else {
                otherCategory.style.display = 'none';
            }
        });
        
        // Trigger the change event to set initial state
        document.getElementById('kategori').dispatchEvent(new Event('change'));
    </script>
</body>
</html>