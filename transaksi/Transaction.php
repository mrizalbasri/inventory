<?php
class Transaction {
    private $database;
    
    /**
     * Constructor - initialize database connection
     * @param PDO $db Database connection
     */
    public function __construct($db) {
        $this->database = $db;
    }
    
    /**
     * Get all transactions with additional information
     * @return array Array of transactions
     */
    public function getAllTransactions() {
        try {
            $stmt = $this->database->query("SELECT t.*, p.nama_produk, p.harga, u.username 
                FROM transaksi t 
                LEFT JOIN produk p ON t.produk_id = p.id
                LEFT JOIN users u ON t.created_by = u.id 
                ORDER BY t.tanggal_transaksi DESC, t.id DESC");
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            throw new Exception("Error loading transactions: " . $e->getMessage());
        }
    }
    
    /**
     * Get filtered transactions based on date range and type
     * @param string $dateStart Start date
     * @param string $dateEnd End date
     * @param string $type Transaction type (masuk/keluar)
     * @return array Filtered transactions
     */
    public function getFilteredTransactions($dateStart = null, $dateEnd = null, $type = null) {
        try {
            $sql = "SELECT t.*, p.nama_produk, p.harga, u.username 
                FROM transaksi t 
                LEFT JOIN produk p ON t.produk_id = p.id
                LEFT JOIN users u ON t.created_by = u.id 
                WHERE 1=1";
            $params = [];
            
            if ($dateStart) {
                $sql .= " AND t.tanggal_transaksi >= :dateStart";
                $params[':dateStart'] = $dateStart;
            }
            
            if ($dateEnd) {
                $sql .= " AND t.tanggal_transaksi <= :dateEnd";
                $params[':dateEnd'] = $dateEnd;
            }
            
            if ($type) {
                $sql .= " AND t.jenis_transaksi = :type";
                $params[':type'] = $type;
            }
            
            $sql .= " ORDER BY t.tanggal_transaksi DESC, t.id DESC";
            
            $stmt = $this->database->prepare($sql);
            foreach ($params as $key => $value) {
                $stmt->bindValue($key, $value);
            }
            $stmt->execute();
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            throw new Exception("Error filtering transactions: " . $e->getMessage());
        }
    }
    
    /**
     * Get transaction by ID
     * @param int $id Transaction ID
     * @return array Transaction data
     */
    public function getTransactionById($id) {
        try {
            $stmt = $this->database->prepare("SELECT * FROM transaksi WHERE id = :id");
            $stmt->bindParam(':id', $id);
            $stmt->execute();
            return $stmt->fetch(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            throw new Exception("Error loading transaction: " . $e->getMessage());
        }
    }
    
    /**
     * Add new transaction(s)
     * @param string $tanggalTransaksi Transaction date
     * @param string $jenisTransaksi Transaction type (masuk/keluar)
     * @param array $produkIds Array of product IDs
     * @param array $quantities Array of quantities
     * @param int $userId User ID who created the transaction
     * @return bool True if successful
     */
    public function addTransaction($tanggalTransaksi, $jenisTransaksi, $produkIds, $quantities, $userId) {
        try {
            $this->database->beginTransaction();
            
            foreach ($produkIds as $key => $produkId) {
                if (empty($produkId)) continue;
                
                $jumlah = $quantities[$key];
                
                // Insert transaction
                $stmt = $this->database->prepare("INSERT INTO transaksi (produk_id, jenis_transaksi, jumlah, tanggal_transaksi, created_by) 
                                  VALUES (:produk_id, :jenis_transaksi, :jumlah, :tanggal_transaksi, :created_by)");
                
                $stmt->bindParam(':produk_id', $produkId);
                $stmt->bindParam(':jenis_transaksi', $jenisTransaksi);
                $stmt->bindParam(':jumlah', $jumlah);
                $stmt->bindParam(':tanggal_transaksi', $tanggalTransaksi);
                $stmt->bindParam(':created_by', $userId);
                
                $stmt->execute();
                
                // Update stock based on transaction type
                $this->updateStock($produkId, $jumlah, $jenisTransaksi);
            }
            
            $this->database->commit();
            return true;
        } catch (PDOException $e) {
            $this->database->rollBack();
            throw new Exception("Transaction failed: " . $e->getMessage());
        }
    }
    
    /**
     * Update existing transaction
     * @param int $id Transaction ID
     * @param string $tanggalTransaksi Transaction date
     * @param string $jenisTransaksi Transaction type
     * @param int $produkId Product ID
     * @param int $jumlah Quantity
     * @return bool True if successful
     */
    public function updateTransaction($id, $tanggalTransaksi, $jenisTransaksi, $produkId, $jumlah) {
        try {
            // Get original transaction data to calculate stock adjustment
            $originalTransaction = $this->getTransactionById($id);
            if (!$originalTransaction) {
                throw new Exception("Transaction not found");
            }
            
            $this->database->beginTransaction();
            
            // Restore original stock state (reverse the original transaction)
            $this->reverseStockUpdate($originalTransaction['produk_id'], $originalTransaction['jumlah'], $originalTransaction['jenis_transaksi']);
            
            // Apply the new transaction to stock
            $this->updateStock($produkId, $jumlah, $jenisTransaksi);
            
            // Update transaction record
            $stmt = $this->database->prepare("UPDATE transaksi 
                SET produk_id = :produk_id, 
                    jenis_transaksi = :jenis_transaksi, 
                    jumlah = :jumlah, 
                    tanggal_transaksi = :tanggal_transaksi 
                WHERE id = :id");
            
            $stmt->bindParam(':produk_id', $produkId);
            $stmt->bindParam(':jenis_transaksi', $jenisTransaksi);
            $stmt->bindParam(':jumlah', $jumlah);
            $stmt->bindParam(':tanggal_transaksi', $tanggalTransaksi);
            $stmt->bindParam(':id', $id);
            
            $stmt->execute();
            
            $this->database->commit();
            return true;
            
        } catch (PDOException $e) {
            $this->database->rollBack();
            throw new Exception("Transaction update failed: " . $e->getMessage());
        }
    }
    
    /**
     * Delete transaction
     * @param int $id Transaction ID
     * @return bool True if successful
     */
    public function deleteTransaction($id) {
        try {
            // Get transaction data to adjust stock
            $transaction = $this->getTransactionById($id);
            if (!$transaction) {
                throw new Exception("Transaction not found");
            }
            
            $this->database->beginTransaction();
            
            // Reverse the effect on stock
            $this->reverseStockUpdate($transaction['produk_id'], $transaction['jumlah'], $transaction['jenis_transaksi']);
            
            // Delete transaction
            $stmt = $this->database->prepare("DELETE FROM transaksi WHERE id = :id");
            $stmt->bindParam(':id', $id);
            $stmt->execute();
            
            $this->database->commit();
            return true;
            
        } catch (PDOException $e) {
            $this->database->rollBack();
            throw new Exception("Delete transaction failed: " . $e->getMessage());
        }
    }
    
    /**
     * Calculate transaction statistics
     * @return array Statistics data
     */
    public function calculateStatistics() {
        $transactions = $this->getAllTransactions();
        $stats = [
            'total_masuk' => 0,
            'total_keluar' => 0,
            'total_value_masuk' => 0,
            'total_value_keluar' => 0
        ];
        
        if (count($transactions) > 0) {
            foreach ($transactions as $transaction) {
                if ($transaction['jenis_transaksi'] == 'masuk') {
                    $stats['total_masuk'] += $transaction['jumlah'];
                    $stats['total_value_masuk'] += ($transaction['jumlah'] * $transaction['harga']);
                } else {
                    $stats['total_keluar'] += $transaction['jumlah'];
                    $stats['total_value_keluar'] += ($transaction['jumlah'] * $transaction['harga']);
                }
            }
        }
        
        return $stats;
    }
    
    /**
     * Get monthly transaction statistics
     * @param int $month Month number (1-12)
     * @param int $year Year (e.g., 2025)
     * @return array Monthly statistics
     */
    public function getMonthlyStatistics($month = null, $year = null) {
        if ($month === null) {
            $month = date('m');
        }
        if ($year === null) {
            $year = date('Y');
        }
        
        try {
            $stmt = $this->database->prepare("SELECT 
                SUM(CASE WHEN jenis_transaksi = 'masuk' THEN 1 ELSE 0 END) as count_masuk,
                SUM(CASE WHEN jenis_transaksi = 'keluar' THEN 1 ELSE 0 END) as count_keluar,
                SUM(CASE WHEN jenis_transaksi = 'masuk' THEN jumlah ELSE 0 END) as total_masuk,
                SUM(CASE WHEN jenis_transaksi = 'keluar' THEN jumlah ELSE 0 END) as total_keluar
                FROM transaksi 
                WHERE MONTH(tanggal_transaksi) = :month AND YEAR(tanggal_transaksi) = :year");
            $stmt->bindParam(':month', $month);
            $stmt->bindParam(':year', $year);
            $stmt->execute();
            return $stmt->fetch(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            throw new Exception("Error calculating monthly statistics: " . $e->getMessage());
        }
    }
    
    /**
     * Helper method to update stock based on transaction type
     * @param int $produkId Product ID
     * @param int $jumlah Quantity
     * @param string $jenisTransaksi Transaction type
     */
    private function updateStock($produkId, $jumlah, $jenisTransaksi) {
        if ($jenisTransaksi == 'masuk') {
            $stmt = $this->database->prepare("UPDATE stok SET jumlah_stok = jumlah_stok + :jumlah WHERE produk_id = :produk_id");
        } else { // keluar
            $stmt = $this->database->prepare("UPDATE stok SET jumlah_stok = jumlah_stok - :jumlah WHERE produk_id = :produk_id");
        }
        
        $stmt->bindParam(':jumlah', $jumlah);
        $stmt->bindParam(':produk_id', $produkId);
        $stmt->execute();
    }
    
    /**
     * Helper method to reverse stock update for editing/deleting transactions
     * @param int $produkId Product ID
     * @param int $jumlah Quantity
     * @param string $jenisTransaksi Transaction type
     */
    private function reverseStockUpdate($produkId, $jumlah, $jenisTransaksi) {
        if ($jenisTransaksi == 'masuk') {
            // If original was stock in, then reduce stock
            $stmt = $this->database->prepare("UPDATE stok SET jumlah_stok = jumlah_stok - :jumlah WHERE produk_id = :produk_id");
        } else {
            // If original was stock out, then add stock
            $stmt = $this->database->prepare("UPDATE stok SET jumlah_stok = jumlah_stok + :jumlah WHERE produk_id = :produk_id");
        }
        $stmt->bindParam(':jumlah', $jumlah);
        $stmt->bindParam(':produk_id', $produkId);
        $stmt->execute();
    }
}