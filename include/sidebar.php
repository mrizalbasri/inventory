<?php

// Start the session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}


// Redirect to login if not logged in
if(!isset($_SESSION['user_id'])) {
    header("Location: " . BASE_URL . "auth/login.php");
    exit();
}



// Get user data from session
$user_name = isset($_SESSION['username']) ? $_SESSION['username'] : "Admin User";
$user_role = isset($_SESSION['role']) ? $_SESSION['role'] : "Administrator";
$user_initial = strtoupper(substr($user_name, 0, 1));

// Dapatkan nama halaman saat ini dari URL
$current_page = $_SERVER['PHP_SELF'];

// Fungsi untuk menentukan apakah menu aktif - dengan perbaikan untuk subfolder
function isActive($page) {
    global $current_page;
    // Normalisasi path untuk perbandingan yang lebih akurat
    $normalized_page = BASE_URL . ltrim($page, '/');
    $normalized_current = str_replace('//', '/', '/' . ltrim($current_page, '/'));
    
    // Debug
    // echo "Comparing: $normalized_page with $normalized_current<br>";
    
    // Cek apakah halaman saat ini cocok dengan halaman yang sedang diperiksa
    if (strpos($normalized_current, $normalized_page) !== false) {
        return 'active';
    }
    
    return '';
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Dashboard - InvenSys</title>
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
      position: fixed;
    }
    
    .sidebar-sticky {
      height: calc(100vh - 48px);
      overflow-y: auto;
      display: flex;
      flex-direction: column;
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
      color: rgba(255, 255, 255, 0.5);
      padding: 10px 15px;
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
    
    /* User profile section */
    .user-profile {
      padding: 15px;
      border-bottom: 1px solid rgba(255, 255, 255, 0.1);
      margin-bottom: 15px;
    }
    
    .user-avatar {
      width: 45px;
      height: 45px;
      border-radius: 50%;
      background-color: #007bff;
      display: flex;
      align-items: center;
      justify-content: center;
      color: white;
      font-weight: bold;
      margin-right: 10px;
    }
    
    .user-info {
      color: white;
    }
    
    .user-name {
      font-weight: bold;
      margin-bottom: 0;
    }
    
    .user-role {
      font-size: 0.8rem;
      color: rgba(255, 255, 255, 0.7);
    }
    
    /* Logout section */
    .sidebar-footer {
      margin-top: auto;
      border-top: 1px solid rgba(255, 255, 255, 0.1);
      padding: 15px;
    }
    
    .logout-btn {
      width: 100%;
      text-align: left;
      padding: 8px 15px;
      background-color: rgba(255, 255, 255, 0.1);
      border: none;
      color: rgba(255, 255, 255, 0.7);
      border-radius: 5px;
      transition: all 0.3s ease;
    }
    
    .logout-btn:hover {
      background-color: #dc3545;
      color: white;
    }
    
    /* Brand/Logo */
    .sidebar-brand {
      padding: 15px;
      font-size: 1.2rem;
      font-weight: bold;
      color: white;
      text-align: center;
      border-bottom: 1px solid rgba(255, 255, 255, 0.1);
      margin-bottom: 10px;
      text-decoration: none;
      display: block;
    }
    
    /* Navigation categories */
    .nav-category {
      margin-top: 15px;
    }
  </style>
</head>
<body>

<!-- Sidebar -->
<nav id="sidebar" class="sidebar col-md-3 col-lg-2 d-md-block">
  <div class="position-sticky sidebar-sticky">
    <!-- Brand/Logo -->
    <a href="<?php echo BASE_URL; ?>index.php" class="sidebar-brand">
      <i class="bi bi-box"></i> InvenSys
    </a>
    
    <!-- User Profile (at the top) -->
    <div class="user-profile d-flex align-items-center">
      <div class="user-avatar">
        <?php echo $user_initial; ?>
      </div>
      <div class="user-info">
        <p class="user-name"><?php echo $user_name; ?></p>
        <span class="user-role"><?php echo $user_role; ?></span>
      </div>
    </div>
    
    <!-- Main Navigation -->
    <div class="sidebar-heading">
      Main Menu
    </div>
    <ul class="nav flex-column">
      <li class="nav-item px-2 mb-2">
        <a href="<?php echo BASE_URL; ?>index.php" class="nav-link <?php echo isActive('index.php'); ?>">
          <i class="bi bi-house"></i>
          Dashboard
        </a>  
      </li>
      <li class="nav-item px-2 mb-2">
        <a href="<?php echo BASE_URL; ?>produk/index.php" class="nav-link <?php echo isActive('produk/index.php'); ?>">
          <i class="bi bi-box-seam"></i>
          Products
        </a>
      </li>
      <li class="nav-item px-2 mb-2">
        <a href="<?php echo BASE_URL; ?>stok/index.php" class="nav-link <?php echo isActive('stok/index.php'); ?>">
          <i class="bi bi-stack"></i>
          Stock Management
        </a>
      </li>
      <li class="nav-item px-2 mb-2">
        <a href="<?php echo BASE_URL; ?>transaksi/index.php" class="nav-link <?php echo isActive('transaksi/index.php'); ?>">
          <i class="bi bi-arrow-left-right"></i>
          Transactions
        </a>
      </li>
    </ul>
    
    <!-- Reporting Section -->
    <div class="sidebar-heading nav-category">
      Reporting
    </div>
    <ul class="nav flex-column">
      <li class="nav-item px-2 mb-2">
        <a href="<?php echo BASE_URL; ?>laporan/index.php" class="nav-link <?php echo isActive('laporan/index.php'); ?>">
          <i class="bi bi-file-earmark-bar-graph"></i>
          Reports
        </a>
      </li>
    </ul>
    
    <!-- Administration Section -->
   
    </ul>
    
    <!-- Logout Section (at the bottom) -->
    <div class="sidebar-footer">
      <button class="logout-btn" onclick="window.location.href='<?php echo BASE_URL; ?>auth/logout.php'">
        <i class="bi bi-box-arrow-right"></i> Logout
      </button>
    </div>
  </div>
</nav>
<!-- End of Sidebar -->

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>