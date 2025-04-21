<?php
// Start session if not already started
session_start();

// Database connection with PDO
require_once '../config/database.php';





$transaction_id = $_GET['id'];

try {
    // Get transaction data before deletion to adjust stock
    $stmt = $database->prepare("SELECT produk_id, jumlah, jenis_transaksi FROM transaksi WHERE id = :id");
    $stmt->bindParam(':id', $transaction_id);
    $stmt->execute();
    $transaction = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$transaction) {
        // Transaction not found
        $_SESSION['error_message'] = "Transaction not found!";
        header("Location: index.php");
        exit();
    }
    
    // Begin transaction
    $database->beginTransaction();
    
    // Adjust stock based on transaction type
    if ($transaction['jenis_transaksi'] == 'masuk') {
        // If deleting a stock in transaction, reduce stock
        $stmt = $database->prepare("UPDATE stok SET jumlah_stok = jumlah_stok - :jumlah WHERE produk_id = :produk_id");
    } else {
        // If deleting a stock out transaction, add stock back
        $stmt = $database->prepare("UPDATE stok SET jumlah_stok = jumlah_stok + :jumlah WHERE produk_id = :produk_id");
    }
    
    $stmt->bindParam(':jumlah', $transaction['jumlah']);
    $stmt->bindParam(':produk_id', $transaction['produk_id']);
    $stmt->execute();
    
    // Delete the transaction
    $stmt = $database->prepare("DELETE FROM transaksi WHERE id = :id");
    $stmt->bindParam(':id', $transaction_id);
    $stmt->execute();
    
    // Commit transaction
    $database->commit();
    
    $_SESSION['success_message'] = "Transaction deleted successfully!";
    
} catch (PDOException $e) {
    // Rollback transaction on error
    $database->rollBack();
    $_SESSION['error_message'] = "Failed to delete transaction: " . $e->getMessage();
}

// Redirect back to transactions page
header("Location: index.php");
exit();
?>