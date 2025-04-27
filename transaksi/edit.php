<?php
// Start session if not already started
session_start();

// Database connection with PDO
require_once '../config/database.php';

// Create database connection
$db = new Database();
$database = $db->getConnection();

// Check if transaction ID is provided
if (!isset($_GET['id']) || empty($_GET['id'])) {
    header("Location: index.php");
    exit();
}

$transaction_id = $_GET['id'];

// Get transaction data
try {
    $stmt = $database->prepare("SELECT * FROM transaksi WHERE id = :id");
    $stmt->bindParam(':id', $transaction_id);
    $stmt->execute();
    $transaction = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$transaction) {
        // Transaction not found
        header("Location: index.php");
        exit();
    }
    
    // Format transaction data for form
    $_SESSION['edit_form_data'] = [
        'id' => $transaction['id'],
        'tanggal_transaksi' => $transaction['tanggal_transaksi'],
        'jenis_transaksi' => $transaction['jenis_transaksi'],
        'produk_id' => $transaction['produk_id'],
        'jumlah' => $transaction['jumlah']
    ];
    
} catch (PDOException $e) {
    $error_message = "Error loading transaction: " . $e->getMessage();
}

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

// Process update form if submitted
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['update_transaction'])) {
    try {
        // Get original transaction data to calculate stock adjustment
        $stmt = $database->prepare("SELECT produk_id, jumlah, jenis_transaksi FROM transaksi WHERE id = :id");
        $stmt->bindParam(':id', $transaction_id);
        $stmt->execute();
        $original_transaction = $stmt->fetch(PDO::FETCH_ASSOC);
        
        // Begin transaction
        $database->beginTransaction();
        
        // Get form data
        $tanggal_transaksi = $_POST['tanggal_transaksi'];
        $jenis_transaksi = $_POST['jenis_transaksi'];
        $produk_id = $_POST['produk_id'];
        $jumlah = $_POST['jumlah'];
        
        // Restore original stock state (reverse the original transaction)
        if ($original_transaction['jenis_transaksi'] == 'masuk') {
            // If original was stock in, then reduce stock
            $stmt = $database->prepare("UPDATE stok SET jumlah_stok = jumlah_stok - :jumlah WHERE produk_id = :produk_id");
        } else {
            // If original was stock out, then add stock
            $stmt = $database->prepare("UPDATE stok SET jumlah_stok = jumlah_stok + :jumlah WHERE produk_id = :produk_id");
        }
        $stmt->bindParam(':jumlah', $original_transaction['jumlah']);
        $stmt->bindParam(':produk_id', $original_transaction['produk_id']);
        $stmt->execute();
        
        // Apply the new transaction to stock
        if ($jenis_transaksi == 'masuk') {
            // For stock in, add stock
            $stmt = $database->prepare("UPDATE stok SET jumlah_stok = jumlah_stok + :jumlah WHERE produk_id = :produk_id");
        } else {
            // For stock out, reduce stock
            $stmt = $database->prepare("UPDATE stok SET jumlah_stok = jumlah_stok - :jumlah WHERE produk_id = :produk_id");
        }
        $stmt->bindParam(':jumlah', $jumlah);
        $stmt->bindParam(':produk_id', $produk_id);
        $stmt->execute();
        
        // Update transaction record
        $stmt = $database->prepare("UPDATE transaksi 
            SET produk_id = :produk_id, 
                jenis_transaksi = :jenis_transaksi, 
                jumlah = :jumlah, 
                tanggal_transaksi = :tanggal_transaksi 
            WHERE id = :id");
        
        $stmt->bindParam(':produk_id', $produk_id);
        $stmt->bindParam(':jenis_transaksi', $jenis_transaksi);
        $stmt->bindParam(':jumlah', $jumlah);
        $stmt->bindParam(':tanggal_transaksi', $tanggal_transaksi);
        $stmt->bindParam(':id', $transaction_id);
        
        $stmt->execute();
        
        // Commit transaction
        $database->commit();
        
        // Clear session form data
        unset($_SESSION['edit_form_data']);
        
        // Set success message and redirect
        $_SESSION['success_message'] = "Transaction updated successfully!";
        header("Location: index.php");
        exit();
        
    } catch (PDOException $e) {
        // Rollback transaction on error
        $database->rollBack();
        $error_message = "Transaction update failed: " . $e->getMessage();
    }
}

// Get product name for the current transaction
$product_name = "";
foreach ($products as $product) {
    if ($product['id'] == $_SESSION['edit_form_data']['produk_id']) {
        $product_name = $product['nama_produk'];
        break;
    }
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Transaction</title>
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
                    <h1 class="h2">Edit Transaction</h1>
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
                <form action="edit.php?id=<?php echo $transaction_id; ?>" method="post">
                        <div class="row mb-3">
                            <div class="col-md-6">
                                <label for="tanggal_transaksi" class="form-label">Transaction Date</label>
                                <input type="date" class="form-control" id="tanggal_transaksi" name="tanggal_transaksi" 
                                       value="<?php echo $_SESSION['edit_form_data']['tanggal_transaksi']; ?>" required>
                            </div>
                            <div class="col-md-6">
                                <label for="jenis_transaksi" class="form-label">Transaction Type</label>
                                <select class="form-select" id="jenis_transaksi" name="jenis_transaksi" required>
                                    <option value="masuk" <?php echo ($_SESSION['edit_form_data']['jenis_transaksi'] == 'masuk') ? 'selected' : ''; ?>>Stock In (Masuk)</option>
                                    <option value="keluar" <?php echo ($_SESSION['edit_form_data']['jenis_transaksi'] == 'keluar') ? 'selected' : ''; ?>>Stock Out (Keluar)</option>
                                </select>
                            </div>
                        </div>
                        
                        <div class="form-section">
                            <div class="product-row row mb-3">
                                <div class="col-md-6">
                                    <label class="form-label">Product</label>
                                    <select name="produk_id" class="form-select" required>
                                        <option value="">Select Product</option>
                                        <?php foreach($products as $product): ?>
                                            <?php $selected = ($_SESSION['edit_form_data']['produk_id'] == $product['id']) ? 'selected' : ''; ?>
                                            <option value="<?php echo $product['id']; ?>" <?php echo $selected; ?>>
                                                <?php echo htmlspecialchars($product['nama_produk']); ?> (Stock: <?php echo $product['jumlah_stok']; ?>)
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label">Quantity</label>
                                    <input type="number" name="jumlah" class="form-control" min="1" 
                                           value="<?php echo $_SESSION['edit_form_data']['jumlah']; ?>" required>
                                </div>
                            </div>
                        </div>
                        
                        <div class="mt-4 text-end">
                            <a href="index.php" class="btn btn-secondary me-2">Cancel</a>
                            <button type="submit" name="update_transaction" class="btn btn-primary">Update Transaction</button>
                        </div>
                    </form>
                </div>
            </main>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>