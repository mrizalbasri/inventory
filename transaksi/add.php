<?php
// Start session if not already started
session_start();

// Include database connection
require_once '../config/database.php';

// Include Transaction class
require_once 'Transaction.php';

// Create database connection
$database = new Database();
$db = $database->getConnection();

// Create Transaction instance
$transactionManager = new Transaction($db);

// Load products for dropdown selection
try {
    $productStmt = $db->query("SELECT p.id, p.nama_produk, s.jumlah_stok 
                            FROM produk p 
                            LEFT JOIN stok s ON p.id = s.produk_id 
                            ORDER BY p.nama_produk");
    $products = $productStmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $error_message = "Error loading products: " . $e->getMessage();
}

// Initialize product rows in session if not set
if (!isset($_SESSION['product_rows'])) {
    $_SESSION['product_rows'] = 1;
}

// Handle form submissions
if (isset($_POST['add_product_row'])) {
    $_SESSION['product_rows']++;
    // Store current form data before redirecting
    if (isset($_POST['tanggal_transaksi'])) {
        $_SESSION['form_data'] = [
            'tanggal_transaksi' => $_POST['tanggal_transaksi'],
            'jenis_transaksi' => $_POST['jenis_transaksi'],
            'produk_id' => $_POST['produk_id'] ?? [],
            'jumlah' => $_POST['jumlah'] ?? []
        ];
    }
    header("Location: add.php");
    exit();
}

if (isset($_POST['remove_product_row'])) {
    $remove_index = $_POST['remove_index'];
    
    // Store current form data excluding removed row
    if (isset($_POST['tanggal_transaksi'])) {
        $produk_ids = $_POST['produk_id'] ?? [];
        $jumlah_values = $_POST['jumlah'] ?? [];
        
        // Remove the item at the specified index
        if (isset($produk_ids[$remove_index])) {
            unset($produk_ids[$remove_index]);
            $produk_ids = array_values($produk_ids); // Re-index array
        }
        
        if (isset($jumlah_values[$remove_index])) {
            unset($jumlah_values[$remove_index]);
            $jumlah_values = array_values($jumlah_values); // Re-index array
        }
        
        $_SESSION['form_data'] = [
            'tanggal_transaksi' => $_POST['tanggal_transaksi'],
            'jenis_transaksi' => $_POST['jenis_transaksi'],
            'produk_id' => $produk_ids,
            'jumlah' => $jumlah_values
        ];
    }
    
    $_SESSION['product_rows']--;
    if ($_SESSION['product_rows'] < 1) {
        $_SESSION['product_rows'] = 1;
    }
    
    header("Location: add.php");
    exit();
}

if (isset($_POST['update_form'])) {
    // Store form data to session
    if (isset($_POST['tanggal_transaksi'])) {
        $_SESSION['form_data'] = [
            'tanggal_transaksi' => $_POST['tanggal_transaksi'],
            'jenis_transaksi' => $_POST['jenis_transaksi'],
            'produk_id' => $_POST['produk_id'] ?? [],
            'jumlah' => $_POST['jumlah'] ?? []
        ];
    }
    header("Location: add.php");
    exit();
}

$error_message = null;

// Process transaction submission
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['submit_transaction'])) {
    try {
        $tanggal_transaksi = $_POST['tanggal_transaksi'];
        $jenis_transaksi = $_POST['jenis_transaksi'];
        $produk_ids = $_POST['produk_id'] ?? [];
        $quantities = $_POST['jumlah'] ?? [];
        $user_id = $_SESSION['user_id'];
        
        // Validate inputs
        if (empty($tanggal_transaksi)) {
            throw new Exception("Transaction date is required");
        }
        
        if (empty($jenis_transaksi)) {
            throw new Exception("Transaction type is required");
        }
        
        if (empty($produk_ids) || count(array_filter($produk_ids)) == 0) {
            throw new Exception("At least one product must be selected");
        }
        
        // Validate quantities
        foreach ($quantities as $index => $qty) {
            if (!is_numeric($qty) || $qty <= 0) {
                throw new Exception("Quantity must be a positive number");
            }
            
            // If transaction type is 'keluar', check stock availability
            if ($jenis_transaksi == 'keluar') {
                $produk_id = $produk_ids[$index];
                
                // Skip if no product selected
                if (empty($produk_id)) continue;
                
                // Get current stock
                $stockStmt = $db->prepare("SELECT jumlah_stok FROM stok WHERE produk_id = ?");
                $stockStmt->execute([$produk_id]);
                $stock = $stockStmt->fetch(PDO::FETCH_ASSOC);
                
                if (!$stock || $stock['jumlah_stok'] < $qty) {
                    // Find product name for error message
                    foreach ($products as $product) {
                        if ($product['id'] == $produk_id) {
                            $productName = $product['nama_produk'];
                            break;
                        }
                    }
                    throw new Exception("Insufficient stock for product: " . ($productName ?? "ID: $produk_id"));
                }
            }
        }
        
        // Process transaction using the Transaction class
        if ($transactionManager->addTransaction($tanggal_transaksi, $jenis_transaksi, $produk_ids, $quantities, $user_id)) {
            // Clear form data after successful submission
            unset($_SESSION['form_data']);
            $_SESSION['product_rows'] = 1;
            
            // Set success message
            $_SESSION['success_message'] = "Transaction added successfully!";
            
            header("Location: index.php");
            exit();
        }
    } catch (Exception $e) {
        $error_message = $e->getMessage();
    }
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
            padding: 15px;
            border-radius: 5px;
            margin-bottom: 15px;
            border-left: 4px solid #0d6efd;
        }
        
        .remove-btn {
            margin-top: 32px;
        }
        
        /* Additional styling for better UI */
        .main-content {
            padding-top: 20px;
            padding-bottom: 50px;
        }
        
        .form-label {
            font-weight: 500;
        }
        
        .alert {
            border-radius: 8px;
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
                    <i class="bi bi-exclamation-triangle-fill me-2"></i> <?php echo htmlspecialchars($error_message); ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
                <?php endif; ?>
                
                <!-- Transaction Form -->
                <div class="transaction-form">
                    <form action="add.php" method="post" id="transactionForm">
                        <div class="row mb-4">
                            <div class="col-md-6">
                                <label for="tanggal_transaksi" class="form-label">Transaction Date</label>
                                <input type="date" class="form-control" id="tanggal_transaksi" name="tanggal_transaksi" 
                                       value="<?php echo isset($_SESSION['form_data']['tanggal_transaksi']) ? htmlspecialchars($_SESSION['form_data']['tanggal_transaksi']) : date('Y-m-d'); ?>" required>
                            </div>
                            <div class="col-md-6">
                                <label for="jenis_transaksi" class="form-label">Transaction Type</label>
                                <select class="form-select" id="jenis_transaksi" name="jenis_transaksi" required>
                                    <option value="">Select Type</option>
                                    <option value="masuk" <?php echo (isset($_SESSION['form_data']['jenis_transaksi']) && $_SESSION['form_data']['jenis_transaksi'] == 'masuk') ? 'selected' : ''; ?>>Stock In (Masuk)</option>
                                    <option value="keluar" <?php echo (isset($_SESSION['form_data']['jenis_transaksi']) && $_SESSION['form_data']['jenis_transaksi'] == 'keluar') ? 'selected' : ''; ?>>Stock Out (Keluar)</option>
                                </select>
                            </div>
                        </div>
                        
                        <div class="form-section">
                            <h5 class="mb-3"><i class="bi bi-box-seam me-2"></i>Products</h5>
                            <div id="products-container">
                                <?php for ($i = 0; $i < $_SESSION['product_rows']; $i++): ?>
                                    <div class="product-row row">
                                        <div class="col-md-5">
                                            <label class="form-label">Product</label>
                                            <select name="produk_id[]" class="form-select product-select" required>
                                                <option value="">Select Product</option>
                                                <?php foreach($products as $product): ?>
                                                    <?php $selected = isset($_SESSION['form_data']['produk_id'][$i]) && $_SESSION['form_data']['produk_id'][$i] == $product['id'] ? 'selected' : ''; ?>
                                                    <option value="<?php echo htmlspecialchars($product['id']); ?>" <?php echo $selected; ?>>
                                                        <?php echo htmlspecialchars($product['nama_produk']); ?> 
                                                        (Stock: <?php echo htmlspecialchars($product['jumlah_stok']); ?>)
                                                    </option>
                                                <?php endforeach; ?>
                                            </select>
                                        </div>
                                        <div class="col-md-5">
                                            <label class="form-label">Quantity</label>
                                            <input type="number" name="jumlah[]" class="form-control quantity-input" min="1" 
                                                   value="<?php echo isset($_SESSION['form_data']['jumlah'][$i]) ? htmlspecialchars($_SESSION['form_data']['jumlah'][$i]) : 1; ?>" required>
                                        </div>
                                        <div class="col-md-2">
                                            <?php if ($_SESSION['product_rows'] > 1): ?>
                                                <button type="submit" name="remove_product_row" class="btn btn-outline-danger btn-sm remove-btn" 
                                                        onclick="document.getElementById('remove_index').value='<?php echo $i; ?>';">
                                                    <i class="bi bi-trash"></i> Remove
                                                </button>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                <?php endfor; ?>
                                <input type="hidden" id="remove_index" name="remove_index" value="">
                            </div>
                            
                            <div class="text-start mt-3">
                                <button type="submit" name="add_product_row" class="btn btn-success">
                                    <i class="bi bi-plus-circle"></i> Add Another Product
                                </button>
                                <button type="submit" id="update_form_button" name="update_form" class="btn btn-secondary ms-2">
                                    <i class="bi bi-arrow-clockwise"></i> Update Form
                                </button>
                            </div>
                        </div>
                        
                        <div class="mt-4 d-flex justify-content-between">
                            <a href="index.php" class="btn btn-secondary">
                                <i class="bi bi-x-circle"></i> Cancel
                            </a>
                            <button type="submit" name="submit_transaction" class="btn btn-primary">
                                <i class="bi bi-save"></i> Save Transaction
                            </button>
                        </div>
                    </form>
                </div>
            </main>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
    document.addEventListener('DOMContentLoaded', function() {
        // Add event listener to product selects to update the form
        const productSelects = document.querySelectorAll('.product-select');
        productSelects.forEach(select => {
            select.addEventListener('change', function() {
                document.getElementById('update_form_button').click();
            });
        });
        
        // Add event listener to quantity inputs
        const quantityInputs = document.querySelectorAll('.quantity-input');
        quantityInputs.forEach(input => {
            input.addEventListener('change', function() {
                document.getElementById('update_form_button').click();
            });
        });
    });
    </script>
</body>
</html>