<?php
session_start([
    'cookie_httponly' => true,
    'cookie_secure' => true,
    'use_strict_mode' => true
]);

// Redirect if not logged in or not admin/small-admin
if (!isset($_SESSION['user_email']) || !in_array($_SESSION['role'], ['admin', 'small-admin', 'small-admi'])) {
    header("Location: login.html");
    exit();
}

// Database configuration
define('DB_HOST', 'localhost');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_NAME', 'brain_cancer_db');

// Database connection
$conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Initialize variables
$errors = [];
$success = false;
$roles = ['user']; // Default allowed roles
$fullname = $email = $role = '';

// Set available roles based on current user's role
if ($_SESSION['role'] === 'admin') {
    $roles = array_merge($roles, ['small-admin', 'small-admi']);
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Validate inputs
    $fullname = trim($_POST['fullname'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    $confirm_password = $_POST['confirm_password'] ?? '';
    $role = $_POST['role'] ?? 'user';
    $admin_code = null;

    if (empty($fullname)) {
        $errors[] = "Full name is required";
    }

    if (empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = "Valid email is required";
    }

    if (empty($password)) {
        $errors[] = "Password is required";
    } elseif (strlen($password) < 8) {
        $errors[] = "Password must be at least 8 characters";
    } elseif ($password !== $confirm_password) {
        $errors[] = "Passwords do not match";
    }

    if (!in_array($role, $roles)) {
        $errors[] = "Invalid role selected";
    }

    // Generate admin code if adding a small-admin
    if (in_array($role, ['small-admin', 'small-admi'])) {
        $admin_code = 'ADM-' . strtoupper(substr(md5(uniqid()), 0, 8));
    }

    // Set managed_by for regular users if current user is a small-admin
    $managed_by = null;
    if (in_array($_SESSION['role'], ['small-admin', 'small-admi']) && $role === 'user') {
        $managed_by = $_SESSION['admin_code'];
    }

    // Check if email already exists
    if (empty($errors)) {
        $stmt = $conn->prepare("SELECT id FROM users WHERE email = ?");
        $stmt->bind_param("s", $email);
        $stmt->execute();
        $stmt->store_result();
        
        if ($stmt->num_rows > 0) {
            $errors[] = "Email already exists";
        }
        $stmt->close();
    }

    // Insert new user if no errors
    if (empty($errors)) {
        $hashed_password = password_hash($password, PASSWORD_DEFAULT);
        $profile_image = 'default-avatar.png';
        
        $stmt = $conn->prepare("INSERT INTO users (email, fullname, password, role, admin_code, managed_by, profile_image) VALUES (?, ?, ?, ?, ?, ?, ?)");
        $stmt->bind_param("sssssss", $email, $fullname, $hashed_password, $role, $admin_code, $managed_by, $profile_image);
        
        if ($stmt->execute()) {
            $success = true;
            // Clear form on success
            $fullname = $email = $role = '';
        } else {
            $errors[] = "Failed to add user: " . $stmt->error;
        }
        $stmt->close();
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Add New User</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        :root {
            --primary-color: #4e73df;
            --secondary-color: #f8f9fc;
            --accent-color: #2e59d9;
            --text-color: #5a5c69;
            --light-gray: #dddfeb;
        }
        
        body {
            background-color: var(--secondary-color);
            color: var(--text-color);
            font-family: 'Nunito', -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
        }
        
        .user-form-container {
            max-width: 650px;
            margin: 2rem auto;
            background: white;
            padding: 2.5rem;
            border-radius: 0.5rem;
            box-shadow: 0 0.15rem 1.75rem 0 rgba(58, 59, 69, 0.15);
            border: 1px solid var(--light-gray);
        }
        
        .form-header {
            text-align: center;
            margin-bottom: 2rem;
            color: var(--primary-color);
            border-bottom: 1px solid var(--light-gray);
            padding-bottom: 1rem;
        }
        
        .form-header h2 {
            font-weight: 600;
        }
        
        .form-label {
            font-weight: 600;
            color: var(--text-color);
            margin-bottom: 0.5rem;
        }
        
        .form-control {
            padding: 0.75rem 1rem;
            border-radius: 0.35rem;
            border: 1px solid var(--light-gray);
            transition: all 0.3s;
        }
        
        .form-control:focus {
            border-color: var(--primary-color);
            box-shadow: 0 0 0 0.2rem rgba(78, 115, 223, 0.25);
        }
        
        .btn-primary {
            background-color: var(--primary-color);
            border-color: var(--primary-color);
            padding: 0.75rem;
            font-weight: 600;
            letter-spacing: 0.5px;
            text-transform: uppercase;
            font-size: 0.85rem;
        }
        
        .btn-primary:hover {
            background-color: var(--accent-color);
            border-color: var(--accent-color);
        }
        
        .btn-outline-secondary {
            padding: 0.75rem;
            font-weight: 600;
            letter-spacing: 0.5px;
            text-transform: uppercase;
            font-size: 0.85rem;
        }
        
        .alert {
            border-radius: 0.35rem;
            padding: 1rem;
        }
        
        .password-strength {
            height: 5px;
            background-color: #e9ecef;
            margin-top: 0.5rem;
            border-radius: 3px;
            overflow: hidden;
        }
        
        .strength-meter {
            height: 100%;
            width: 0;
            transition: width 0.3s;
        }
        
        .input-group-text {
            background-color: var(--light-gray);
            border: 1px solid var(--light-gray);
        }
        
        .password-toggle {
            cursor: pointer;
            color: var(--text-color);
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="user-form-container">
            <div class="form-header">
                <h2><i class="fas fa-user-plus me-2"></i>Add New User</h2>
            </div>
            
            <?php if ($success): ?>
                <div class="alert alert-success d-flex align-items-center">
                    <i class="fas fa-check-circle me-2"></i>
                    <div>User added successfully!</div>
                </div>
            <?php endif; ?>
            
            <?php if (!empty($errors)): ?>
                <div class="alert alert-danger">
                    <div class="d-flex align-items-center mb-2">
                        <i class="fas fa-exclamation-triangle me-2"></i>
                        <strong>Please fix the following errors:</strong>
                    </div>
                    <ul class="mb-0 ps-4">
                        <?php foreach ($errors as $error): ?>
                            <li><?= htmlspecialchars($error) ?></li>
                        <?php endforeach; ?>
                    </ul>
                </div>
            <?php endif; ?>
            
            <form action="add_user.php" method="POST" class="needs-validation" novalidate>
                <div class="row mb-4">
                    <div class="col-md-6 mb-3 mb-md-0">
                        <label for="fullname" class="form-label">Full Name</label>
                        <div class="input-group">
                            <span class="input-group-text"><i class="fas fa-user"></i></span>
                            <input type="text" class="form-control" id="fullname" name="fullname" 
                                   value="<?= htmlspecialchars($fullname) ?>" required>
                        </div>
                    </div>
                    
                    <div class="col-md-6">
                        <label for="email" class="form-label">Email</label>
                        <div class="input-group">
                            <span class="input-group-text"><i class="fas fa-envelope"></i></span>
                            <input type="email" class="form-control" id="email" name="email" 
                                   value="<?= htmlspecialchars($email) ?>" required>
                        </div>
                    </div>
                </div>
                
                <div class="row mb-4">
                    <div class="col-md-6 mb-3 mb-md-0">
                        <label for="password" class="form-label">Password</label>
                        <div class="input-group">
                            <span class="input-group-text"><i class="fas fa-lock"></i></span>
                            <input type="password" class="form-control" id="password" name="password" required>
                            <span class="input-group-text password-toggle" onclick="togglePassword('password')">
                                <i class="fas fa-eye"></i>
                            </span>
                        </div>
                        <div class="password-strength mt-2">
                            <div class="strength-meter" id="password-strength"></div>
                        </div>
                        <small class="text-muted">Minimum 8 characters</small>
                    </div>
                    
                    <div class="col-md-6">
                        <label for="confirm_password" class="form-label">Confirm Password</label>
                        <div class="input-group">
                            <span class="input-group-text"><i class="fas fa-lock"></i></span>
                            <input type="password" class="form-control" id="confirm_password" name="confirm_password" required>
                            <span class="input-group-text password-toggle" onclick="togglePassword('confirm_password')">
                                <i class="fas fa-eye"></i>
                            </span>
                        </div>
                    </div>
                </div>
                
                <div class="mb-4">
                    <label for="role" class="form-label">Role</label>
                    <select class="form-select" id="role" name="role" required>
                        <?php foreach ($roles as $r): ?>
                            <option value="<?= $r ?>" <?= $role === $r ? 'selected' : '' ?>>
                                <?= ucfirst(str_replace('-', ' ', $r)) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                
                <div class="d-grid gap-3">
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-user-plus me-2"></i>Add User
                    </button>
                    <a href="dashboard.php" class="btn btn-outline-secondary">
                        <i class="fas fa-arrow-left me-2"></i>Back to Dashboard
                    </a>
                </div>
            </form>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Password strength indicator
        document.getElementById('password').addEventListener('input', function() {
            const password = this.value;
            const strengthMeter = document.getElementById('password-strength');
            let strength = 0;
            
            if (password.length >= 8) strength += 1;
            if (password.match(/[a-z]/) && password.match(/[A-Z]/)) strength += 1;
            if (password.match(/[0-9]/)) strength += 1;
            if (password.match(/[^a-zA-Z0-9]/)) strength += 1;
            
            // Update strength meter
            strengthMeter.style.width = (strength * 25) + '%';
            strengthMeter.style.backgroundColor = 
                strength < 2 ? '#dc3545' : 
                strength < 4 ? '#ffc107' : '#28a745';
        });
        
        // Toggle password visibility
        function togglePassword(fieldId) {
            const field = document.getElementById(fieldId);
            const icon = field.nextElementSibling.querySelector('i');
            
            if (field.type === 'password') {
                field.type = 'text';
                icon.classList.remove('fa-eye');
                icon.classList.add('fa-eye-slash');
            } else {
                field.type = 'password';
                icon.classList.remove('fa-eye-slash');
                icon.classList.add('fa-eye');
            }
        }
        
        // Form validation
        (function() {
            'use strict';
            const forms = document.querySelectorAll('.needs-validation');
            
            Array.from(forms).forEach(function(form) {
                form.addEventListener('submit', function(event) {
                    if (!form.checkValidity()) {
                        event.preventDefault();
                        event.stopPropagation();
                    }
                    
                    form.classList.add('was-validated');
                }, false);
            });
        })();
    </script>
</body>
</html>
<?php
$conn->close();
?>