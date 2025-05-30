<?php
// Start the session
session_start();

// Check if already logged in
if(isset($_SESSION['user_id'])) {
    header("Location:" . BASE_URL . "index.php");
    exit();
}

// Include database connection
require_once '../config/database.php';

// User authentication class
class UserAuth {
    private $db;
    private $error_message = '';
    
    public function __construct() {
        // Get database connection
        $database = new Database();
        $this->db = $database->getConnection();
    }
    
    public function login($username, $password) {
        // Validate input
        if (empty($username) || empty($password)) {
            $this->error_message = 'Please enter both username and password';
            return false;
        }
        
        try {
            // Query to find user
            $stmt = $this->db->prepare("SELECT id, username, password, role FROM users WHERE username = :username");
            $stmt->bindParam(':username', $username);
            $stmt->execute();
            
            if ($stmt->rowCount() > 0) {
                $user = $stmt->fetch(PDO::FETCH_ASSOC);
                
                // Verify password
                if (password_verify($password, $user['password'])) {
                    // Set session variables
                    $_SESSION['user_id'] = $user['id'];
                    $_SESSION['username'] = $user['username'];
                    $_SESSION['role'] = $user['role'];
                    $_SESSION['last_activity'] = time();
                    
                    // Regenerate session ID for security
                    session_regenerate_id(true);
                    return true;
                } else {
                    $this->error_message = 'Invalid username or password';
                    return false;
                }
            } else {
                $this->error_message = 'User not found';
                return false;
            }
        } catch (PDOException $e) {
            $this->error_message = 'Database error: ' . $e->getMessage();
            return false;
        }
    }
    
    public function getErrorMessage() {
        return $this->error_message;
    }
    
    public function setRememberMe($user_id) {
        if (isset($_POST['remember']) && $_POST['remember'] == 'on') {
            $selector = bin2hex(random_bytes(8));
            $token = bin2hex(random_bytes(32));
            $expires = time() + 60 * 60 * 24 * 30; // 30 days
            
            // Delete any existing token
            $stmt = $this->db->prepare("DELETE FROM user_tokens WHERE user_id = :user_id");
            $stmt->bindParam(':user_id', $user_id, PDO::PARAM_INT);
            $stmt->execute();
            
            // Insert new token
            $hashed_token = password_hash($token, PASSWORD_DEFAULT);
            $expires_format = date('Y-m-d H:i:s', $expires);
            
            $stmt = $this->db->prepare("INSERT INTO user_tokens (user_id, selector, token, expires) 
                                       VALUES (:user_id, :selector, :token, :expires)");
            $stmt->bindParam(':user_id', $user_id, PDO::PARAM_INT);
            $stmt->bindParam(':selector', $selector);
            $stmt->bindParam(':token', $hashed_token);
            $stmt->bindParam(':expires', $expires_format);
            $stmt->execute();
            
            // Set cookie
            setcookie(
                'remember',
                $selector . ':' . $token,
                $expires,
                '/',
                '',     // domain
                false,  // secure only
                true    // httponly
            );
        }
    }
}

// Initialize error message
$error_message = '';

// Process login form
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $auth = new UserAuth();
    $username = trim($_POST['username']);
    $password = trim($_POST['password']);
    
    if ($auth->login($username, $password)) {
        // Set remember me cookie if checked
        $auth->setRememberMe($_SESSION['user_id']);
        
        // Redirect to dashboard
        header("Location: " . BASE_URL . "index.php");
        exit();
    } else {
        $error_message = $auth->getErrorMessage();
    }
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - Inventory System</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css">
    <style>
        body {
            background-color: #f8f9fa;
            height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }
        
        .login-form {
            width: 100%;
            max-width: 420px;
            padding: 15px;
            margin: auto;
        }
        
        .card {
            border-radius: 10px;
            box-shadow: 0 10px 20px rgba(0, 0, 0, 0.1);
            border: none;
        }
        
        .card-header {
            background: linear-gradient(45deg, #007bff, #5eb5ff);
            color: white;
            text-align: center;
            border-radius: 10px 10px 0 0 !important;
            padding: 20px;
        }
        
        .btn-primary {
            background: linear-gradient(45deg, #007bff, #5eb5ff);
            border: none;
            width: 100%;
            padding: 10px;
            border-radius: 5px;
        }
        
        .form-control {
            border-radius: 5px;
            padding: 10px;
        }
        
        .logo {
            font-size: 3rem;
            margin-bottom: 10px;
        }
        
        .forgot-password {
            font-size: 0.9rem;
        }
        
        .register-link {
            text-align: center;
            margin-top: 15px;
        }
    </style>
</head>
<body>
    <div class="login-form">
        <div class="card">
            <div class="card-header">
                <div class="logo"><i class="bi bi-box-seam"></i></div>
                <h3>Inventory System</h3>
                <p class="mb-0">Please login to continue</p>
            </div>
            <div class="card-body p-4">
                <?php if(!empty($error_message)): ?>
                <div class="alert alert-danger" role="alert">
                    <?php echo htmlspecialchars($error_message); ?>
                </div>
                <?php endif; ?>
                
                <form method="POST" action="<?php echo htmlspecialchars($_SERVER['PHP_SELF']); ?>">
                    <div class="mb-3">
                        <label for="username" class="form-label">Username</label>
                        <div class="input-group">
                            <span class="input-group-text"><i class="bi bi-person"></i></span>
                            <input type="text" class="form-control" id="username" name="username" placeholder="Enter your username" required>
                        </div>
                    </div>
                    <div class="mb-3">
                        <label for="password" class="form-label">Password</label>
                        <div class="input-group">
                            <span class="input-group-text"><i class="bi bi-lock"></i></span>
                            <input type="password" class="form-control" id="password" name="password" placeholder="Enter your password" required>
                        </div>
                    </div>
                    <div class="mb-3 form-check">
                        <input type="checkbox" class="form-check-input" id="remember" name="remember">
                        <label class="form-check-label" for="remember">Remember me</label>
                    </div>
                    <button type="submit" class="btn btn-primary">Login</button>
                </form>
            </div>
        </div>
        <div class="register-link">
            <p>Don't have an account? <a href="register.php">Register here</a></p>
        </div>
    </div>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>