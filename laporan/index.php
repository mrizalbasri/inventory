<?php
require_once '../config/database.php';
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
    
    // Update report
    if (isset($_POST['update_report'])) {
        $reportModel->updateReport(
            $_POST['id'],
            $_POST['jenis_laporan'],
            $_POST['tanggal_awal'],
            $_POST['tanggal_akhir'],
            $_POST['generated_by']
        );
        $_SESSION['success'] = "Report successfully updated!";
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

    header('Location: index.php');
    exit();
}

// Get report by ID for edit modal
$editReport = null;
if (isset($_GET['edit_id'])) {
    $editReport = $reportModel->getReportById($_GET['edit_id']);
    $showEditModal = true;
} else {
    $showEditModal = false;
}

// Fetch all reports
$reports = $reportModel->getAllReports();

// Apply search filter
if (isset($_GET['search']) && !empty($_GET['search'])) {
    $filtered = [];
    $search = strtolower($_GET['search']);
    foreach ($reports as $report) {
        if (
            strpos(strtolower($report['id']), $search) !== false ||
            strpos(strtolower($report['jenis_laporan']), $search) !== false
        ) {
            $filtered[] = $report;
        }
    }
    $reports = $filtered;
}

// Apply jenis filter
if (isset($_GET['filter_jenis']) && !empty($_GET['filter_jenis'])) {
    $filtered = [];
    foreach ($reports as $report) {
        if ($report['jenis_laporan'] == $_GET['filter_jenis']) {
            $filtered[] = $report;
        }
    }
    $reports = $filtered;
}

// Simple pagination implementation
$records_per_page = 10;
$total_records = count($reports);
$total_pages = ceil($total_records / $records_per_page);
$current_page = isset($_GET['page']) ? max(1, min($total_pages, intval($_GET['page']))) : 1;
$offset = ($current_page - 1) * $records_per_page;

// Slice the array for pagination
$paginatedReports = array_slice($reports, $offset, $records_per_page);
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
        
        .btn-warning {
            background-color: var(--warning-color);
            border-color: var(--warning-color);
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
        
        /* Modal styling */
        .modal-header {
            background-color: var(--primary-color);
            color: white;
        }
        
        .modal-footer {
            background-color: #f8f9fc;
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
            <main class="col main-content">
                <div class="d-sm-flex align-items-center justify-content-between mb-4">
                    <h1 class="h3 mb-0">Manajemen Laporan</h1>
                </div>

                <!-- Success Alert -->
                <?php if (isset($_SESSION['success'])): ?>
                <div class="alert alert-success alert-dismissible fade show" role="alert">
                    <?= $_SESSION['success'] ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
                <?php unset($_SESSION['success']); endif; ?>
                
                <!-- Error Alert -->
                <?php if (isset($_SESSION['error'])): ?>
                <div class="alert alert-danger alert-dismissible fade show" role="alert">
                    <?= $_SESSION['error'] ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
                <?php unset($_SESSION['error']); endif; ?>

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
                                    <input type="text" name="search" class="form-control me-2" placeholder="Cari laporan..." 
                                        value="<?= isset($_GET['search']) ? htmlspecialchars($_GET['search']) : '' ?>">
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
                                    <?php if (empty($paginatedReports)): ?>
                                    <tr>
                                        <td colspan="6" class="text-center">Tidak ada data laporan</td>
                                    </tr>
                                    <?php else: ?>
                                    <?php foreach ($paginatedReports as $report): ?>
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
                                            <a href="view.php?id=<?= $report['id'] ?>" class="btn btn-sm btn-info">
                                                <i class="bi bi-eye"></i>
                                            </a>
                                            <a href="index.php?edit_id=<?= $report['id'] ?>" class="btn btn-sm btn-warning">
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
                        
                        <!-- Pagination -->
                        <?php if ($total_pages > 1): ?>
                        <nav aria-label="Page navigation" class="mt-4">
                            <ul class="pagination justify-content-center">
                                <li class="page-item <?= ($current_page <= 1) ? 'disabled' : '' ?>">
                                    <a class="page-link" href="?page=<?= $current_page - 1 ?><?= isset($_GET['search']) ? '&search='.urlencode($_GET['search']) : '' ?><?= isset($_GET['filter_jenis']) ? '&filter_jenis='.urlencode($_GET['filter_jenis']) : '' ?>" tabindex="-1" <?= ($current_page <= 1) ? 'aria-disabled="true"' : '' ?>>Previous</a>
                                </li>
                                
                                <?php for ($i = 1; $i <= $total_pages; $i++): ?>
                                <li class="page-item <?= ($current_page == $i) ? 'active' : '' ?>">
                                    <a class="page-link" href="?page=<?= $i ?><?= isset($_GET['search']) ? '&search='.urlencode($_GET['search']) : '' ?><?= isset($_GET['filter_jenis']) ? '&filter_jenis='.urlencode($_GET['filter_jenis']) : '' ?>"><?= $i ?></a>
                                </li>
                                <?php endfor; ?>
                                
                                <li class="page-item <?= ($current_page >= $total_pages) ? 'disabled' : '' ?>">
                                    <a class="page-link" href="?page=<?= $current_page + 1 ?><?= isset($_GET['search']) ? '&search='.urlencode($_GET['search']) : '' ?><?= isset($_GET['filter_jenis']) ? '&filter_jenis='.urlencode($_GET['filter_jenis']) : '' ?>">Next</a>
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

    <!-- Edit Report Modal -->
    <?php if ($showEditModal && $editReport): ?>
    <div class="modal fade show" id="editModal" tabindex="-1" style="display: block; background-color: rgba(0,0,0,0.5);" aria-modal="true" role="dialog">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Edit Laporan #<?= htmlspecialchars($editReport['id']) ?></h5>
                    <a href="index.php" class="btn-close" aria-label="Close"></a>
                </div>
                <form method="POST" action="index.php">
                    <div class="modal-body">
                        <div class="mb-3">
                            <label for="edit_id" class="form-label">ID Laporan</label>
                            <input type="number" class="form-control" id="edit_id" name="id" value="<?= htmlspecialchars($editReport['id']) ?>" readonly>
                        </div>
                        <div class="mb-3">
                            <label for="edit_jenis_laporan" class="form-label">Jenis Laporan</label>
                            <select class="form-select" id="edit_jenis_laporan" name="jenis_laporan" required>
                                <option value="">Pilih Jenis</option>
                                <option value="stok" <?= $editReport['jenis_laporan'] == 'stok' ? 'selected' : '' ?>>Laporan Stok</option>
                                <option value="transaksi" <?= $editReport['jenis_laporan'] == 'transaksi' ? 'selected' : '' ?>>Laporan Transaksi</option>
                                <option value="keuangan" <?= $editReport['jenis_laporan'] == 'keuangan' ? 'selected' : '' ?>>Laporan Keuangan</option>
                                <option value="penjualan" <?= $editReport['jenis_laporan'] == 'penjualan' ? 'selected' : '' ?>>Laporan Penjualan</option>
                            </select>
                        </div>
                        <div class="mb-3">
                            <label for="edit_tanggal_awal" class="form-label">Tanggal Awal</label>
                            <input type="date" class="form-control" id="edit_tanggal_awal" name="tanggal_awal" value="<?= htmlspecialchars($editReport['tanggal_awal']) ?>" required>
                        </div>
                        <div class="mb-3">
                            <label for="edit_tanggal_akhir" class="form-label">Tanggal Akhir</label>
                            <input type="date" class="form-control" id="edit_tanggal_akhir" name="tanggal_akhir" value="<?= htmlspecialchars($editReport['tanggal_akhir']) ?>" required>
                        </div>
                        <div class="mb-3">
                            <label for="edit_generated_by" class="form-label">Generated By</label>
                            <input type="number" class="form-control" id="edit_generated_by" name="generated_by" value="<?= htmlspecialchars($editReport['generated_by']) ?>" required>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <a href="index.php" class="btn btn-secondary">Cancel</a>
                        <button type="submit" name="update_report" class="btn btn-primary">Save Changes</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    <?php endif; ?>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.2.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>