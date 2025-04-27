<?php
// Start the session
session_start();

// Check if already logged in
if(isset($_SESSION['user_id'])) {
    header("Location: index.php");
    exit();
}

// Include database connection
require_once '../config/database.php';

// User registration class
class UserRegistration {
    private $db;
    private $error_message = '';
    private $success_message = '';
    private $available_roles = [
        'user' => 'Standard User',
        'admin' => 'Administrator', 
        'manager' => 'Manager',
        'supervisor' => 'Supervisor',
        'operator' => 'Operator'
    ];
    
    public function __construct() {
        // Get database connection
        $database = new Database();
        $this->db = $database->getConnection();
    }
    
    public function getAvailableRoles() {
        return $this->available_roles;
    }
    
    public function register($username, $email, $password, $confirm_password, $selected_role) {
        // Validate role
        if (!array_key_exists($selected_role, $this->available_roles)) {
            $selected_role = 'user'; // Default to user if invalid role
        }
        
        // Basic validation
        if (empty($username) || empty($email) || empty($password) || empty($confirm_password)) {
            $this->error_message = 'All fields are required';
            return false;
        } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $this->error_message = 'Please enter a valid email';
            return false;
        } elseif (strlen($password) < 6) {
            $this->error_message = 'Password must be at least 6 characters long';
            return false;
        } elseif ($password !== $confirm_password) {
            $this->error_message = 'Passwords do not match';
            return false;
        }
        
        try {
            // Check if username already exists
            $stmt = $this->db->prepare("SELECT COUNT(*) FROM users WHERE username = :username");
            $stmt->bindParam(':username', $username);
            $stmt->execute();
            if ($stmt->fetchColumn() > 0) {
                $this->error_message = 'Username already exists';
                return false;
            }
            
            // Check if email already exists
            $stmt = $this->db->prepare("SELECT COUNT(*) FROM users WHERE email = :email");
            $stmt->bindParam(':email', $email);
            $stmt->execute();
            if ($stmt->fetchColumn() > 0) {
                $this->error_message = 'Email already exists';
                return false;
            }
            
            // Insert new user
            $hashed_password = password_hash($password, PASSWORD_DEFAULT);
            
            $stmt = $this->db->prepare("INSERT INTO users (username, email, password, role, created_at) 
                                      VALUES (:username, :email, :password, :role, NOW())");
            $stmt->bindParam(':username', $username);
            $stmt->bindParam(':email', $email);
            $stmt->bindParam(':password', $hashed_password);
            $stmt->bindParam(':role', $selected_role);
            
            if ($stmt->execute()) {
                $this->success_message = 'Registration successful! You can now <a href="login.php">login</a>.';
                return true;
            } else {
                $this->error_message = 'Registration failed. Please try again.';
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
    
    public function getSuccessMessage() {
        return $this->success_message;
    }
}

// Initialize variables
$error_message = '';
$success_message = '';

// Create registration instance
$registration = new UserRegistration();
$available_roles = $registration->getAvailableRoles();

// Process registration form
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username']);
    $email = trim($_POST['email']);
    $password = trim($_POST['password']);
    $confirm_password = trim($_POST['confirm_password']);
    $selected_role = isset($_POST['role']) ? trim($_POST['role']) : 'user';
    
    if ($registration->register($username, $email, $password, $confirm_password, $selected_role)) {
        $success_message = $registration->getSuccessMessage();
    } else {
        $error_message = $registration->getErrorMessage();
    }
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Register - Inventory System</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css">
    <style>
        body {
            background-color: #f8f9fa;
            height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        
        .register-form {
            width: 100%;
            max-width: 480px;
            padding: 15px;
            margin: auto;
        }
        
        .card {
            border-radius: 10px;
            box-shadow: 0 10px 20px rgba(0, 0, 0, 0.1);
            border: none;
        }
        
        .card-header {
            background: linear-gradient(45deg, #28a745, #5dd879);
            color: white;
            text-align: center;
            border-radius: 10px 10px 0 0 !important;
            padding: 20px;
        }
        
        .btn-success {
            background: linear-gradient(45deg, #28a745, #5dd879);
            border: none;
            width: 100%;
            padding: 10px;
        }
        
        .form-control {
            border-radius: 5px;
            padding: 10px;
        }
        
        .logo {
            font-size: 3rem;
            margin-bottom: 10px;
        }
        
        .login-link {
            text-align: center;
            margin-top: 15px;
        }
    </style>
</head>
<body>
    <div class="register-form">
        <div class="card">
            <div class="card-header">
                <div class="logo"><i class="bi bi-box-seam"></i></div>
                <h3>Inventory System</h3>
                <p class="mb-0">Create a new account</p>
            </div>
            <div class="card-body p-4">
                <?php if(!empty($error_message)): ?>
                <div class="alert alert-danger" role="alert">
                    <?php echo htmlspecialchars($error_message); ?>
                </div>
                <?php endif; ?>
                
                <?php if(!empty($success_message)): ?>
                <div class="alert alert-success" role="alert">
                    <?php echo $success_message; ?>
                </div>
                <?php endif; ?>
                
                <form method="POST" action="<?php echo htmlspecialchars($_SERVER['PHP_SELF']); ?>">
                    <div class="mb-3">
                        <label for="username" class="form-label">Username</label>
                        <div class="input-group">
                            <span class="input-group-text"><i class="bi bi-person"></i></span>
                            <input type="text" class="form-control" id="username" name="username" placeholder="Choose a username" required>
                        </div>
                    </div>
                    <div class="mb-3">
                        <label for="email" class="form-label">Email</label>
                        <div class="input-group">
                            <span class="input-group-text"><i class="bi bi-envelope"></i></span>
                            <input type="email" class="form-control" id="email" name="email" placeholder="Enter your email" required>
                        </div>
                    </div>
                    <div class="mb-3">
                        <label for="role" class="form-label">Role</label>
                        <div class="input-group">
                            <span class="input-group-text"><i class="bi bi-person-badge"></i></span>
                            <select class="form-select" id="role" name="role" required>
                                <?php foreach($available_roles as $role_key => $role_name): ?>
                                <option value="<?php echo htmlspecialchars($role_key); ?>"><?php echo htmlspecialchars($role_name); ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>
                    <div class="mb-3">
                        <label for="password" class="form-label">Password</label>
                        <div class="input-group">
                            <span class="input-group-text"><i class="bi bi-lock"></i></span>
                            <input type="password" class="form-control" id="password" name="password" placeholder="Create a password" required>
                        </div>
                        <div class="form-text">Password must be at least 6 characters long</div>
                    </div>
                    <div class="mb-3">
                        <label for="confirm_password" class="form-label">Confirm Password</label>
                        <div class="input-group">
                            <span class="input-group-text"><i class="bi bi-lock-fill"></i></span>
                            <input type="password" class="form-control" id="confirm_password" name="confirm_password" placeholder="Confirm your password" required>
                        </div>
                    </div>
                    <div class="mb-3 form-check">
                        <input type="checkbox" class="form-check-input" id="terms" required>
                        <label class="form-check-label" for="terms">I agree to the terms and conditions</label>
                    </div>
                    <button type="submit" class="btn btn-success">Register</button>
                </form>
            </div>
        </div>
        <div class="login-link">
            <p>Already have an account? <a href="login.php">Login here</a></p>
        </div>
    </div>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>