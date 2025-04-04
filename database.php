<?php

try {
    $database = new PDO("mysql:host=localhost;dbname=inventory_system", "root", "");
} catch (PDOException $e) {
    die("Error: " . $e->getMessage());
}


?>