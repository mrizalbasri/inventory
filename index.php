<?php
// Start the session
session_start();



// Include your existing database connection file
require_once 'config/database.php';

// Get total products
$stmt = $database->query("SELECT COUNT(*) as total FROM produk");
$total_products = $stmt->fetch(PDO::FETCH_ASSOC)['total'];

// Get total stock value
$stmt = $database->query("SELECT SUM(p.harga * s.jumlah_stok) as total_value 
                    FROM produk p 
                    JOIN stok s ON p.id = s.produk_id");
$total_stock_value = $stmt->fetch(PDO::FETCH_ASSOC)['total_value'];

// Get low stock items (less than 20)
$stmt = $database->query("SELECT COUNT(*) as total FROM stok WHERE jumlah_stok < 20");
$low_stock_count = $stmt->fetch(PDO::FETCH_ASSOC)['total'];

// Get transactions for last 7 days
$stmt = $database->query("SELECT COUNT(*) as total FROM transaksi 
                    WHERE tanggal_transaksi >= DATE_SUB(NOW(), INTERVAL 7 DAY)");
$recent_transactions = $stmt->fetch(PDO::FETCH_ASSOC)['total'];

// Get top 5 products by stock value
$stmt = $database->query("SELECT p.nama_produk, p.harga, s.jumlah_stok, (p.harga * s.jumlah_stok) as total_value 
                    FROM produk p 
                    JOIN stok s ON p.id = s.produk_id 
                    ORDER BY total_value DESC 
                    LIMIT 5");
$top_products = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Get latest transactions
$stmt = $database->query("SELECT t.id, p.nama_produk, t.jenis_transaksi, t.jumlah, t.tanggal_transaksi, u.username 
                    FROM transaksi t 
                    JOIN produk p ON t.produk_id = p.id 
                    LEFT JOIN users u ON t.created_by = u.id 
                    ORDER BY t.tanggal_transaksi DESC 
                    LIMIT 5");
$latest_transactions = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Get stock movements (in vs out)
$stmt = $database->query("SELECT SUM(jumlah) as total_in FROM transaksi WHERE jenis_transaksi = 'masuk'");
$total_stock_in = $stmt->fetch(PDO::FETCH_ASSOC)['total_in'];

$stmt = $database->query("SELECT SUM(jumlah) as total_out FROM transaksi WHERE jenis_transaksi = 'keluar'");
$total_stock_out = $stmt->fetch(PDO::FETCH_ASSOC)['total_out'];

// Get stock by category
$stmt = $database->query("SELECT p.kategori, COUNT(*) as count, SUM(s.jumlah_stok) as total_stock 
                         FROM produk p 
                         JOIN stok s ON p.id = s.produk_id 
                         GROUP BY p.kategori");
$categories = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard - Inventory System</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css">
    <style>
        .card {
            transition: transform 0.3s, box-shadow 0.3s;
            border-radius: 10px;
            border: none;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
        }
        
        .card:hover {
            transform: translateY(-5px);
            box-shadow: 0 10px 15px rgba(0, 0, 0, 0.1);
        }
        
        .card-header {
            border-radius: 10px 10px 0 0 !important;
            font-weight: bold;
        }
        
        .stat-card .bi {
            font-size: 2rem;
            opacity: 0.8;
        }
        
        .progress {
            height: 10px;
            border-radius: 5px;
        }
        
        .table-hover tbody tr:hover {
            background-color: rgba(0, 123, 255, 0.05);
        }
        
        .text-primary {
            color: #007bff !important;
        }
        
        .bg-gradient-primary {
            background: linear-gradient(45deg, #007bff, #5eb5ff);
        }
        
        .bg-gradient-success {
            background: linear-gradient(45deg, #28a745, #5dd879);
        }
        
        .bg-gradient-warning {
            background: linear-gradient(45deg, #ffc107, #ffe083);
        }
        
        .bg-gradient-danger {
            background: linear-gradient(45deg, #dc3545, #ff8a97);
        }
        
        .dashboard-title {
            font-weight: 700;
            color: #343a40;
            border-left: 5px solid #007bff;
            padding-left: 15px;
        }
        
        .welcome-section {
            background-color: #f8f9fa;
            border-radius: 10px;
            padding: 20px;
            margin-bottom: 20px;
            border-left: 5px solid #007bff;
        }
    </style>
</head>
<body>

    <div class="container-fluid">
        <div class="row">
            <!-- Include sidebar -->
            <?php include 'include/sidebar.php'; ?>
            
            <!-- Main Content -->
            <main class="col-md-9 ms-sm-auto col-lg-10 px-md-4 py-4">
                <div class="welcome-section">
                    <h1 class="dashboard-title">Dashboard</h1>
                    <p class="text-muted">Welcome back, <?php echo isset($_SESSION['username']) ? $_SESSION['username'] : 'User'; ?>! Here's what's happening with your inventory today.</p>
                </div>
                
                <!-- Stats Cards -->
                <div class="row mb-4">
                    <div class="col-md-3 mb-3">
                        <div class="card stat-card text-white bg-gradient-primary">
                            <div class="card-body d-flex justify-content-between align-items-center">
                                <div>
                                    <h6 class="card-title mb-0">Total Products</h6>
                                    <h2 class="mb-0"><?php echo number_format($total_products); ?></h2>
                                </div>
                                <i class="bi bi-box-seam"></i>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3 mb-3">
                        <div class="card stat-card text-white bg-gradient-success">
                            <div class="card-body d-flex justify-content-between align-items-center">
                                <div>
                                    <h6 class="card-title mb-0">Stock Value</h6>
                                    <h2 class="mb-0">Rp <?php echo number_format($total_stock_value); ?></h2>
                                </div>
                                <i class="bi bi-cash-stack"></i>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3 mb-3">
                        <div class="card stat-card text-white bg-gradient-warning">
                            <div class="card-body d-flex justify-content-between align-items-center">
                                <div>
                                    <h6 class="card-title mb-0">Low Stock Items</h6>
                                    <h2 class="mb-0"><?php echo number_format($low_stock_count); ?></h2>
                                </div>
                                <i class="bi bi-exclamation-triangle"></i>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3 mb-3">
                        <div class="card stat-card text-white bg-gradient-danger">
                            <div class="card-body d-flex justify-content-between align-items-center">
                                <div>
                                    <h6 class="card-title mb-0">Recent Transactions</h6>
                                    <h2 class="mb-0"><?php echo number_format($recent_transactions); ?></h2>
                                </div>
                                <i class="bi bi-arrow-left-right"></i>
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- Main Content Row -->
                <div class="row mb-4">
                    <!-- Top Products by Value -->
                    <div class="col-md-6 mb-4">
                        <div class="card h-100">
                            <div class="card-header bg-white">
                                <h5 class="card-title mb-0">Top Products by Value</h5>
                            </div>
                            <div class="card-body">
                                <div class="table-responsive">
                                    <table class="table table-hover">
                                        <thead>
                                            <tr>
                                                <th>Product</th>
                                                <th>Price</th>
                                                <th>Stock</th>
                                                <th>Total Value</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php foreach($top_products as $product): ?>
                                            <tr>
                                                <td><?php echo htmlspecialchars($product['nama_produk']); ?></td>
                                                <td>Rp <?php echo number_format($product['harga']); ?></td>
                                                <td><?php echo number_format($product['jumlah_stok']); ?></td>
                                                <td>Rp <?php echo number_format($product['total_value']); ?></td>
                                            </tr>
                                            <?php endforeach; ?>
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Latest Transactions -->
                    <div class="col-md-6 mb-4">
                        <div class="card h-100">
                            <div class="card-header bg-white">
                                <h5 class="card-title mb-0">Latest Transactions</h5>
                            </div>
                            <div class="card-body">
                                <div class="table-responsive">
                                    <table class="table table-hover">
                                        <thead>
                                            <tr>
                                                <th>Product</th>
                                                <th>Type</th>
                                                <th>Quantity</th>
                                                <th>Date</th>
                                                <th>User</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php foreach($latest_transactions as $transaction): ?>
                                            <tr>
                                                <td><?php echo htmlspecialchars($transaction['nama_produk']); ?></td>
                                                <td>
                                                    <?php if($transaction['jenis_transaksi'] == 'masuk'): ?>
                                                        <span class="badge bg-success">In</span>
                                                    <?php else: ?>
                                                        <span class="badge bg-danger">Out</span>
                                                    <?php endif; ?>
                                                </td>
                                                <td><?php echo number_format($transaction['jumlah']); ?></td>
                                                <td><?php echo date('d M Y', strtotime($transaction['tanggal_transaksi'])); ?></td>
                                                <td><?php echo htmlspecialchars($transaction['username'] ?? 'Unknown'); ?></td>
                                            </tr>
                                            <?php endforeach; ?>
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- Third Row -->
                <div class="row mb-4">
                    <!-- Stock In vs Stock Out -->
                    <div class="col-md-6 mb-4">
                        <div class="card h-100">
                            <div class="card-header bg-white">
                                <h5 class="card-title mb-0">Stock Movement Overview</h5>
                            </div>
                            <div class="card-body">
                                <div class="d-flex justify-content-between mb-4">
                                    <div class="text-center">
                                        <h5>Total Stock In</h5>
                                        <h3 class="text-success"><?php echo number_format($total_stock_in); ?> units</h3>
                                    </div>
                                    <div class="text-center">
                                        <h5>Total Stock Out</h5>
                                        <h3 class="text-danger"><?php echo number_format($total_stock_out); ?> units</h3>
                                    </div>
                                </div>
                                
                                <h6 class="mb-2">Stock Movement Ratio</h6>
                                <div class="progress mb-3">
                                    <?php 
                                    $total = $total_stock_in + $total_stock_out;
                                    $in_percentage = ($total > 0) ? ($total_stock_in / $total) * 100 : 0;
                                    ?>
                                    <div class="progress-bar bg-success" role="progressbar" style="width: <?php echo $in_percentage; ?>%" 
                                         aria-valuenow="<?php echo $in_percentage; ?>" aria-valuemin="0" aria-valuemax="100">
                                        <?php echo round($in_percentage); ?>% In
                                    </div>
                                    <div class="progress-bar bg-danger" role="progressbar" style="width: <?php echo 100 - $in_percentage; ?>%" 
                                         aria-valuenow="<?php echo 100 - $in_percentage; ?>" aria-valuemin="0" aria-valuemax="100">
                                        <?php echo round(100 - $in_percentage); ?>% Out
                                    </div>
                                </div>
                                
                                <div class="d-flex justify-content-between align-items-center">
                                    <span class="text-success">Stock In</span>
                                    <span class="text-danger">Stock Out</span>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Stock by Category -->
                    <div class="col-md-6 mb-4">
                        <div class="card h-100">
                            <div class="card-header bg-white">
                                <h5 class="card-title mb-0">Stock by Category</h5>
                            </div>
                            <div class="card-body">
                                <div class="table-responsive">
                                    <table class="table table-hover">
                                        <thead>
                                            <tr>
                                                <th>Category</th>
                                                <th>Products</th>
                                                <th>Total Stock</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php foreach($categories as $category): ?>
                                            <tr>
                                                <td><?php echo htmlspecialchars($category['kategori'] ?? 'Uncategorized'); ?></td>
                                                <td><?php echo number_format($category['count']); ?></td>
                                                <td><?php echo number_format($category['total_stock']); ?></td>
                                            </tr>
                                            <?php endforeach; ?>
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- Footer -->
                <footer class="d-flex justify-content-between align-items-center pt-3 my-4 border-top">
                    <p class="text-muted">&copy; 2025 Inventory System</p>
                    <p class="text-muted">Last updated: <?php echo date('d M Y H:i'); ?></p>
                </footer>
            </main>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
    // Add any custom JavaScript here
    document.addEventListener('DOMContentLoaded', function() {
        // You can add interactivity here if needed
        console.log('Dashboard loaded successfully');
    });
    </script>
</body>
</html>