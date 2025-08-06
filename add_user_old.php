<?php
// Strict session configuration with enhanced security
session_start([
    'cookie_httponly' => true,
    'cookie_secure' => true,
    'use_strict_mode' => true,
    'cookie_samesite' => 'Strict',
    'sid_length' => 128,
    'sid_bits_per_character' => 6
]);

// Security headers
header("X-Frame-Options: DENY");
header("X-Content-Type-Options: nosniff");
header("X-XSS-Protection: 1; mode=block");
header("Referrer-Policy: strict-origin-when-cross-origin");
header("Strict-Transport-Security: max-age=31536000; includeSubDomains");

// Database configuration
define('DB_HOST', 'localhost');
define('DB_NAME', 'brain_cancer_db');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DEFAULT_PROFILE_IMAGE', 'images/default_profile.png');

// Redirect if not logged in or not authorized
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

// Check authorization based on role
$allowedRoles = ['admin', 'small-admi'];
if (!in_array($_SESSION['role'] ?? '', $allowedRoles)) {
    header("Location: unauthorized.php");
    exit();
}

// Database connection with PDO
try {
    $dsn = "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=utf8mb4";
    $options = [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES => false,
        PDO::ATTR_PERSISTENT => false
    ];
    $pdo = new PDO($dsn, DB_USER, DB_PASS, $options);
} catch (PDOException $e) {
    error_log("Database connection failed: " . $e->getMessage());
    die("System error. Please try again later.");
}

// Initialize user data
$userData = [
    'user_id' => filter_var($_SESSION['user_id'], FILTER_VALIDATE_INT),
    'email' => filter_var($_SESSION['user_email'] ?? '', FILTER_SANITIZE_EMAIL),
    'role' => htmlspecialchars($_SESSION['role'] ?? 'user', ENT_QUOTES, 'UTF-8'),
    'admin_code' => isset($_SESSION['admin_code']) ? htmlspecialchars($_SESSION['admin_code'], ENT_QUOTES, 'UTF-8') : null,
    'fullname' => 'Guest User',
    'profile_image' => DEFAULT_PROFILE_IMAGE
];

// Fetch user profile
try {
    $stmt = $pdo->prepare("SELECT fullname, profile_image FROM users WHERE id = ?");
    $stmt->execute([$userData['user_id']]);
    $profile = $stmt->fetch();
    
    if ($profile) {
        $userData['fullname'] = htmlspecialchars($profile['fullname'] ?? $userData['fullname'], ENT_QUOTES, 'UTF-8');
        $userData['profile_image'] = htmlspecialchars($profile['profile_image'] ?? DEFAULT_PROFILE_IMAGE, ENT_QUOTES, 'UTF-8');
    }
} catch (PDOException $e) {
    error_log("Profile fetch error: " . $e->getMessage());
}

// Initialize variables
$userManagementData = [];
$adminData = [];
$smallAdmins = [];
$managedUsers = [];
$errors = [];
$success = false;
$showAddForm = isset($_GET['action']) && $_GET['action'] === 'add';
$form_title = "";
$allowed_roles = [];

// Set role permissions
if ($userData['role'] === 'admin') {
    $allowed_roles = ['small-admi'];
    $form_title = "Register New Small Admin";
    
    try {
        $stmt = $pdo->prepare("
            SELECT u.id, u.fullname, u.email, u.admin_code,
                   (SELECT COUNT(*) FROM users WHERE managed_by = u.admin_code) as user_count 
            FROM users u 
            WHERE u.role = 'small-admi'
        ");
        $stmt->execute();
        $adminData = $stmt->fetchAll();
        $smallAdmins = $adminData;
    } catch (PDOException $e) {
        error_log("Small admin fetch error: " . $e->getMessage());
        $errors[] = "Failed to load small admin data";
    }
} else {
    $allowed_roles = ['user'];
    $form_title = "Register New User";
    
    try {
        $stmt = $pdo->prepare("SELECT id, fullname, email, role FROM users WHERE managed_by = ?");
        $stmt->execute([$userData['admin_code']]);
        $userManagementData = $stmt->fetchAll();
        $managedUsers = $userManagementData;
    } catch (PDOException $e) {
        error_log("Managed users fetch error: " . $e->getMessage());
        $errors[] = "Failed to load managed users";
    }
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_user'])) {
    // Validate CSRF token
    if (!isset($_POST['csrf_token']) || !hash_equals($_SESSION['csrf_token'], $_POST['csrf_token'])) {
        $errors[] = "Invalid CSRF token";
    } else {
        // Sanitize inputs
        $fullname = trim(htmlspecialchars($_POST['fullname'] ?? '', ENT_QUOTES, 'UTF-8'));
        $email = filter_var(trim($_POST['email'] ?? ''), FILTER_SANITIZE_EMAIL);
        $password = $_POST['password'] ?? '';
        $confirm_password = $_POST['confirm_password'] ?? '';
        $role = in_array($_POST['role'] ?? '', $allowed_roles) ? $_POST['role'] : $allowed_roles[0];
        $admin_code = null;
        $managed_by = null;

        // Validation
        if (empty($fullname) || strlen($fullname) > 100) {
            $errors[] = "Full name must be between 1-100 characters";
        }

        if (empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL) || strlen($email) > 255) {
            $errors[] = "Valid email is required (max 255 characters)";
        }

        if (empty($password) || strlen($password) < 8 || !preg_match('/^[0-9]+$/', $password)) {
            $errors[] = "Password must be at least 8 numeric digits";
        } elseif ($password !== $confirm_password) {
            $errors[] = "Passwords do not match";
        }

        // Generate admin code for small-admin
        if ($role === 'small-admi') {
            $admin_code = 'ADM-' . bin2hex(random_bytes(4));
        }

        // Set manager relationship
        if ($userData['role'] === 'small-admi' && $role === 'user') {
            $managed_by = $userData['admin_code'];
        }

        // Check email uniqueness
        if (empty($errors)) {
            try {
                $stmt = $pdo->prepare("SELECT id FROM users WHERE email = ?");
                $stmt->execute([$email]);
                
                if ($stmt->rowCount() > 0) {
                    $errors[] = "Email already exists";
                }
            } catch (PDOException $e) {
                error_log("Email check error: " . $e->getMessage());
                $errors[] = "System error during email validation";
            }
        }

        // Create user
        if (empty($errors)) {
            try {
                $hashed_password = password_hash($password, PASSWORD_DEFAULT);
                
                $stmt = $pdo->prepare("
                    INSERT INTO users 
                    (email, fullname, password, role, admin_code, managed_by, profile_image) 
                    VALUES (?, ?, ?, ?, ?, ?, ?)
                ");
                $stmt->execute([
                    $email, 
                    $fullname, 
                    $hashed_password, 
                    $role, 
                    $admin_code, 
                    $managed_by, 
                    DEFAULT_PROFILE_IMAGE
                ]);
                
                $success = true;
                $showAddForm = false;
                
                // Refresh data
                if ($userData['role'] === 'small-admi') {
                    $stmt = $pdo->prepare("SELECT id, fullname, email, role FROM users WHERE managed_by = ?");
                    $stmt->execute([$userData['admin_code']]);
                    $userManagementData = $stmt->fetchAll();
                    $managedUsers = $userManagementData;
                }
                
                // Redirect to prevent form resubmission
                header("Location: user_management.php?success=1");
                exit();
            } catch (PDOException $e) {
                error_log("User creation error: " . $e->getMessage());
                $errors[] = "Failed to create user. Please try again.";
            }
        }
    }
}

// Check for success message from redirect
if (isset($_GET['success'])) {
    $success = true;
}

// Generate CSRF token
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="Content-Security-Policy" content="default-src 'self'; script-src 'self' https://cdn.jsdelivr.net 'unsafe-inline'; style-src 'self' https://cdn.jsdelivr.net https://cdnjs.cloudflare.com 'unsafe-inline'; img-src 'self' data:; font-src 'self' https://cdn.jsdelivr.net https://cdnjs.cloudflare.com;">
    <title>User Management | Brain Cancer Detection</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        :root {
            --primary: #3b0a85;
            --primary-light: #5e35b1;
            --secondary: #6c757d;
            --success: #28a745;
            --danger: #dc3545;
            --info: #17a2b8;
            --warning: #ffc107;
            --light: #f8f9fa;
            --dark: #343a40;
            --gray-100: #f8f9fa;
            --gray-200: #e9ecef;
            --gray-300: #dee2e6;
            --shadow-sm: 0 0.125rem 0.25rem rgba(0, 0, 0, 0.075);
            --shadow: 0 0.5rem 1rem rgba(0, 0, 0, 0.15);
            --radius-sm: 0.25rem;
            --radius: 0.5rem;
            --radius-lg: 1rem;
        }
        
        body {
            background: linear-gradient(135deg, #f5f7fa 0%, #e4e8f0 100%);
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            min-height: 100vh;
        }
        
        .sidebar {
            width: 250px;
            min-height: 100vh;
            background: linear-gradient(180deg, var(--primary) 0%, var(--primary-light) 100%);
            box-shadow: var(--shadow);
            position: fixed;
            z-index: 1000;
            color: white;
        }
        
        .sidebar-brand {
            padding: 1.5rem;
            font-weight: 700;
            color: white;
            border-bottom: 1px solid rgba(255,255,255,0.1);
        }
        
        .sidebar-nav {
            padding: 1rem 0;
        }
        
        .nav-link {
            padding: 0.75rem 1.5rem;
            color: rgba(255,255,255,0.8);
            border-left: 3px solid transparent;
            transition: all 0.3s;
        }
        
        .nav-link:hover, .nav-link.active {
            color: white;
            background-color: rgba(255,255,255,0.1);
            border-left-color: white;
        }
        
        .nav-link i {
            margin-right: 0.75rem;
        }
        
        .profile-section {
            border-top: 1px solid rgba(255,255,255,0.1);
            padding: 1.5rem;
            text-align: center;
            background: rgba(255,255,255,0.1);
            backdrop-filter: blur(5px);
            margin: 1rem;
            border-radius: var(--radius);
        }
        
        .profile-img {
            width: 80px;
            height: 80px;
            object-fit: cover;
            border-radius: 50%;
            margin-bottom: 1rem;
            border: 3px solid white;
        }
        
        .main-content {
            margin-left: 250px;
            padding: 2rem;
            background-color: transparent;
        }
        
        .user-form-container {
            max-width: 700px;
            margin: 0 auto;
            background: white;
            padding: 2.5rem;
            border-radius: var(--radius-lg);
            box-shadow: var(--shadow-sm);
        }
        
        .form-header {
            text-align: center;
            margin-bottom: 2rem;
        }
        
        .form-header h2 {
            font-weight: 700;
            color: var(--primary);
        }
        
        .form-label {
            font-weight: 600;
        }
        
        .password-container {
            position: relative;
        }
        
        .password-toggle {
            position: absolute;
            right: 10px;
            top: 50%;
            transform: translateY(-50%);
            cursor: pointer;
        }
        
        .password-strength {
            height: 5px;
            background-color: var(--gray-200);
            margin-top: 0.5rem;
            border-radius: 3px;
            overflow: hidden;
        }
        
        .strength-meter {
            height: 100%;
            width: 0;
            transition: all 0.3s;
        }
        
        .chart-container {
            background: white;
            padding: 1.5rem;
            border-radius: var(--radius);
            box-shadow: var(--shadow-sm);
            margin-bottom: 2rem;
        }
        
        .chart-title {
            color: var(--primary);
            border-bottom: 1px solid var(--gray-200);
            padding-bottom: 0.75rem;
            margin-bottom: 1.5rem;
        }
        
        @media (max-width: 992px) {
            .sidebar {
                width: 100%;
                position: relative;
                min-height: auto;
            }
            
            .main-content {
                margin-left: 0;
            }
        }
    </style>
</head>
<body>
    <!-- Sidebar -->
    <div class="sidebar">
        <div class="sidebar-brand">
            <i class="fas fa-brain me-2"></i>Brain Cancer Detection
        </div>
        
        <ul class="nav flex-column sidebar-nav">
            <li class="nav-item">
                <a class="nav-link" href="dashboard.php">
                    <i class="fas fa-tachometer-alt"></i> Dashboard
                </a>
            </li>
            
            <li class="nav-item">
                <a class="nav-link" href="predict.php">
                    <i class="fas fa-microscope"></i> Prediction
                </a>
            </li>
            
            <li class="nav-item">
                <a class="nav-link" href="report.php">
                    <i class="fas fa-file-medical"></i> Report
                </a>
            </li>
            
            <?php if (in_array($userData['role'], ['admin', 'small-admi'])): ?>
                <li class="nav-item">
                    <a class="nav-link active" href="user_management.php">
                        <i class="fas fa-users-cog"></i> User Management
                    </a>
                </li>
            <?php endif; ?>
        </ul>
        
        <!-- Profile Section -->
        <div class="profile-section">
            <img src="<?= htmlspecialchars($userData['profile_image'], ENT_QUOTES, 'UTF-8') ?>" 
                 class="profile-img" alt="Profile"
                 onerror="this.src='<?= htmlspecialchars(DEFAULT_PROFILE_IMAGE, ENT_QUOTES, 'UTF-8') ?>'">
            <h5 class="mb-1 text-white"><?= htmlspecialchars($userData['fullname'], ENT_QUOTES, 'UTF-8') ?></h5>
            <p class="small mb-2 text-white-50"><?= htmlspecialchars($userData['email'], ENT_QUOTES, 'UTF-8') ?></p>
            <span class="badge bg-light text-primary"><?= htmlspecialchars(ucfirst($userData['role']), ENT_QUOTES, 'UTF-8') ?></span>
            
            <div class="mt-3 d-flex flex-column">
                <a href="update_profile.php" class="btn btn-sm btn-light mb-2">
                    <i class="fas fa-user-edit"></i> Update Profile
                </a>
                <a href="logout.php" class="btn btn-sm btn-outline-light">
                    <i class="fas fa-sign-out-alt"></i> Logout
                </a>
            </div>
        </div>
    </div>
    
    <!-- Main Content -->
    <div class="main-content">
        <!-- User Management Section -->
        <div class="chart-container mt-4">
            <h3 class="chart-title">
                <i class="fas fa-users-cog text-primary"></i>
                User Management
            </h3>
            
            <?php if ($success): ?>
                <div class="alert alert-success alert-dismissible fade show" role="alert">
                    <i class="fas fa-check-circle me-2"></i>
                    <span>Account created successfully!</span>
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
            <?php endif; ?>
            
            <?php if ($showAddForm): ?>
                <!-- Add User Form -->
                <div class="user-form-container mt-4">
                    <div class="form-header">
                        <h2><i class="fas fa-user-plus me-2"></i><?= htmlspecialchars($form_title, ENT_QUOTES, 'UTF-8') ?></h2>
                        <p class="text-muted">
                            <?php if ($userData['role'] === 'admin'): ?>
                                Register a new Small Admin account
                            <?php else: ?>
                                Register a new User account
                            <?php endif; ?>
                        </p>
                    </div>
                    
                    <?php if (!empty($errors)): ?>
                        <div class="alert alert-danger">
                            <div class="d-flex align-items-center mb-2">
                                <i class="fas fa-exclamation-triangle me-2"></i>
                                <strong>Please fix the following errors:</strong>
                            </div>
                            <ul class="mb-0 ps-4">
                                <?php foreach ($errors as $error): ?>
                                    <li><?= htmlspecialchars($error, ENT_QUOTES, 'UTF-8') ?></li>
                                <?php endforeach; ?>
                            </ul>
                        </div>
                    <?php endif; ?>
                    
                    <form method="POST" class="needs-validation" novalidate>
                        <input type="hidden" name="add_user" value="1">
                        <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION['csrf_token'], ENT_QUOTES, 'UTF-8') ?>">
                        <input type="hidden" name="role" value="<?= htmlspecialchars($allowed_roles[0], ENT_QUOTES, 'UTF-8') ?>">
                        
                        <div class="row mb-4">
                            <div class="col-md-6 mb-3 mb-md-0">
                                <label for="fullname" class="form-label">Full Name</label>
                                <div class="input-group">
                                    <span class="input-group-text"><i class="fas fa-user"></i></span>
                                    <input type="text" class="form-control" id="fullname" name="fullname" 
                                           value="<?= htmlspecialchars($_POST['fullname'] ?? '', ENT_QUOTES, 'UTF-8') ?>" 
                                           required maxlength="100">
                                    <div class="invalid-feedback">
                                        Please provide a valid full name (1-100 characters).
                                    </div>
                                </div>
                            </div>
                            
                            <div class="col-md-6">
                                <label for="email" class="form-label">Email Address</label>
                                <div class="input-group">
                                    <span class="input-group-text"><i class="fas fa-envelope"></i></span>
                                    <input type="email" class="form-control" id="email" name="email" 
                                           value="<?= htmlspecialchars($_POST['email'] ?? '', ENT_QUOTES, 'UTF-8') ?>" 
                                           required maxlength="255">
                                    <div class="invalid-feedback">
                                        Please provide a valid email address.
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        <div class="row mb-4">
                            <div class="col-md-6 mb-3 mb-md-0">
                                <label for="password" class="form-label">Password</label>
                                <div class="input-group password-container">
                                    <input type="password" class="form-control" id="password" name="password" 
                                           pattern="[0-9]*" inputmode="numeric" required minlength="8"
                                           oninput="this.value=this.value.replace(/[^0-9]/g,'');updatePasswordStrength()">
                                    <span class="password-toggle" onclick="togglePassword('password')">
                                        <i class="fas fa-eye"></i>
                                    </span>
                                    <div class="invalid-feedback">
                                        Password must be at least 8 numeric digits.
                                    </div>
                                </div>
                                <div class="password-strength mt-2">
                                    <div class="strength-meter" id="password-strength"></div>
                                </div>
                                <small class="text-muted">Must be 8 or more numeric digits</small>
                            </div>
                            
                            <div class="col-md-6">
                                <label for="confirm_password" class="form-label">Confirm Password</label>
                                <div class="input-group password-container">
                                    <input type="password" class="form-control" id="confirm_password" name="confirm_password" 
                                           pattern="[0-9]*" inputmode="numeric" required minlength="8"
                                           oninput="this.value=this.value.replace(/[^0-9]/g,'')">
                                    <span class="password-toggle" onclick="togglePassword('confirm_password')">
                                        <i class="fas fa-eye"></i>
                                    </span>
                                    <div class="invalid-feedback">
                                        Passwords must match.
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        <div class="d-grid gap-3 mt-4">
                            <button type="submit" class="btn btn-primary py-2">
                                <i class="fas fa-user-plus me-2"></i>Create Account
                            </button>
                            <a href="user_management.php" class="btn btn-outline-secondary py-2">
                                <i class="fas fa-arrow-left me-2"></i>Back to User Management
                            </a>
                        </div>
                    </form>
                </div>
            <?php else: ?>
                
                <!-- User Management Table -->
                <?php if (in_array($userData['role'], ['admin', 'small-admi'])): ?>
                    <a href="user_management.php?action=add" class="btn btn-primary mb-3">
                        <i class="fas fa-user-plus"></i> Add New User
                    </a>
                <?php endif; ?>
                
                <?php if ($userData['role'] === 'small-admi' && !empty($managedUsers)): ?>
                    <h4>Your Managed Users</h4>
                    <div class="table-responsive">
                        <table class="table table-bordered table-hover">
                            <thead class="thead-light">
                                <tr>
                                    <th>#</th>
                                    <th>Name</th>
                                    <th>Email</th>
                                    <th>Role</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($managedUsers as $i => $user): ?>
                                <tr>
                                    <td><?= $i + 1 ?></td>
                                    <td><?= htmlspecialchars($user['fullname'], ENT_QUOTES, 'UTF-8') ?></td>
                                    <td><?= htmlspecialchars($user['email'], ENT_QUOTES, 'UTF-8') ?></td>
                                    <td><?= htmlspecialchars($user['role'], ENT_QUOTES, 'UTF-8') ?></td>
                                    <td>
                                        <a href="edit_user.php?id=<?= (int)$user['id'] ?>" class="btn btn-sm btn-warning">
                                            <i class="fas fa-edit"></i> Edit
                                        </a>
                                        <a href="delete_user.php?id=<?= (int)$user['id'] ?>" 
                                           onclick="return confirm('Are you sure you want to delete this user?')" 
                                           class="btn btn-sm btn-danger">
                                            <i class="fas fa-trash"></i> Delete
                                        </a>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php elseif ($userData['role'] === 'small-admi' && empty($managedUsers)): ?>
                    <div class="alert alert-info">
                        <i class="fas fa-info-circle me-2"></i> You don't have any managed users yet.
                    </div>
                <?php endif; ?>
                
                <?php if ($userData['role'] === 'admin' && !empty($smallAdmins)): ?>
                    <h4>All Small Admins</h4>
                    <div class="table-responsive">
                        <table class="table table-bordered table-hover">
                            <thead class="thead-light">
                                <tr>
                                    <th>#</th>
                                    <th>Full Name</th>
                                    <th>Email</th>
                                    <th>Admin Code</th>
                                    <th>Managed Users</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($smallAdmins as $i => $admin): ?>
                                <tr>
                                    <td><?= $i + 1 ?></td>
                                    <td><?= htmlspecialchars($admin['fullname'], ENT_QUOTES, 'UTF-8') ?></td>
                                    <td><?= htmlspecialchars($admin['email'], ENT_QUOTES, 'UTF-8') ?></td>
                                    <td><?= htmlspecialchars($admin['admin_code'], ENT_QUOTES, 'UTF-8') ?></td>
                                    <td><?= (int)$admin['user_count'] ?></td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php elseif ($userData['role'] === 'admin' && empty($smallAdmins)): ?>
                    <div class="alert alert-info">
                        <i class="fas fa-info-circle me-2"></i> There are no small admins registered yet.
                    </div>
                <?php endif; ?>
            <?php endif; ?>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Update password strength meter
        function updatePasswordStrength() {
            const password = document.getElementById('password').value;
            const strengthMeter = document.getElementById('password-strength');
            let strength = 0;
            
            // Length check
            if (password.length >= 8) strength += 1;
            if (password.length >= 12) strength += 1;
            
            // Update strength meter (scale 0-2)
            const width = Math.min(strength * 50, 100);
            strengthMeter.style.width = width + '%';
            
            // Update color based on strength
            if (strength < 1) {
                strengthMeter.style.backgroundColor = '#dc3545'; // Weak
            } else if (strength < 2) {
                strengthMeter.style.backgroundColor = '#fd7e14'; // Medium
            } else {
                strengthMeter.style.backgroundColor = '#28a745'; // Strong
            }
        }
        
        // Toggle password visibility
        function togglePassword(fieldId) {
            const field = document.getElementById(fieldId);
            const icon = field.parentNode.querySelector('.fa-eye, .fa-eye-slash');
            
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
                    
                    // Check password match
                    const password = document.getElementById('password');
                    const confirmPassword = document.getElementById('confirm_password');
                    
                    if (password.value !== confirmPassword.value) {
                        confirmPassword.setCustomValidity("Passwords must match");
                    } else {
                        confirmPassword.setCustomValidity("");
                    }
                    
                    form.classList.add('was-validated');
                }, false);
            });
        })();
        
        // Initialize password strength meter
        document.addEventListener('DOMContentLoaded', function() {
            updatePasswordStrength();
        });
    </script>
</body>
</html>
<?php
// Close database connection
$pdo = null;
?>