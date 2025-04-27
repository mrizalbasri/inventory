<?php
class Stock {
    private $db;
    
    public function __construct($database) {
        $this->db = $database;
    }
    
    // Get all stock items with product information
    public function getAllStock() {
        $sql = "SELECT s.id, s.produk_id, s.jumlah_stok, s.updated_at, p.nama_produk 
                FROM stok s 
                JOIN produk p ON s.produk_id = p.id
                ORDER BY s.jumlah_stok ASC";
        $result = $this->db->prepare($sql);
        $result->execute();
        return $result->fetchAll(PDO::FETCH_ASSOC);
    }
    
    // Get stock by ID
    public function getStockById($id) {
        $sql = "SELECT s.id, s.produk_id, s.jumlah_stok, s.updated_at, p.nama_produk 
                FROM stok s 
                JOIN produk p ON s.produk_id = p.id 
                WHERE s.id = ?";
        $result = $this->db->prepare($sql);
        $result->execute([$id]);
        return $result->fetch(PDO::FETCH_ASSOC);
    }
    
    // Get stock count
    public function getStockCount() {
        $sql = "SELECT COUNT(*) as count FROM stok";
        $result = $this->db->prepare($sql);
        $result->execute();
        $count = $result->fetch(PDO::FETCH_ASSOC);
        return $count['count'];
    }
    
    // Get product count
    public function getProductCount() {
        $sql = "SELECT COUNT(*) as count FROM produk";
        $result = $this->db->prepare($sql);
        $result->execute();
        $count = $result->fetch(PDO::FETCH_ASSOC);
        return $count['count'];
    }
    
    // Get low stock count
    public function getLowStockCount() {
        $sql = "SELECT COUNT(*) as count FROM stok WHERE jumlah_stok < 10";
        $result = $this->db->prepare($sql);
        $result->execute();
        $count = $result->fetch(PDO::FETCH_ASSOC);
        return $count['count'];
    }
    
    // Get total stock value
    public function getTotalStockValue() {
        $sql = "SELECT SUM(s.jumlah_stok * p.harga) as total_value 
                FROM stok s 
                JOIN produk p ON s.produk_id = p.id";
        $result = $this->db->prepare($sql);
        $result->execute();
        $value = $result->fetch(PDO::FETCH_ASSOC);
        return $value['total_value'] ?: 0;
    }
    
    // Get all products
    public function getAllProducts() {
        $sql = "SELECT id, nama_produk FROM produk ORDER BY nama_produk ASC";
        $result = $this->db->prepare($sql);
        $result->execute();
        return $result->fetchAll(PDO::FETCH_ASSOC);
    }
    
    // Add stock
    public function addStock($produk_id, $jumlah_stok) {
        $sql = "INSERT INTO stok (produk_id, jumlah_stok) VALUES (?, ?)";
        $result = $this->db->prepare($sql);
        return $result->execute([$produk_id, $jumlah_stok]);
    }
    
    // Update stock
    public function updateStock($id, $jumlah_stok) {
        $sql = "UPDATE stok SET jumlah_stok = ? WHERE id = ?";
        $result = $this->db->prepare($sql);
        return $result->execute([$jumlah_stok, $id]);
    }
    
    // Delete stock
    public function deleteStock($id) {
        $sql = "DELETE FROM stok WHERE id = ?";
        $result = $this->db->prepare($sql);
        return $result->execute([$id]);
    }
    
    // Filter stock items
    public function filterStockItems($stockItems, $search = '', $stockLevel = '') {
        $filteredItems = $stockItems;
        
        // Filter by search term
        if (!empty($search)) {
            $search = strtolower($search);
            $filteredItems = array_filter($filteredItems, function($item) use ($search) {
                return strpos(strtolower($item['nama_produk']), $search) !== false;
            });
        }
        
        // Filter by stock level
        if (!empty($stockLevel)) {
            $filteredItems = array_filter($filteredItems, function($item) use ($stockLevel) {
                if ($stockLevel == 'low') {
                    return $item['jumlah_stok'] < 10;
                } elseif ($stockLevel == 'medium') {
                    return $item['jumlah_stok'] >= 10 && $item['jumlah_stok'] < 20;
                } elseif ($stockLevel == 'good') {
                    return $item['jumlah_stok'] >= 20;
                }
                return true;
            });
        }
        
        return $filteredItems;
    }
}
?>