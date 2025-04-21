<?php
// Include your database connection file
require_once '../config/database.php';

// Start session for messages
session_start();

// Function to retrieve all products with optional filtering
function getAllProducts($database) {
    $query = "SELECT * FROM produk";
    $params = [];
    
    // Search functionality
    if (isset($_GET['search']) && !empty($_GET['search'])) {
        $search = $_GET['search'];
        $query .= " WHERE nama_produk LIKE ? OR deskripsi LIKE ? OR kategori LIKE ?";
        $params = ["%$search%", "%$search%", "%$search%"];
    }
    
    // Category filter
    if (isset($_GET['filter_kategori']) && !empty($_GET['filter_kategori'])) {
        if (strpos($query, 'WHERE') !== false) {
            $query .= " AND kategori = ?";
        } else {
            $query .= " WHERE kategori = ?";
        }
        $params[] = $_GET['filter_kategori'];
    }
    
    // Sort functionality
    $sort_by = isset($_GET['sort']) ? $_GET['sort'] : 'created_at';
    $sort_order = isset($_GET['dir']) ? $_GET['dir'] : 'DESC';
    
    $allowed_sort_fields = ['id', 'nama_produk', 'harga', 'kategori', 'created_at'];
    $allowed_sort_orders = ['ASC', 'DESC'];
    
    if (!in_array($sort_by, $allowed_sort_fields)) {
        $sort_by = 'created_at';
    }
    
    if (!in_array($sort_order, $allowed_sort_orders)) {
        $sort_order = 'DESC';
    }
    
    $query .= " ORDER BY $sort_by $sort_order";
    
    // Pagination
    $records_per_page = 10;
    $page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
    $offset = ($page - 1) * $records_per_page;
    
    $query .= " LIMIT $offset, $records_per_page";
    
    $stmt = $database->prepare($query);
    $stmt->execute($params);
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

// Function to count total products for pagination
function countTotalProducts($database) {
    $query = "SELECT COUNT(*) FROM produk";
    $params = [];
    
    // Search functionality
    if (isset($_GET['search']) && !empty($_GET['search'])) {
        $search = $_GET['search'];
        $query .= " WHERE nama_produk LIKE ? OR deskripsi LIKE ? OR kategori LIKE ?";
        $params = ["%$search%", "%$search%", "%$search%"];
    }
    
    // Category filter
    if (isset($_GET['filter_kategori']) && !empty($_GET['filter_kategori'])) {
        if (strpos($query, 'WHERE') !== false) {
            $query .= " AND kategori = ?";
        } else {
            $query .= " WHERE kategori = ?";
        }
        $params[] = $_GET['filter_kategori'];
    }
    
    $stmt = $database->prepare($query);
    $stmt->execute($params);
    return $stmt->fetchColumn();
}

// Function to get unique categories
function getCategories($database) {
    $stmt = $database->prepare("SELECT DISTINCT kategori FROM produk WHERE kategori IS NOT NULL AND kategori != '' ORDER BY kategori");
    $stmt->execute();
    return $stmt->fetchAll(PDO::FETCH_COLUMN);
}

// Function to get product by ID
function getProductById($database, $id) {
    $stmt = $database->prepare("SELECT * FROM produk WHERE id = ?");
    $stmt->execute([$id]);
    return $stmt->fetch(PDO::FETCH_ASSOC);
}

// Handle product deletion
if (isset($_POST['delete_product'])) {
    $id = $_POST['product_id'];
    
    $stmt = $database->prepare("DELETE FROM produk WHERE id = ?");
    
    try {
        $result = $stmt->execute([$id]);
        
        if ($result) {
            $_SESSION['success_message'] = "Produk berhasil dihapus!";
        } else {
            $_SESSION['error_message'] = "Gagal menghapus produk.";
        }
    } catch(PDOException $e) {
        $_SESSION['error_message'] = "Error: " . $e->getMessage();
    }
    
    // Redirect to avoid form resubmission
    header("Location: " . $_SERVER['PHP_SELF']);
    exit();
}

// Get categories for filter dropdown
$categories = getCategories($database);

// Get total records for pagination
$total_records = countTotalProducts($database);
$records_per_page = 10;
$total_pages = ceil($total_records / $records_per_page);
$current_page = isset($_GET['page']) ? max(1, min($total_pages, intval($_GET['page']))) : 1;

// Retrieve products with filters
$products = getAllProducts($database);

// Get current sort parameters for column headers
$current_sort_by = isset($_GET['sort']) ? $_GET['sort'] : 'created_at';
$current_sort_order = isset($_GET['dir']) ? $_GET['dir'] : 'DESC';

// Function to generate sort URL
function getSortUrl($field) {
    $params = $_GET;
    $params['sort'] = $field;
    $params['dir'] = (isset($_GET['sort']) && $_GET['sort'] == $field && isset($_GET['dir']) && $_GET['dir'] == 'asc') ? 'desc' : 'asc';
    return '?' . http_build_query($params);
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manajemen Produk</title>
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
        
        .btn-danger {
            background-color: var(--danger-color);
            border-color: var(--danger-color);
        }
        
        .btn-success {
            background-color: var(--success-color);
            border-color: var(--success-color);
        }
        
        .btn-warning {
            background-color: var(--warning-color);
            border-color: var(--warning-color);
        }
        
        .btn-info {
            background-color: var(--info-color);
            border-color: var(--info-color);
            color: white;
        }
        
        .form-control:focus {
            border-color: #bac8f3;
            box-shadow: 0 0 0 0.2rem rgba(78, 115, 223, 0.25);
        }
        
        .table th {
            background-color: #f8f9fc;
            color: #5a5c69;
            font-weight: 700;
            border-top: none;
        }
        
        /* Pagination Styling */
        .pagination .page-item .page-link {
            color: var(--primary-color);
        }
        
        .pagination .page-item.active .page-link {
            background-color: var(--primary-color);
            border-color: var(--primary-color);
            color: white;
        }
        
        /* Alert styling */
        .alert {
            border-radius: 0.35rem;
            border: none;
        }
        
        /* Badge styling */
        .badge-category {
            font-size: 85%;
        }
        
        .price-column {
            font-weight: bold;
            color: var(--primary-color);
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
       
        /* Filter section styling */
        .filter-card {
            margin-bottom: 20px;
        }
        footer {
            font-size: 0.8rem;
            color: var(--secondary-color);
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
            <main class="col-md-10 ms-sm-auto col-lg-10 px-md-4 main-content">
                <div class="d-sm-flex align-items-center justify-content-between mb-4">
                    <h1 class="h3 mb-0">Manajemen Produk</h1>
                    <div>
                        <a href="add.php" class="btn btn-primary ">
                            <i class="bi bi-plus-lg me-1"></i> Tambah Produk Baru
                        </a>
                    </div>
                </div>

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

                <!-- Fixed Filter Section - Always Visible -->
                <div class="card shadow filter-card">
                    <div class="card-header py-3">
                        <h6 class="m-0 font-weight-bold text-primary"><i class="bi bi-funnel me-1"></i> Filter dan Pencarian</h6>
                    </div>
                    <div class="card-body">
                        <form method="GET" action="" class="row g-3">
                            <div class="col-md-4">
                                <label class="form-label">Search</label>
                                <div class="input-group">
                                    <input type="text" name="search" class="form-control" placeholder="Search by name, description, or category" value="<?php echo isset($_GET['search']) ? htmlspecialchars($_GET['search']) : ''; ?>">
                                    <button class="btn btn-outline-secondary" type="submit"><i class="bi bi-search"></i></button>
                                </div>
                            </div>
                            <div class="col-md-3">
                                <label class="form-label">Category</label>
                                <select name="filter_kategori" class="form-select">
                                    <option value="">All Categories</option>
                                    <?php foreach($categories as $category): ?>
                                        <option value="<?php echo htmlspecialchars($category); ?>" <?php echo (isset($_GET['filter_kategori']) && $_GET['filter_kategori'] == $category) ? 'selected' : ''; ?>>
                                            <?php echo htmlspecialchars($category); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="col-md-3">
                                <label class="form-label">Sort By</label>
                                <select name="sort" class="form-select">
                                    <option value="created_at" <?php echo $current_sort_by == 'created_at' ? 'selected' : ''; ?>>Date Added</option>
                                    <option value="nama_produk" <?php echo $current_sort_by == 'nama_produk' ? 'selected' : ''; ?>>Name</option>
                                    <option value="harga" <?php echo $current_sort_by == 'harga' ? 'selected' : ''; ?>>Price</option>
                                    <option value="kategori" <?php echo $current_sort_by == 'kategori' ? 'selected' : ''; ?>>Category</option>
                                </select>
                            </div>
                            <div class="col-md-2">
                                <label class="form-label">Order</label>
                                <select name="dir" class="form-select">
                                    <option value="ASC" <?php echo $current_sort_order == 'ASC' ? 'selected' : ''; ?>>Ascending</option>
                                    <option value="DESC" <?php echo $current_sort_order == 'DESC' ? 'selected' : ''; ?>>Descending</option>
                                </select>
                            </div>
                            <div class="col-md-12 text-end">
                                <button type="submit" class="btn btn-primary"><i class="bi bi-filter"></i> Apply Filters</button>
                                <a href="<?php echo $_SERVER['PHP_SELF']; ?>" class="btn btn-outline-secondary"><i class="bi bi-x-circle"></i> Clear Filters</a>
                            </div>
                        </form>
                    </div>
                </div>

                <!-- Product List Card -->
                <div class="card shadow mb-4">
                    <div class="card-header py-3">
                        <div class="d-flex justify-content-between align-items-center">
                            <h6 class="m-0 font-weight-bold text-primary"><i class="bi bi-list-ul me-1"></i> Daftar Produk</h6>
                            <span class="badge bg-primary"><?php echo $total_records; ?> Produk</span>
                        </div>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-striped table-hover">
                                <thead>
                                    <tr>
                                        <th>
                                            <a href="<?php echo getSortUrl('id'); ?>" class="text-decoration-none">
                                                ID <i class="bi bi-arrow-down-up"></i>
                                            </a>
                                        </th>
                                        <th>
                                            <a href="<?php echo getSortUrl('nama_produk'); ?>" class="text-decoration-none">
                                                Nama <i class="bi bi-arrow-down-up"></i>
                                            </a>
                                        </th>
                                        <th>Deskripsi</th>
                                        <th>
                                            <a href="<?php echo getSortUrl('harga'); ?>" class="text-decoration-none">
                                                Harga <i class="bi bi-arrow-down-up"></i>
                                            </a>
                                        </th>
                                        <th>
                                            <a href="<?php echo getSortUrl('kategori'); ?>" class="text-decoration-none">
                                                Kategori <i class="bi bi-arrow-down-up"></i>
                                            </a>
                                        </th>
                                        <th class="text-center">Aksi</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php if(count($products) > 0): ?>
                                        <?php foreach($products as $product): ?>
                                        <tr>
                                            <td><?php echo htmlspecialchars($product['id']); ?></td>
                                            <td><?php echo htmlspecialchars($product['nama_produk']); ?></td>
                                            <td>
                                                <?php 
                                                $description = htmlspecialchars($product['deskripsi'] ?? '-');
                                                echo (strlen($description) > 50) ? substr($description, 0, 50) . '...' : $description; 
                                                ?>
                                            </td>
                                            <td class="price-column">Rp. <?php echo number_format($product['harga'], 2); ?></td>
                                            <td>
                                                <?php 
                                                $label = "secondary";
                                                if (!empty($product['kategori'])) {
                                                    switch(strtolower($product['kategori'])) {
                                                        case 'elektronik':
                                                            $label = "primary";
                                                            break;
                                                        case 'aksesoris':
                                                            $label = "success";
                                                            break;
                                                        case 'jaringan':
                                                            $label = "warning";
                                                            break;
                                                        case 'gadget':
                                                            $label = "info";
                                                            break;
                                                    }
                                                }
                                                ?>
                                                <?php if(!empty($product['kategori'])): ?>
                                                    <span class="badge bg-<?php echo $label; ?> <?php echo ($label == 'warning') ? 'text-dark' : ''; ?> badge-category">
                                                        <?php echo htmlspecialchars($product['kategori']); ?>
                                                    </span>
                                                <?php else: ?>
                                                    <em class="text-muted">-</em>
                                                <?php endif; ?>
                                            </td>
                                            <td class="text-center">
                                                <div class="btn-group" role="group">
                                                    <a href="edit.php?id=<?php echo $product['id']; ?>" class="btn btn-sm btn-warning me-1" title="Edit Produk">
                                                        <i class="bi bi-pencil"></i>
                                                    </a>
                                                    <form method="POST" class="d-inline" onsubmit="return confirm('Apakah Anda yakin ingin menghapus produk ini?');">
                                                        <input type="hidden" name="product_id" value="<?php echo $product['id']; ?>">
                                                        <button type="submit" name="delete_product" class="btn btn-sm btn-danger">
                                                            <i class="bi bi-trash"></i>
                                                        </button>
                                                    </form>
                                                </div>
                                            </td>
                                        </tr>
                                        <?php endforeach; ?>
                                    <?php else: ?>
                                        <tr>
                                            <td colspan="6" class="text-center">Tidak ada produk ditemukan.</td>
                                        </tr>
                                    <?php endif; ?>
                                </tbody>
                            </table>
                        </div>
                        
                        <!-- Pagination -->
                        <?php if ($total_pages > 1): ?>
                        <nav aria-label="Page navigation" class="mt-4">
                            <ul class="pagination justify-content-center">
                                <li class="page-item <?php echo ($current_page <= 1) ? 'disabled' : ''; ?>">
                                    <a class="page-link" href="?page=<?php echo $current_page - 1; ?>&sort=<?php echo $current_sort_by; ?>&dir=<?php echo $current_sort_order; ?><?php echo isset($_GET['search']) ? '&search='.$_GET['search'] : ''; ?><?php echo isset($_GET['filter_kategori']) ? '&filter_kategori='.$_GET['filter_kategori'] : ''; ?>" tabindex="-1" <?php echo ($current_page <= 1) ? 'aria-disabled="true"' : ''; ?>>Previous</a>
                                </li>
                                
                                <?php for ($i = 1; $i <= $total_pages; $i++): ?>
                                <li class="page-item <?php echo ($current_page == $i) ? 'active' : ''; ?>">
                                    <a class="page-link" href="?page=<?php echo $i; ?>&sort=<?php echo $current_sort_by; ?>&dir=<?php echo $current_sort_order; ?><?php echo isset($_GET['search']) ? '&search='.$_GET['search'] : ''; ?><?php echo isset($_GET['filter_kategori']) ? '&filter_kategori='.$_GET['filter_kategori'] : ''; ?>"><?php echo $i; ?></a>
                                </li>
                                <?php endfor; ?>
                                
                                <li class="page-item <?php echo ($current_page >= $total_pages) ? 'disabled' : ''; ?>">
                                    <a class="page-link" href="?page=<?php echo $current_page + 1; ?>&sort=<?php echo $current_sort_by; ?>&dir=<?php echo $current_sort_order; ?><?php echo isset($_GET['search']) ? '&search='.$_GET['search'] : ''; ?><?php echo isset($_GET['filter_kategori']) ? '&filter_kategori='.$_GET['filter_kategori'] : ''; ?>">Next</a>
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

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.2.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>