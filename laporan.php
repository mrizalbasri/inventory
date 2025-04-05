<?php
include 'database.php';
include 'report_model.php';

// Initialize Report Model
$reportModel = new ReportModel($database);

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    // Add new report
    if (isset($_POST['add_report'])) {
        $reportModel->createReport(
            $_POST['id'], 
            $_POST['jenis_laporan'], 
            $_POST['tanggal_awal'], 
            $_POST['tanggal_akhir'], 
            $_POST['generated_by']
        );
        $_SESSION['success'] = "Report successfully added!";
    }
    
    // Delete report
    if (isset($_POST['delete_report'])) {
        $reportModel->deleteReport($_POST['report_id']);
        $_SESSION['success'] = "Report successfully deleted!";
    }
    
    // Export PDF
    if (isset($_POST['export_pdf'])) {
        header('Location: export_pdf.php');
        exit();
    }
    
    // Export Excel
    if (isset($_POST['export_excel'])) {
        header('Location: export_excel.php');
        exit();
    }
    
    // Handle theme toggle
    if (isset($_POST['toggle_theme'])) {
        $newThemeValue = isset($_POST['dark_mode']) ? 'true' : 'false';
        setcookie('dark_mode', $newThemeValue, time() + 31536000, '/');
        header('Location: laporan.php');
        exit();
    }

    header('Location: laporan.php');
    exit();
}

// Fetch all reports
$reports = $reportModel->getAllReports();

// Get user preferences
$darkMode = isset($_COOKIE['dark_mode']) && $_COOKIE['dark_mode'] === 'true';
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manajemen Laporan</title>
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
            background: linear-gradient(180deg, #4e73df 10%, #224abe 100%);
            color: white;
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
        
        .btn-danger {
            background-color: var(--danger-color);
            border-color: var(--danger-color);
        }
        
        .btn-success {
            background-color: var(--success-color);
            border-color: var(--success-color);
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
        
        .theme-switch-wrapper {
            position: fixed;
            top: 20px;
            right: 20px;
            z-index: 1000;
            display: flex;
            align-items: center;
        }
        
        .dark-theme {
            background-color: #1e1e2d;
            color: #e2e2e2;
        }
        
        .dark-theme .sidebar {
            background: linear-gradient(180deg, #3a3a57 10%, #1e1e2d 100%);
        }
        
        .dark-theme .card {
            background-color: #2d2d3f;
            border-color: #2d2d3f;
            color: #e2e2e2;
            box-shadow: 0 0.15rem 1.75rem 0 rgba(0, 0, 0, 0.2);
        }
        
        .dark-theme .card-header {
            background-color: #262636;
            border-color: #2d2d3f;
            color: #e2e2e2;
        }
        
        .dark-theme .table {
            color: #e2e2e2;
        }
        
        .dark-theme .table th {
            background-color: #262636;
            color: #e2e2e2;
            border-color: #2d2d3f;
        }
        
        .dark-theme .table td {
            border-color: #2d2d3f;
        }
        
        .dark-theme .form-control {
            background-color: #262636;
            border-color: #2d2d3f;
            color: #e2e2e2;
        }
        
        .dark-theme .form-select {
            background-color: #262636;
            border-color: #2d2d3f;
            color: #e2e2e2;
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
        
        .dark-theme .pagination .page-item .page-link {
            background-color: #262636;
            border-color: #2d2d3f;
            color: #e2e2e2;
        }
        
        .dark-theme .pagination .page-item.active .page-link {
            background-color: var(--primary-color);
            border-color: var(--primary-color);
        }
        
        /* Alert styling */
        .alert {
            border-radius: 0.35rem;
            border: none;
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
<body class="<?= $darkMode ? 'dark-theme' : '' ?>">
    <!-- Theme Switch Form -->
    <div class="theme-switch-wrapper">
        <form method="POST" action="">
            <button type="submit" name="toggle_theme" class="btn <?= $darkMode ? 'btn-light' : 'btn-dark' ?>">
                <i class="bi <?= $darkMode ? 'bi-sun' : 'bi-moon' ?>"></i>
                <?= $darkMode ? 'Light Mode' : 'Dark Mode' ?>
            </button>
            <input type="hidden" name="dark_mode" value="<?= !$darkMode ? 'true' : '' ?>">
        </form>
    </div>
    
    <!-- Mobile Menu Toggle -->
    <div class="mobile-only mt-3 ms-3">
        <a href="#" class="btn btn-primary">
            <i class="bi bi-list"></i> Menu
        </a>
    </div>

    <div class="container-fluid">
        <div class="row">
            <!-- Sidebar -->
            <?php include 'sidebar.php'; ?>

            <!-- Main Content -->
            <main class="col main-content">
                <div class="d-sm-flex align-items-center justify-content-between mb-4">
                    <h1 class="h3 mb-0">Manajemen Laporan</h1>
                    <div>
                        <form method="POST" class="d-inline">
                            <button type="submit" name="export_pdf" class="btn btn-danger">
                                <i class="bi bi-file-earmark-pdf me-1"></i> Export PDF
                            </button>
                        </form>
                        <form method="POST" class="d-inline ms-2">
                            <button type="submit" name="export_excel" class="btn btn-success">
                                <i class="bi bi-file-earmark-excel me-1"></i> Export Excel
                            </button>
                        </form>
                    </div>
                </div>

                <!-- Success Alert -->
                <?php if (isset($_SESSION['success'])): ?>
                <div class="alert alert-success alert-dismissible fade show" role="alert">
                    <?= $_SESSION['success'] ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
                <?php unset($_SESSION['success']); endif; ?>

                <!-- Add Report Card -->
                <div class="card shadow mb-4">
                    <div class="card-header py-3">
                        <h6 class="m-0 font-weight-bold text-primary">Tambah Laporan Baru</h6>
                    </div>
                    <div class="card-body">
                        <form method="POST" class="row g-3">
                            <div class="col-md-2">
                                <label for="id" class="form-label">ID Laporan</label>
                                <input type="number" id="id" name="id" class="form-control" required>
                            </div>
                            <div class="col-md-3">
                                <label for="jenis_laporan" class="form-label">Jenis Laporan</label>
                                <select id="jenis_laporan" name="jenis_laporan" class="form-select" required>
                                    <option value="">Pilih Jenis</option>
                                    <option value="stok">Laporan Stok</option>
                                    <option value="transaksi">Laporan Transaksi</option>
                                    <option value="keuangan">Laporan Keuangan</option>
                                    <option value="penjualan">Laporan Penjualan</option>
                                </select>
                            </div>
                            <div class="col-md-2">
                                <label for="tanggal_awal" class="form-label">Tanggal Awal</label>
                                <input type="date" id="tanggal_awal" name="tanggal_awal" class="form-control" required>
                            </div>
                            <div class="col-md-2">
                                <label for="tanggal_akhir" class="form-label">Tanggal Akhir</label>
                                <input type="date" id="tanggal_akhir" name="tanggal_akhir" class="form-control" required>
                            </div>
                            <div class="col-md-2">
                                <label for="generated_by" class="form-label">Generated By</label>
                                <input type="number" id="generated_by" name="generated_by" class="form-control" required>
                            </div>
                            <div class="col-md-12 mt-4">
                                <button type="submit" name="add_report" class="btn btn-primary">
                                    <i class="bi bi-plus-circle me-1"></i> Tambah Laporan
                                </button>
                            </div>
                        </form>
                    </div>
                </div>

                <!-- Reports List Card -->
                <div class="card shadow mb-4">
                    <div class="card-header py-3">
                        <h6 class="m-0 font-weight-bold text-primary">Daftar Laporan</h6>
                    </div>
                    <div class="card-body">
                        <div class="row mb-3">
                            <div class="col-md-6">
                                <form method="GET" class="d-flex">
                                    <input type="text" name="search" class="form-control me-2" placeholder="Cari laporan...">
                                    <button class="btn btn-primary" type="submit">
                                        <i class="bi bi-search"></i>
                                    </button>
                                </form>
                            </div>
                            <div class="col-md-3">
                                <form method="GET">
                                    <select name="filter_jenis" class="form-select" onchange="this.form.submit()">
                                        <option value="">Semua Jenis</option>
                                        <option value="stok" <?= isset($_GET['filter_jenis']) && $_GET['filter_jenis'] == 'stok' ? 'selected' : '' ?>>Laporan Stok</option>
                                        <option value="transaksi" <?= isset($_GET['filter_jenis']) && $_GET['filter_jenis'] == 'transaksi' ? 'selected' : '' ?>>Laporan Transaksi</option>
                                        <option value="keuangan" <?= isset($_GET['filter_jenis']) && $_GET['filter_jenis'] == 'keuangan' ? 'selected' : '' ?>>Laporan Keuangan</option>
                                        <option value="penjualan" <?= isset($_GET['filter_jenis']) && $_GET['filter_jenis'] == 'penjualan' ? 'selected' : '' ?>>Laporan Penjualan</option>
                                    </select>
                                </form>
                            </div>
                        </div>
                        
                        <div class="table-responsive">
                            <table class="table table-hover">
                                <thead>
                                    <tr>
                                        <th>
                                            <a href="?sort=id&dir=<?= isset($_GET['dir']) && $_GET['dir'] == 'asc' ? 'desc' : 'asc' ?>" class="text-decoration-none">
                                                ID <i class="bi bi-arrow-down-up"></i>
                                            </a>
                                        </th>
                                        <th>
                                            <a href="?sort=jenis_laporan&dir=<?= isset($_GET['dir']) && $_GET['dir'] == 'asc' ? 'desc' : 'asc' ?>" class="text-decoration-none">
                                                Jenis Laporan <i class="bi bi-arrow-down-up"></i>
                                            </a>
                                        </th>
                                        <th>
                                            <a href="?sort=tanggal_awal&dir=<?= isset($_GET['dir']) && $_GET['dir'] == 'asc' ? 'desc' : 'asc' ?>" class="text-decoration-none">
                                                Tanggal Awal <i class="bi bi-arrow-down-up"></i>
                                            </a>
                                        </th>
                                        <th>
                                            <a href="?sort=tanggal_akhir&dir=<?= isset($_GET['dir']) && $_GET['dir'] == 'asc' ? 'desc' : 'asc' ?>" class="text-decoration-none">
                                                Tanggal Akhir <i class="bi bi-arrow-down-up"></i>
                                            </a>
                                        </th>
                                        <th>
                                            <a href="?sort=generated_by&dir=<?= isset($_GET['dir']) && $_GET['dir'] == 'asc' ? 'desc' : 'asc' ?>" class="text-decoration-none">
                                                Generated By <i class="bi bi-arrow-down-up"></i>
                                            </a>
                                        </th>
                                        <th class="text-center">Aksi</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php if (empty($reports)): ?>
                                    <tr>
                                        <td colspan="6" class="text-center">Tidak ada data laporan</td>
                                    </tr>
                                    <?php else: ?>
                                    <?php foreach ($reports as $report): ?>
                                    <tr>
                                        <td><?= htmlspecialchars($report['id']) ?></td>
                                        <td>
                                            <?php 
                                            $label = "secondary";
                                            if ($report['jenis_laporan'] == 'stok') $label = "primary";
                                            if ($report['jenis_laporan'] == 'transaksi') $label = "success";
                                            if ($report['jenis_laporan'] == 'keuangan') $label = "warning";
                                            if ($report['jenis_laporan'] == 'penjualan') $label = "info";
                                            ?>
                                            <span class="badge bg-<?= $label ?>">
                                                <?= ucfirst(htmlspecialchars($report['jenis_laporan'])) ?>
                                            </span>
                                        </td>
                                        <td><?= htmlspecialchars(date('d M Y', strtotime($report['tanggal_awal']))) ?></td>
                                        <td><?= htmlspecialchars(date('d M Y', strtotime($report['tanggal_akhir']))) ?></td>
                                        <td><?= htmlspecialchars($report['generated_by']) ?></td>
                                        <td class="text-center">
                                            <a href="view_report.php?id=<?= $report['id'] ?>" class="btn btn-sm btn-info">
                                                <i class="bi bi-eye"></i>
                                            </a>
                                            <a href="edit_report.php?id=<?= $report['id'] ?>" class="btn btn-sm btn-warning">
                                                <i class="bi bi-pencil"></i>
                                            </a>
                                            <form method="POST" class="d-inline">
                                                <input type="hidden" name="report_id" value="<?= $report['id'] ?>">
                                                <button type="submit" name="delete_report" class="btn btn-sm btn-danger" onclick="return confirm('Apakah Anda yakin ingin menghapus laporan ini?');">
                                                    <i class="bi bi-trash"></i>
                                                </button>
                                            </form>
                                        </td>
                                    </tr>
                                    <?php endforeach; ?>
                                    <?php endif; ?>
                                </tbody>
                            </table>
                        </div>
                        
                        <?php
                        // Simple pagination implementation
                        $total_records = count($reports);
                        $records_per_page = 10;
                        $total_pages = ceil($total_records / $records_per_page);
                        $current_page = isset($_GET['page']) ? max(1, min($total_pages, intval($_GET['page']))) : 1;
                        ?>
                        
                        <!-- Pagination -->
                        <?php if ($total_pages > 1): ?>
                        <nav aria-label="Page navigation" class="mt-4">
                            <ul class="pagination justify-content-center">
                                <li class="page-item <?= ($current_page <= 1) ? 'disabled' : '' ?>">
                                    <a class="page-link" href="?page=<?= $current_page - 1 ?>" tabindex="-1" <?= ($current_page <= 1) ? 'aria-disabled="true"' : '' ?>>Previous</a>
                                </li>
                                
                                <?php for ($i = 1; $i <= $total_pages; $i++): ?>
                                <li class="page-item <?= ($current_page == $i) ? 'active' : '' ?>">
                                    <a class="page-link" href="?page=<?= $i ?>"><?= $i ?></a>
                                </li>
                                <?php endfor; ?>
                                
                                <li class="page-item <?= ($current_page >= $total_pages) ? 'disabled' : '' ?>">
                                    <a class="page-link" href="?page=<?= $current_page + 1 ?>">Next</a>
                                </li>
                            </ul>
                        </nav>
                        <?php endif; ?>
                    </div>
                </div>
            </main>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.2.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>