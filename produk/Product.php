<?php

class Product{
    private $database;
    private $recordsPerPage = 10;
    
    public function __construct($database = null) {
        if ($database === null) {
            // Jika tidak ada database yang diberikan, buat koneksi baru
            $db = new Database();
            $this->database = $db->getConnection();
        } else {
            $this->database = $database;
        }
    }
    
    // ----- READ OPERATIONS -----
    
    // Get all products with optional filtering, sorting, and pagination
    public function getAllProducts() {
        $query = "SELECT * FROM produk";
        $params = [];
        
        // Search functionality
        if (isset($_GET['search']) && !empty($_GET['search'])) {
            $search = $_GET['search'];
            $query .= " WHERE nama_produk LIKE ? OR deskripsi LIKE ? OR kategori LIKE ?";
            $params = ["%$search%", "%$search%", "%$search%"];
        }
        
        // Category filter
        if (isset($_GET['filter_kategori']) && !empty($_GET['filter_kategori'])) {
            if (strpos($query, 'WHERE') !== false) {
                $query .= " AND kategori = ?";
            } else {
                $query .= " WHERE kategori = ?";
            }
            $params[] = $_GET['filter_kategori'];
        }
        
        // Sort functionality
        $sort_by = isset($_GET['sort']) ? $_GET['sort'] : 'created_at';
        $sort_order = isset($_GET['dir']) ? $_GET['dir'] : 'DESC';
        
        $allowed_sort_fields = ['id', 'nama_produk', 'harga', 'kategori', 'created_at'];
        $allowed_sort_orders = ['ASC', 'DESC'];
        
        if (!in_array($sort_by, $allowed_sort_fields)) {
            $sort_by = 'created_at';
        }
        
        if (!in_array($sort_order, $allowed_sort_orders)) {
            $sort_order = 'DESC';
        }
        
        $query .= " ORDER BY $sort_by $sort_order";
        
        // Pagination
        $page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
        $offset = ($page - 1) * $this->recordsPerPage;
        
        $query .= " LIMIT $offset, $this->recordsPerPage";
        
        try {
            $stmt = $this->database->prepare($query);
            $stmt->execute($params);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            $_SESSION['error_message'] = "Error fetching products: " . $e->getMessage();
            return [];
        }
    }
    
    // Count total products for pagination
    public function countTotalProducts() {
        $query = "SELECT COUNT(*) FROM produk";
        $params = [];
        
        // Search functionality
        if (isset($_GET['search']) && !empty($_GET['search'])) {
            $search = $_GET['search'];
            $query .= " WHERE nama_produk LIKE ? OR deskripsi LIKE ? OR kategori LIKE ?";
            $params = ["%$search%", "%$search%", "%$search%"];
        }
        
        // Category filter
        if (isset($_GET['filter_kategori']) && !empty($_GET['filter_kategori'])) {
            if (strpos($query, 'WHERE') !== false) {
                $query .= " AND kategori = ?";
            } else {
                $query .= " WHERE kategori = ?";
            }
            $params[] = $_GET['filter_kategori'];
        }
        
        try {
            $stmt = $this->database->prepare($query);
            $stmt->execute($params);
            return $stmt->fetchColumn();
        } catch (PDOException $e) {
            $_SESSION['error_message'] = "Error counting products: " . $e->getMessage();
            return 0;
        }
    }
    
    // Get unique categories
    public function getCategories() {
        try {
            $stmt = $this->database->prepare("SELECT DISTINCT kategori FROM produk WHERE kategori IS NOT NULL AND kategori != '' ORDER BY kategori");
            $stmt->execute();
            return $stmt->fetchAll(PDO::FETCH_COLUMN);
        } catch (PDOException $e) {
            $_SESSION['error_message'] = "Error fetching categories: " . $e->getMessage();
            return [];
        }
    }
    
    // Get product by ID
    public function getProductById($id) {
        try {
            $stmt = $this->database->prepare("SELECT * FROM produk WHERE id = ?");
            $stmt->execute([$id]);
            return $stmt->fetch(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            $_SESSION['error_message'] = "Error fetching product: " . $e->getMessage();
            return false;
        }
    }
    


    // ----- CREATE OPERATIONS -----
    
    // Add new product
    public function addProduct($nama_produk, $deskripsi, $harga, $kategori) {
        try {
            $stmt = $this->database->prepare("INSERT INTO produk (nama_produk, deskripsi, harga, kategori, created_at, updated_at) VALUES (?, ?, ?, ?, NOW(), NOW())");
            $result = $stmt->execute([$nama_produk, $deskripsi, $harga, $kategori]);
            
            if ($result) {
                $_SESSION['success_message'] = "Produk berhasil ditambahkan!";
                return true;
            } else {
                $_SESSION['error_message'] = "Gagal menambahkan produk. Database Error: " . implode(", ", $stmt->errorInfo());
                return false;
            }
        } catch (PDOException $e) {
            $_SESSION['error_message'] = "Error adding product: " . $e->getMessage();
            return false;
        }
    }

    
    // ----- UPDATE OPERATIONS -----
    
    // Update existing product
    public function updateProduct($id, $nama_produk, $deskripsi, $harga, $kategori) {
        try {
            $stmt = $this->database->prepare("UPDATE produk SET nama_produk = ?, deskripsi = ?, harga = ?, kategori = ?, updated_at = NOW() WHERE id = ?");
            $result = $stmt->execute([$nama_produk, $deskripsi, $harga, $kategori, $id]);
            
            if ($result) {
                $_SESSION['success_message'] = "Produk berhasil diperbarui!";
                return true;
            } else {
                $_SESSION['error_message'] = "Gagal memperbarui produk.";
                return false;
            }
        } catch (PDOException $e) {
            $_SESSION['error_message'] = "Error updating product: " . $e->getMessage();
            return false;
        }
    }
    


    // ----- DELETE OPERATIONS -----
    
    // Delete product
    public function deleteProduct($id) {
        try {
            $stmt = $this->database->prepare("DELETE FROM produk WHERE id = ?");
            $result = $stmt->execute([$id]);
            
            if ($result) {
                $_SESSION['success_message'] = "Produk berhasil dihapus!";
                return true;
            } else {
                $_SESSION['error_message'] = "Gagal menghapus produk.";
                return false;
            }
        } catch(PDOException $e) {
            $_SESSION['error_message'] = "Error: " . $e->getMessage();
            return false;
        }
    }


    
    // ----- VALIDATION -----
    
    // Validate product data
    public function validateProductData($nama_produk, $harga) {
        $errors = [];
        
        if (empty($nama_produk)) {
            $errors[] = "Nama produk harus diisi.";
        }
        
        if ($harga <= 0) {
            $errors[] = "Harga harus lebih besar dari 0.";
        }
        
        return $errors;
    }
    
    // ----- HELPER METHODS FOR UI -----
    
    // Get records per page
    public function getRecordsPerPage() {
        return $this->recordsPerPage;
    }
    
    // Get current sort parameters
    public function getCurrentSortBy() {
        return isset($_GET['sort']) ? $_GET['sort'] : 'created_at';
    }
    
    public function getCurrentSortOrder() {
        return isset($_GET['dir']) ? $_GET['dir'] : 'DESC';
    }
    
    // Generate sort URL
    public function getSortUrl($field) {
        $params = $_GET;
        $params['sort'] = $field;
        $params['dir'] = (isset($_GET['sort']) && $_GET['sort'] == $field && isset($_GET['dir']) && $_GET['dir'] == 'ASC') ? 'DESC' : 'ASC';
        return '?' . http_build_query($params);
    }
}
?>