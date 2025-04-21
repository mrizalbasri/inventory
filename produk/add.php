<?php
// Sertakan berkas koneksi basis data Anda
require_once '../config/database.php';

// Mulai sesi untuk pesan
session_start();

// Fungsi untuk mendapatkan semua kategori untuk dropdown
function getCategories($database) {
    $stmt = $database->prepare("SELECT DISTINCT kategori FROM produk WHERE kategori IS NOT NULL AND kategori != '' ORDER BY kategori");
    $stmt->execute();
    return $stmt->fetchAll(PDO::FETCH_COLUMN);
}

// Tangani penyerahan formulir
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Dapatkan data formulir
    $nama_produk = trim($_POST['nama_produk']);
    $deskripsi = trim($_POST['deskripsi']);
    $harga = floatval($_POST['harga']);
    $kategori = trim($_POST['kategori']);
    
    // Periksa jika kategori baru dipilih
    if ($kategori === 'lainnya' && !empty($_POST['kategori_baru'])) {
        $kategori = trim($_POST['kategori_baru']);
    }
    
    // Validasi masukan
    $errors = [];
    if (empty($nama_produk)) {
        $errors[] = "Nama produk harus diisi.";
    }
    if ($harga <= 0) {
        $errors[] = "Harga harus lebih besar dari 0.";
    }
    
    // Jika tidak ada kesalahan, lanjutkan dengan insert
    if (empty($errors)) {
        try {
            $stmt = $database->prepare("INSERT INTO produk (nama_produk, deskripsi, harga, kategori, created_at) VALUES (?, ?, ?, ?, NOW())");
            $result = $stmt->execute([$nama_produk, $deskripsi, $harga, $kategori]);
            
            if ($result) {
                $_SESSION['success_message'] = "Produk baru berhasil ditambahkan!";
                header("Location: index.php");
                exit;
            } else {
                $errors[] = "Gagal menambahkan produk.";
            }
        } catch (PDOException $e) {
            $errors[] = "Kesalahan: " . $e->getMessage();
        }
    }
}

// Dapatkan kategori untuk dropdown
$categories = getCategories($database);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Tambah Produk Baru</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.2.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css">
    <style>
        :root {
            --warna-utama: #4e73df;
            --warna-sekunder: #858796;
            --warna-sukses: #1cc88a;
            --warna-info: #36b9cc;
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
            background-color: var(--warna-utama);
            border-color: var(--warna-utama);
        }
        
        .btn-secondary {
            background-color: var(--warna-sekunder);
            border-color: var(--warna-sekunder);
        }
        
        .btn-success {
            background-color: var(--warna-sukses);
            border-color: var(--warna-sukses);
        }
        
        .form-control:focus {
            border-color: #bac8f3;
            box-shadow: 0 0 0 0.2rem rgba(78, 115, 223, 0.25);
        }
        
        /* Responsif seluler */
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
                    <h1 class="h3 mb-0">Tambah Produk Baru</h1>
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
                        <h6 class="m-0 font-weight-bold text-primary"><i class="bi bi-plus-circle me-1"></i> Formulir Tambah Produk</h6>
                    </div>
                    <div class="card-body">
                        <form method="POST" action="">
                            <div class="mb-3">
                                <label for="nama_produk" class="form-label">Nama Produk <span class="text-danger">*</span></label>
                                <input type="text" class="form-control" id="nama_produk" name="nama_produk" required value="<?php echo isset($nama_produk) ? htmlspecialchars($nama_produk) : ''; ?>">
                            </div>
                            
                            <div class="mb-3">
                                <label for="deskripsi" class="form-label">Deskripsi Produk</label>
                                <textarea class="form-control" id="deskripsi" name="deskripsi" rows="4"><?php echo isset($deskripsi) ? htmlspecialchars($deskripsi) : ''; ?></textarea>
                            </div>
                            
                            <div class="mb-3">
                                <label for="harga" class="form-label">Harga <span class="text-danger">*</span></label>
                                <div class="input-group">
                                    <span class="input-group-text">Rp</span>
                                    <input type="number" class="form-control" id="harga" name="harga" step="0.01" min="0" required value="<?php echo isset($harga) ? htmlspecialchars($harga) : ''; ?>">
                                </div>
                            </div>
                            
                            <div class="mb-3">
                                <label for="kategori" class="form-label">Kategori</label>
                                <select class="form-select" id="kategori" name="kategori">
                                    <option value="">-- Pilih Kategori --</option>
                                    <?php foreach ($categories as $cat): ?>
                                        <option value="<?php echo htmlspecialchars($cat); ?>" <?php echo (isset($kategori) && $kategori == $cat) ? 'selected' : ''; ?>>
                                            <?php echo htmlspecialchars($cat); ?>
                                        </option>
                                    <?php endforeach; ?>
                                    <option value="lainnya" <?php echo (isset($kategori) && $kategori == 'lainnya') ? 'selected' : ''; ?>>Kategori Lainnya</option>
                                </select>
                            </div>
                            
                            <div class="mb-3" id="kategori_baru_container" style="display: <?php echo (isset($kategori) && $kategori == 'lainnya') ? 'block' : 'none'; ?>;">
                                <label for="kategori_baru" class="form-label">Kategori Baru</label>
                                <input type="text" class="form-control" id="kategori_baru" name="kategori_baru" value="<?php echo isset($_POST['kategori_baru']) ? htmlspecialchars($_POST['kategori_baru']) : ''; ?>">
                            </div>
                            
                            <div class="d-grid gap-2 d-md-flex justify-content-md-end">
                                <button type="reset" class="btn btn-secondary me-md-2">
                                    <i class="bi bi-x-circle me-1"></i> Reset
                                </button>
                                <button type="submit" class="btn btn-primary">
                                    <i class="bi bi-save me-1"></i> Simpan Produk
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            </main>
        </div>
    </div>

    <!-- JavaScript -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.2.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Handle mobile menu toggle
            const mobileMenuBtn = document.querySelector('.mobile-only .btn');
            const sidebar = document.querySelector('.sidebar');
            
            if (mobileMenuBtn && sidebar) {
                mobileMenuBtn.addEventListener('click', function(e) {
                    e.preventDefault();
                    sidebar.style.display = sidebar.style.display === 'block' ? 'none' : 'block';
                });
            }
            
            // Handle "Kategori Lainnya" selection
            const kategoriSelect = document.getElementById('kategori');
            const kategoriBaru = document.getElementById('kategori_baru_container');
            
            if (kategoriSelect && kategoriBaru) {
                kategoriSelect.addEventListener('change', function() {
                    if (this.value === 'lainnya') {
                        kategoriBaru.style.display = 'block';
                    } else {
                        kategoriBaru.style.display = 'none';
                    }
                });
            }
        });
    </script>
</body>
</html>