<?php
// Start session if not already started
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// Database connection with PDO
require_once '../config/database.php';

// Check if user is logged in
// if (!isset($_SESSION['user_id'])) {
//     header("Location: login.php");
//     exit();
// }

// Get products for transaction form
try {
    $stmt = $database->query("SELECT p.id, p.nama_produk, p.harga, s.jumlah_stok 
    FROM produk p
    JOIN stok s ON p.id = s.produk_id
    ORDER BY p.nama_produk");
    $products = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $error_message = "Error loading products: " . $e->getMessage();
}

// Initialize product rows in session if not exists
if (!isset($_SESSION['product_rows'])) {
    $_SESSION['product_rows'] = 1;
}

// Handle adding product row
if (isset($_POST['add_product_row'])) {
    $_SESSION['product_rows']++;
    // Store current form data in session
    storeFormData();
    header("Location: add.php");
    exit();
}

// Handle removing product row
if (isset($_POST['remove_product_row']) && $_SESSION['product_rows'] > 1) {
    $remove_index = $_POST['remove_index'];
    // Store current form data excluding the removed row
    storeFormDataExcept($remove_index);
    $_SESSION['product_rows']--;
    header("Location: add.php");
    exit();
}

// Function to store form data in session
function storeFormData() {
    if (isset($_POST['produk_id'])) {
        $_SESSION['form_data'] = [
            'tanggal_transaksi' => $_POST['tanggal_transaksi'] ?? date('Y-m-d'),
            'customer_name' => $_POST['customer_name'] ?? '',
            'jenis_transaksi' => $_POST['jenis_transaksi'] ?? 'keluar',
            'produk_id' => $_POST['produk_id'] ?? [],
            'jumlah' => $_POST['jumlah'] ?? []
        ];
    }
}

// Function to store form data except specified index
function storeFormDataExcept($exclude_index) {
    if (isset($_POST['produk_id'])) {
        $produk_ids = [];
        $quantities = [];
        
        foreach ($_POST['produk_id'] as $i => $produk_id) {
            if ($i != $exclude_index) {
                $produk_ids[] = $produk_id;
                $quantities[] = $_POST['jumlah'][$i];
            }
        }
        
        $_SESSION['form_data'] = [
            'tanggal_transaksi' => $_POST['tanggal_transaksi'] ?? date('Y-m-d'),
            'customer_name' => $_POST['customer_name'] ?? '',
            'jenis_transaksi' => $_POST['jenis_transaksi'] ?? 'keluar',
            'produk_id' => $produk_ids,
            'jumlah' => $quantities
        ];
    }
}

// Handle product selection for each row
if (isset($_POST['update_form'])) {
    storeFormData();
    header("Location: add.php");
    exit();
}

// Process transaction form if submitted
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['submit_transaction'])) {
    // Validate that at least one product is selected
    $has_products = false;
    if (isset($_POST['produk_id'])) {
        foreach ($_POST['produk_id'] as $produk_id) {
            if (!empty($produk_id)) {
                $has_products = true;
                break;
            }
        }
    }
    
    if (!$has_products) {
        $error_message = "Please select at least one product.";
    } else {
        try {
            // Begin transaction
            $database->beginTransaction();
            
            $tanggal_transaksi = $_POST['tanggal_transaksi'];
            $jenis_transaksi = $_POST['jenis_transaksi'];
            $user_id = $_SESSION['user_id'] ?? 1; // Default to user ID 1 if not logged in
            
            // Process each product as a separate transaction entry
            foreach ($_POST['produk_id'] as $key => $produk_id) {
                if (empty($produk_id)) continue; // Skip empty selections
                
                $jumlah = $_POST['jumlah'][$key];
                
                // Insert transaction
                $stmt = $database->prepare("INSERT INTO transaksi (produk_id, jenis_transaksi, jumlah, tanggal_transaksi, created_by) 
                                  VALUES (:produk_id, :jenis_transaksi, :jumlah, :tanggal_transaksi, :created_by)");
                
                $stmt->bindParam(':produk_id', $produk_id);
                $stmt->bindParam(':jenis_transaksi', $jenis_transaksi);
                $stmt->bindParam(':jumlah', $jumlah);
                $stmt->bindParam(':tanggal_transaksi', $tanggal_transaksi);
                $stmt->bindParam(':created_by', $user_id);
                
                $stmt->execute();
                
                // Update stock based on transaction type
                if ($jenis_transaksi == 'masuk') {
                    $stmt = $database->prepare("UPDATE stok SET jumlah_stok = jumlah_stok + :jumlah WHERE produk_id = :produk_id");
                } else { // keluar
                    $stmt = $database->prepare("UPDATE stok SET jumlah_stok = jumlah_stok - :jumlah WHERE produk_id = :produk_id");
                }
                
                $stmt->bindParam(':jumlah', $jumlah);
                $stmt->bindParam(':produk_id', $produk_id);
                $stmt->execute();
            }
            
            // Commit transaction
            $database->commit();
            
            // Clear session form data and reset product rows
            unset($_SESSION['form_data']);
            $_SESSION['product_rows'] = 1;
            
            $success_message = "Transaction successfully recorded!";
            
            // Redirect to transactions list after successful submission
            header("Location: index.php?success=1");
            exit();
        } catch (PDOException $e) {
            // Rollback transaction on error
            $database->rollBack();
            $error_message = "Transaction failed: " . $e->getMessage();
        }
    }
}

// Helper function to get product details by id
function getProductDetails($products, $produk_id) {
    foreach ($products as $product) {
        if ($product['id'] == $produk_id) {
            return $product;
        }
    }
    return null;
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Add Transaction</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css">
    <style>
        .transaction-form {
            background-color: #fff;
            border-radius: 8px;
            box-shadow: 0 0 15px rgba(0, 0, 0, 0.1);
            padding: 20px;
            margin-bottom: 30px;
        }
        
        .form-section {
            margin-bottom: 20px;
        }
        
        .product-row {
            background-color: #f8f9fa;
            padding: 10px;
            border-radius: 5px;
            margin-bottom: 10px;
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
                    <h1 class="h2">Add New Transaction</h1>
                    <div class="btn-toolbar mb-2 mb-md-0">
                        <a href="index.php" class="btn btn-sm btn-outline-secondary">
                            <i class="bi bi-arrow-left"></i> Back to Transactions
                        </a>
                    </div>
                </div>
                
                <?php if(isset($error_message)): ?>
                <div class="alert alert-danger alert-dismissible fade show" role="alert">
                    <?php echo $error_message; ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
                <?php endif; ?>
                
                <!-- Transaction Form -->
                <div class="transaction-form">
                    <form action="add_transaksi.php" method="post" id="transactionForm">
                        <div class="row mb-3">
                            <div class="col-md-6">
                                <label for="tanggal_transaksi" class="form-label">Transaction Date</label>
                                <input type="date" class="form-control" id="tanggal_transaksi" name="tanggal_transaksi" 
                                       value="<?php echo isset($_SESSION['form_data']['tanggal_transaksi']) ? $_SESSION['form_data']['tanggal_transaksi'] : date('Y-m-d'); ?>" required>
                            </div>
                            <div class="col-md-6">
                                <label for="jenis_transaksi" class="form-label">Transaction Type</label>
                                <select class="form-select" id="jenis_transaksi" name="jenis_transaksi" required>
                                    <option value="masuk" <?php echo (isset($_SESSION['form_data']['jenis_transaksi']) && $_SESSION['form_data']['jenis_transaksi'] == 'masuk') ? 'selected' : ''; ?>>Stock In (Masuk)</option>
                                    <option value="keluar" <?php echo (isset($_SESSION['form_data']['jenis_transaksi']) && $_SESSION['form_data']['jenis_transaksi'] == 'keluar') ? 'selected' : ''; ?>>Stock Out (Keluar)</option>
                                </select>
                            </div>
                        </div>
                        
                        <div class="form-section">
                            <h5>Products</h5>
                            <div id="products-container">
                                <?php for ($i = 0; $i < $_SESSION['product_rows']; $i++): ?>
                                    <div class="product-row row mb-3">
                                        <div class="col-md-6">
                                            <label class="form-label">Product</label>
                                            <select name="produk_id[]" class="form-select product-select" required onchange="document.getElementById('update_form_button').click();">
                                                <option value="">Select Product</option>
                                                <?php foreach($products as $product): ?>
                                                    <?php $selected = isset($_SESSION['form_data']['produk_id'][$i]) && $_SESSION['form_data']['produk_id'][$i] == $product['id'] ? 'selected' : ''; ?>
                                                    <option value="<?php echo $product['id']; ?>" <?php echo $selected; ?>>
                                                        <?php echo htmlspecialchars($product['nama_produk']); ?> (Stock: <?php echo $product['jumlah_stok']; ?>)
                                                    </option>
                                                <?php endforeach; ?>
                                            </select>
                                        </div>
                                        <div class="col-md-4">
                                            <label class="form-label">Quantity</label>
                                            <input type="number" name="jumlah[]" class="form-control quantity-input" min="1" 
                                                   value="<?php echo isset($_SESSION['form_data']['jumlah'][$i]) ? $_SESSION['form_data']['jumlah'][$i] : 1; ?>" required>
                                        </div>
                                        <div class="col-md-2 d-flex align-items-end mb-2">
                                            <?php if ($_SESSION['product_rows'] > 1): ?>
                                                <button type="submit" name="remove_product_row" class="btn btn-danger btn-sm" 
                                                        onclick="document.getElementById('remove_index').value='<?php echo $i; ?>';">
                                                    <i class="bi bi-trash"></i>
                                                </button>
                                                <input type="hidden" id="remove_index" name="remove_index" value="">
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                <?php endfor; ?>
                            </div>
                            
                            <div class="text-start mt-3">
                                <button type="submit" name="add_product_row" class="btn btn-success">
                                    <i class="bi bi-plus-circle"></i> Add Product
                                </button>
                                <button type="submit" id="update_form_button" name="update_form" class="btn btn-secondary ms-2">
                                    <i class="bi bi-arrow-clockwise"></i> Update Form
                                </button>
                            </div>
                        </div>
                        
                        <div class="mt-4 text-end">
                            <a href="index.php" class="btn btn-secondary me-2">Cancel</a>
                            <button type="submit" name="submit_transaction" class="btn btn-primary">Save Transaction</button>
                        </div>
                    </form>
                </div>
            </main>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>