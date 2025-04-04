<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Dashboard dengan Hover Effect (CSS Only)</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css">
  <style>
    body {
      font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
      background-color: #f8f9fa;
    }
    
    .sidebar {
      background-color: #343a40;
      min-height: 100vh;
      box-shadow: 0 0 10px rgba(0, 0, 0, 0.1);
    }
    
    .sidebar-sticky {
      height: calc(100vh - 48px);
      overflow-y: auto;
    }
    
    .nav-link {
      color: rgba(255, 255, 255, 0.7);
      border-radius: 5px;
      padding: 10px 15px;
      transition: all 0.3s ease;
      position: relative;
    }
    
    .nav-link:hover {
      color: #fff;
      background-color: rgba(255, 255, 255, 0.1);
      transform: translateX(5px);
    }
    
    .nav-link.active {
      color: #fff;
      background-color: #007bff;
    }
    
    .nav-link i {
      margin-right: 10px;
    }
    
    .sidebar-heading {
      text-transform: uppercase;
      font-size: 0.75rem;
      font-weight: bold;
      letter-spacing: 1px;
    }
    
    /* Efek hover dengan CSS saja */
    .nav-item {
      position: relative;
    }
    
    .nav-item::after {
      content: '';
      position: absolute;
      width: 0;
      height: 2px;
      bottom: 0;
      left: 0;
      background-color: #007bff;
      transition: width 0.3s;
    }
    
    .nav-item:hover::after {
      width: 100%;
    }
    
    /* Indikator aktif */
    .nav-link.active::before {
      content: '';
      position: absolute;
      top: 0;
      left: 0;
      width: 4px;
      height: 100%;
      background-color: #fff;
      border-radius: 0 2px 2px 0;
    }
    
    /* Content area */
    .main-content {
      padding: 20px;
    }
  </style>
</head>
<body>
  <div class="container-fluid">
    <div class="row">
      <!-- Sidebar -->
      <?php
// Dapatkan nama halaman saat ini dari URL
$current_page = basename($_SERVER['PHP_SELF']);

// Fungsi untuk menentukan apakah menu aktif
function isActive($page) {
    global $current_page;
    return ($current_page == $page) ? 'active' : '';
}
?>

    <!-- Sidebar -->
    <nav id="sidebar" class="sidebar col-md-3 col-lg-2 d-md-block">
      <div class="position-sticky sidebar-sticky pt-3">
        <ul class="nav flex-column">
          <li class="nav-item px-2 mb-3">
            <a href="dashboard.php" class="nav-link <?php echo isActive('dashboard.php'); ?>">
              <i class="bi bi-speedometer2"></i>
              Dashboard
            </a>
          </li>
          <li class="nav-item px-2 mb-3">
            <a href="produk.php" class="nav-link <?php echo isActive('produk.php'); ?>">
              <i class="bi bi-box-seam"></i>
              Products
            </a>
          </li>
          <li class="nav-item px-2 mb-3">
            <a href="stok.php" class="nav-link <?php echo isActive('stok.php'); ?>">
              <i class="bi bi-stack"></i>
              Stock Management
            </a>
          </li>
          <li class="nav-item px-2 mb-3">
            <a href="transaksi.php" class="nav-link <?php echo isActive('transaksi.php'); ?>">
              <i class="bi bi-arrow-left-right"></i>
              Transactions
            </a>
          </li>
          <li class="nav-item px-2 mb-3">
            <a href="laporan.php" class="nav-link <?php echo isActive('laporan.php'); ?>">
              <i class="bi bi-file-earmark-bar-graph"></i>
              Reports
            </a>
          </li>
          <li class="nav-item px-2 mb-3">
            <a href="supplier.php" class="nav-link <?php echo isActive('supplier.php'); ?>">
              <i class="bi bi-truck"></i>
              Suppliers
            </a>
          </li>
          <li class="nav-item px-2 mb-3">
            <a href="pengguna.php" class="nav-link <?php echo isActive('pengguna.php'); ?>">
              <i class="bi bi-people"></i>
              Users
            </a>
          </li>
          <li class="nav-item px-2">
            <a href="pengaturan.php" class="nav-link <?php echo isActive('pengaturan.php'); ?>">
              <i class="bi bi-gear"></i>
              Settings
            </a>
          </li>
        </ul>
        
        <h6 class="sidebar-heading d-flex justify-content-between align-items-center px-3 mt-5 mb-1 text-white">
          <span>Saved Reports</span>
        </h6>
        <ul class="nav flex-column mb-2">
          <li class="nav-item px-2">
            <a class="nav-link" href="#">
              <i class="bi bi-file-earmark-text"></i> Monthly Inventory
            </a>
          </li>
          <li class="nav-item px-2">
            <a class="nav-link" href="#">
              <i class="bi bi-file-earmark-text"></i> Stock Movement
            </a>
          </li>
        </ul>
      </div>
    </nav>
    <!-- End of Sidebar -->
 

  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>