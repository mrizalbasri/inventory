<?php
// User authentication class - handles logout functionality
class UserAuth {
    public function logout() {
        // Start the session if not already started
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        
        // Check for remember me cookie and delete from database if exists
        if (isset($_COOKIE['remember'])) {
            $this->deleteRememberToken();
            
            // Delete the cookie
            setcookie('remember', '', time() - 3600, '/');
        }
        
        // Unset all session variables
        $_SESSION = array();
        
        // Destroy the session cookie
        if (ini_get("session.use_cookies")) {
            $params = session_get_cookie_params();
            setcookie(
                session_name(), 
                '', 
                time() - 42000,
                $params["path"], 
                $params["domain"],
                $params["secure"], 
                $params["httponly"]
            );
        }
        
        // Destroy the session
        session_destroy();
    }
    
    private function deleteRememberToken() {
        if (isset($_COOKIE['remember']) && !empty($_COOKIE['remember'])) {
            list($selector) = explode(':', $_COOKIE['remember']);
            
            try {
                require_once '../config/database.php';
                $database = new Database();
                $db = $database->getConnection();
                
                $stmt = $db->prepare("DELETE FROM user_tokens WHERE selector = :selector");
                $stmt->bindParam(':selector', $selector);
                $stmt->execute();
            } catch (PDOException $e) {
                // Just log the error, but continue with logout
                error_log('Error deleting remember token: ' . $e->getMessage());
            }
        }
    }
}

// Create authentication instance and logout
$auth = new UserAuth();
$auth->logout();

// Redirect to login page with a message
header("Location: login.php?logout=success");
exit();
?>