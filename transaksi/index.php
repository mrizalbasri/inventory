<?php
// Start session if not already started
session_start();

// Database connection with PDO
require_once '../config/database.php';

// Check if user is logged in
// if (!isset($_SESSION['user_id'])) {
//     header("Location: login.php");
//     exit();
// }

// Get all transactions for display with additional information
try {
    $stmt = $database->query("SELECT t.*, p.nama_produk, p.harga, u.username 
    FROM transaksi t 
    LEFT JOIN produk p ON t.produk_id = p.id
    LEFT JOIN users u ON t.created_by = u.id 
    ORDER BY t.tanggal_transaksi DESC, t.id DESC");
    $transactions = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $error_message = "Error loading transactions: " . $e->getMessage();
}

// Calculate transaction statistics
$total_masuk = 0;
$total_keluar = 0;
$total_value_masuk = 0;
$total_value_keluar = 0;

if (isset($transactions) && count($transactions) > 0) {
    foreach ($transactions as $transaction) {
        if ($transaction['jenis_transaksi'] == 'masuk') {
            $total_masuk += $transaction['jumlah'];
            $total_value_masuk += ($transaction['jumlah'] * $transaction['harga']);
        } else {
            $total_keluar += $transaction['jumlah'];
            $total_value_keluar += ($transaction['jumlah'] * $transaction['harga']);
        }
    }
}

// Get transactions for current month
$current_month = date('m');
$current_year = date('Y');
try {
    $stmt = $database->prepare("SELECT SUM(CASE WHEN jenis_transaksi = 'masuk' THEN 1 ELSE 0 END) as count_masuk,
                              SUM(CASE WHEN jenis_transaksi = 'keluar' THEN 1 ELSE 0 END) as count_keluar
                              FROM transaksi 
                              WHERE MONTH(tanggal_transaksi) = :month AND YEAR(tanggal_transaksi) = :year");
    $stmt->bindParam(':month', $current_month);
    $stmt->bindParam(':year', $current_year);
    $stmt->execute();
    $monthly_stats = $stmt->fetch(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $error_message = "Error calculating statistics: " . $e->getMessage();
}

// Process success/error messages from other pages
if (isset($_SESSION['success_message'])) {
    $success_message = $_SESSION['success_message'];
    unset($_SESSION['success_message']);
}

if (isset($_SESSION['error_message'])) {
    $error_message = $_SESSION['error_message'];
    unset($_SESSION['error_message']);
}

// Check if we need to handle success message from add_transaksi.php
if (isset($_GET['success']) && $_GET['success'] == 1) {
    $success_message = "Transaction successfully recorded!";
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Transaction Management</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css">
    <style>
        .transactions-list {
            background-color: #fff;
            border-radius: 8px;
            box-shadow: 0 0 15px rgba(0, 0, 0, 0.1);
            padding: 20px;
            margin-bottom: 30px;
        }
        
        .stats-card {
            background-color: #fff;
            border-radius: 8px;
            box-shadow: 0 0 15px rgba(0, 0, 0, 0.1);
            padding: 20px;
            margin-bottom: 30px;
            transition: transform 0.3s;
        }
        
        .stats-card:hover {
            transform: translateY(-5px);
        }
        
        .table-responsive {
            border-radius: 5px;
            overflow: hidden;
        }
        
        .badge-masuk {
            background-color: #28a745;
            color: white;
            padding: 5px 10px;
            border-radius: 20px;
            font-size: 0.8rem;
            font-weight: bold;
        }
        
        .badge-keluar {
            background-color: #dc3545;
            color: white;
            padding: 5px 10px;
            border-radius: 20px;
            font-size: 0.8rem;
            font-weight: bold;
        }
        
        .card-masuk {
            border-left: 4px solid #28a745;
        }
        
        .card-keluar {
            border-left: 4px solid #dc3545;
        }
        
        .card-value {
            border-left: 4px solid #007bff;
        }
        
        .card-icon {
            font-size: 2.5rem;
            opacity: 0.8;
        }
        
        .action-buttons .btn {
            width: 32px;
            height: 32px;
            padding: 0;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            margin-right: 5px;
        }
        
        .transaction-filters {
            background-color: #f8f9fa;
            border-radius: 8px;
            padding: 15px;
            margin-bottom: 20px;
        }
        
        .pagination-container {
            display: flex;
            justify-content: center;
            margin-top: 20px;
        }
        
        .empty-state {
            text-align: center;
            padding: 40px 0;
        }
        
        .empty-state i {
            font-size: 3rem;
            color: #6c757d;
            margin-bottom: 20px;
        }
        footer {
            font-size: 0.8rem;
            color: var(--secondary-color);
        }
        
    </style>
</head>
<body>
    <div class="container-fluid">
        <div class="row">
            <!-- Include Sidebar -->
            <?php include '../include/sidebar.php'; ?>
            
            <!-- Main Content -->
            <main class="col-md-9 ms-sm-auto col-lg-10 px-md-4 main-content">
                <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
                    <h1 class="h2">Transaction Management</h1>
                    <div class="btn-toolbar mb-2 mb-md-0">
                        <a href="add.php" class="btn btn-sm btn-primary">
                            <i class="bi bi-plus-circle"></i> New Transaction
                        </a>
                    </div>
                </div>
                
                <?php if(isset($success_message)): ?>
                <div class="alert alert-success alert-dismissible fade show" role="alert">
                    <?php echo $success_message; ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
                <?php endif; ?>
                
                <?php if(isset($error_message)): ?>
                <div class="alert alert-danger alert-dismissible fade show" role="alert">
                    <?php echo $error_message; ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
                <?php endif; ?>
                
                <!-- Statistics Cards -->
                <div class="row mb-4">
                    <div class="col-md-3">
                        <div class="stats-card card-masuk">
                            <div class="d-flex justify-content-between align-items-center">
                                <div>
                                    <h6 class="text-muted">Total Stock In</h6>
                                    <h3><?php echo number_format($total_masuk, 0, ',', '.'); ?></h3>
                                    <p class="text-muted mb-0"><small>Total items received</small></p>
                                </div>
                                <div class="card-icon text-success">
                                    <i class="bi bi-box-arrow-in-down"></i>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="stats-card card-keluar">
                            <div class="d-flex justify-content-between align-items-center">
                                <div>
                                    <h6 class="text-muted">Total Stock Out</h6>
                                    <h3><?php echo number_format($total_keluar, 0, ',', '.'); ?></h3>
                                    <p class="text-muted mb-0"><small>Total items distributed</small></p>
                                </div>
                                <div class="card-icon text-danger">
                                    <i class="bi bi-box-arrow-up"></i>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="stats-card card-value">
                            <div class="d-flex justify-content-between align-items-center">
                                <div>
                                    <h6 class="text-muted">Total Value In</h6>
                                    <h3>Rp <?php echo number_format($total_value_masuk, 0, ',', '.'); ?></h3>
                                    <p class="text-muted mb-0"><small>Value of received items</small></p>
                                </div>
                                <div class="card-icon text-primary">
                                    <i class="bi bi-cash-stack"></i>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="stats-card card-value">
                            <div class="d-flex justify-content-between align-items-center">
                                <div>
                                    <h6 class="text-muted">Total Value Out</h6>
                                    <h3>Rp <?php echo number_format($total_value_keluar, 0, ',', '.'); ?></h3>
                                    <p class="text-muted mb-0"><small>Value of distributed items</small></p>
                                </div>
                                <div class="card-icon text-primary">
                                    <i class="bi bi-cart-check"></i>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- Transaction Filters -->
                <div class="transaction-filters mb-4">
                    <form action="transaksi.php" method="get" class="row g-3">
                        <div class="col-md-3">
                            <label for="filter_date_start" class="form-label">Date From</label>
                            <input type="date" class="form-control" id="filter_date_start" name="date_start" 
                                   value="<?php echo isset($_GET['date_start']) ? $_GET['date_start'] : ''; ?>">
                        </div>
                        <div class="col-md-3">
                            <label for="filter_date_end" class="form-label">Date To</label>
                            <input type="date" class="form-control" id="filter_date_end" name="date_end" 
                                   value="<?php echo isset($_GET['date_end']) ? $_GET['date_end'] : ''; ?>">
                        </div>
                        <div class="col-md-3">
                            <label for="filter_type" class="form-label">Transaction Type</label>
                            <select class="form-select" id="filter_type" name="type">
                                <option value="">All Types</option>
                                <option value="masuk" <?php echo (isset($_GET['type']) && $_GET['type'] == 'masuk') ? 'selected' : ''; ?>>Stock In (Masuk)</option>
                                <option value="keluar" <?php echo (isset($_GET['type']) && $_GET['type'] == 'keluar') ? 'selected' : ''; ?>>Stock Out (Keluar)</option>
                            </select>
                        </div>
                        <div class="col-md-3 d-flex align-items-end">
                            <button type="submit" class="btn btn-primary me-2">
                                <i class="bi bi-filter"></i> Apply Filters
                            </button>
                            <a href="transaksi.php" class="btn btn-outline-secondary">
                                <i class="bi bi-x-circle"></i> Clear
                            </a>
                        </div>
                    </form>
                </div>
                
                <!-- Transactions List -->
                <div class="transactions-list">
                    <div class="d-flex justify-content-between align-items-center mb-3">
                        <h3 class="mb-0">Transaction History</h3>
                        <div class="btn-group">
                            <button type="button" class="btn btn-sm btn-outline-secondary" onclick="window.print()">
                                <i class="bi bi-printer"></i> Print
                            </button>
                            <a href="export_transaksi.php" class="btn btn-sm btn-outline-secondary">
                                <i class="bi bi-download"></i> Export
                            </a>
                        </div>
                    </div>
                    
                    <?php if(isset($transactions) && count($transactions) > 0): ?>
                        <div class="table-responsive">
                            <table class="table table-striped table-hover">
                                <thead class="table-dark">
                                    <tr>
                                        <th width="5%">No</th>
                                        <th width="15%">Date</th>
                                        <th width="30%">Product</th>
                                        <th width="15%">Quantity</th>
                                        <th width="15%">User</th>
                                        <th width="20%">Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php $no = 1; ?>
                                    <?php foreach($transactions as $transaction): ?>
                                    <tr>
                                        <td><?php echo $no++; ?></td>
                                        <td><?php echo date('d M Y', strtotime($transaction['tanggal_transaksi'])); ?></td>
                                        <td>
                                            <strong><?php echo htmlspecialchars($transaction['nama_produk']); ?></strong>
                                            <br>
                                            <small class="text-muted">Rp <?php echo number_format($transaction['harga'], 0, ',', '.'); ?> per unit</small>
                                        </td>
                                        <td>
                                            <?php if($transaction['jenis_transaksi'] == 'masuk'): ?>
                                                <span class="badge-masuk">+<?php echo number_format($transaction['jumlah'], 0, ',', '.'); ?></span>
                                            <?php else: ?>
                                                <span class="badge-keluar">-<?php echo number_format($transaction['jumlah'], 0, ',', '.'); ?></span>
                                            <?php endif; ?>
                                        </td>
                                        <td><?php echo htmlspecialchars($transaction['username'] ?? 'System'); ?></td>
                                        <td class="action-buttons">
                                            <a href="edit.php?id=<?php echo $transaction['id']; ?>" class="btn btn-sm btn-warning" title="Edit">
                                                <i class="bi bi-pencil"></i>
                                            </a>
                                            <a href="delete.php?id=<?php echo $transaction['id']; ?>" class="btn btn-sm btn-danger" title="Delete"
                                               onclick="return confirm('Are you sure you want to delete this transaction? This will also adjust inventory levels.');">
                                                <i class="bi bi-trash"></i>
                                            </a>
                                        </td>
                                    </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                        
                        <!-- Basic Pagination - Add pagination logic as needed -->
                        <div class="pagination-container">
                            <nav aria-label="Page navigation">
                                <ul class="pagination">
                                    <li class="page-item disabled">
                                        <a class="page-link" href="#" tabindex="-1" aria-disabled="true">Previous</a>
                                    </li>
                                    <li class="page-item active"><a class="page-link" href="#">1</a></li>
                                    <li class="page-item"><a class="page-link" href="#">2</a></li>
                                    <li class="page-item"><a class="page-link" href="#">3</a></li>
                                    <li class="page-item">
                                        <a class="page-link" href="#">Next</a>
                                    </li>
                                </ul>
                            </nav>
                        </div>
                    <?php else: ?>
                        <!-- Empty State -->
                        <div class="empty-state">
                            <i class="bi bi-clipboard-x"></i>
                            <h4>No Transactions Found</h4>
                            <p class="text-muted">There are no transactions to display. Start by adding a new transaction.</p>
                            <a href="add_transaksi.php" class="btn btn-primary mt-3">
                                <i class="bi bi-plus-circle"></i> Add First Transaction
                            </a>
                        </div>
                    <?php endif; ?>
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

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>