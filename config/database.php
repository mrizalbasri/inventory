<?php
class Database {
    private $database;
    
    public function __construct() {
        $this->database = new PDO(
            "mysql:host=localhost;dbname=inventory_system",
            "root", 
            ""
        );
    }
    
    public function __destruct() {
        $this->database = null;
    }
    
    public function getConnection() {
        return $this->database;
    }
}

// Define BASE_URL
define('BASE_URL', '/inventory/');
?>
