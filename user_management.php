<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}
require_once 'config.php';

// Prepare sidebar variables
$role = $_SESSION['role'] ?? 'user';
$fullname = $_SESSION['user_name'] ?? 'User Name';
$email = $_SESSION['user_email'] ?? 'user@example.com';
$profileImage = $_SESSION['profile_image'] ?? 'default-avatar.png';
$admin_code = $_SESSION['admin_code'] ?? null;

// Handle form submissions
$errors = [];
$success = false;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Add User Logic
    if (isset($_POST['add_user'])) {
        $new_fullname = trim($_POST['fullname'] ?? '');
        $new_email = trim($_POST['email'] ?? '');
        $new_password = $_POST['password'] ?? '';
        $confirm_password = $_POST['confirm_password'] ?? '';
        
        // Validate inputs
        if (empty($new_fullname)) {
            $errors[] = 'Full name is required.';
        } elseif (!preg_match("/^[A-Za-zÀ-ÿ\s']+$/", $new_fullname)) {
            $errors[] = 'Full name can only contain letters and spaces.';
        } elseif (strlen($new_fullname) < 2 || strlen($new_fullname) > 100) {
            $errors[] = 'Full name must be between 2-100 characters.';
        }
        
        if (empty($new_email)) {
            $errors[] = 'Email is required.';
        } elseif (!filter_var($new_email, FILTER_VALIDATE_EMAIL)) {
            $errors[] = 'Invalid email format.';
        }
        
        if (empty($new_password)) {
            $errors[] = 'Password is required.';
        } elseif (strlen($new_password) < 8) {
            $errors[] = 'Password must be at least 8 characters.';
        } elseif (!preg_match("/[A-Z]/", $new_password)) {
            $errors[] = 'Password must contain at least one uppercase letter.';
        } elseif (!preg_match("/[a-z]/", $new_password)) {
            $errors[] = 'Password must contain at least one lowercase letter.';
        } elseif (!preg_match("/[0-9]/", $new_password)) {
            $errors[] = 'Password must contain at least one number.';
        } elseif ($new_password !== $confirm_password) {
            $errors[] = 'Passwords do not match.';
        }
        
        // Only proceed if no errors
        if (empty($errors)) {
            try {
                $pdo = new PDO("mysql:host=" . DB_HOST . ";dbname=" . DB_NAME, DB_USER, DB_PASS);
                $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
                
                // Check if email exists
                $stmt = $pdo->prepare("SELECT id FROM users WHERE email = ?");
                $stmt->execute([$new_email]);
                if ($stmt->rowCount() > 0) {
                    $errors[] = 'Email already exists.';
                } else {
                    $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);
                    
                    // Set role based on current user's role
                    $new_role = 'user';
                    $new_admin_code = null;
                    $managed_by = null;
                    
                    if ($role === 'admin') {
                        $new_role = 'small-admi';
                        $new_admin_code = uniqid('adm_', true);
                    } elseif ($role === 'small-admi') {
                        $managed_by = $admin_code;
                    }
                    
                    $stmt = $pdo->prepare("INSERT INTO users (fullname, email, password, role, admin_code, managed_by) 
                                          VALUES (?, ?, ?, ?, ?, ?)");
                    $stmt->execute([
                        $new_fullname, 
                        $new_email, 
                        $hashed_password, 
                        $new_role,
                        $new_admin_code,
                        $managed_by
                    ]);
                    $success = true;
                }
            } catch (PDOException $e) {
                $errors[] = 'Database error: ' . $e->getMessage();
            }
        }
    }
    // Delete User Logic
    elseif (isset($_POST['delete_user'])) {
        $user_id = $_POST['user_id'] ?? 0;
        
        try {
            $pdo = new PDO("mysql:host=" . DB_HOST . ";dbname=" . DB_NAME, DB_USER, DB_PASS);
            $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            
            if ($role === 'admin') {
                // Admin can only delete small-admin users
                $stmt = $pdo->prepare("DELETE FROM users WHERE id = ? AND role = 'small-admi'");
                $stmt->execute([$user_id]);
            } elseif ($role === 'small-admi') {
                // Small-admin can only delete their own users
                $stmt = $pdo->prepare("DELETE FROM users WHERE id = ? AND managed_by = ?");
                $stmt->execute([$user_id, $admin_code]);
            }
            
            if ($stmt->rowCount() > 0) {
                $success = true;
                header("Location: user_management.php");
                exit();
            }
        } catch (PDOException $e) {
            $errors[] = 'Database error: ' . $e->getMessage();
        }
    }
    // Edit User Logic
    elseif (isset($_POST['edit_user'])) {
        $user_id = $_POST['user_id'] ?? 0;
        $new_fullname = trim($_POST['fullname'] ?? '');
        $new_email = trim($_POST['email'] ?? '');
        
        // Validate inputs
        if (empty($new_fullname)) {
            $errors[] = 'Full name is required.';
        }
        if (empty($new_email)) {
            $errors[] = 'Email is required.';
        } elseif (!filter_var($new_email, FILTER_VALIDATE_EMAIL)) {
            $errors[] = 'Invalid email format.';
        }
        
        if (empty($errors)) {
            try {
                $pdo = new PDO("mysql:host=" . DB_HOST . ";dbname=" . DB_NAME, DB_USER, DB_PASS);
                $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
                
                // Check if email exists for another user
                $stmt = $pdo->prepare("SELECT id FROM users WHERE email = ? AND id != ?");
                $stmt->execute([$new_email, $user_id]);
                if ($stmt->rowCount() > 0) {
                    $errors[] = 'Email already exists for another user.';
                } else {
                    if ($role === 'admin') {
                        // Admin can only edit small-admin users
                        $stmt = $pdo->prepare("UPDATE users SET fullname = ?, email = ? WHERE id = ? AND role = 'small-admi'");
                        $stmt->execute([$new_fullname, $new_email, $user_id]);
                    } elseif ($role === 'small-admi') {
                        // Small-admin can only edit their own users
                        $stmt = $pdo->prepare("UPDATE users SET fullname = ?, email = ? WHERE id = ? AND managed_by = ?");
                        $stmt->execute([$new_fullname, $new_email, $user_id, $admin_code]);
                    }
                    
                    if ($stmt->rowCount() > 0) {
                        $success = true;
                        header("Location: user_management.php");
                        exit();
                    }
                }
            } catch (PDOException $e) {
                $errors[] = 'Database error: ' . $e->getMessage();
            }
        }
    }
}

// Fetch users based on role
try {
    $pdo = new PDO("mysql:host=" . DB_HOST . ";dbname=" . DB_NAME, DB_USER, DB_PASS);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    if ($role === 'admin') {
        // Admin can only see small-admin users
        $stmt = $pdo->query("SELECT id, fullname, email, admin_code FROM users WHERE role = 'small-admi'");
        $users = $stmt->fetchAll(PDO::FETCH_ASSOC);
        // Fetch managed user count for each small-admin
        foreach ($users as &$user) {
            $stmt2 = $pdo->prepare("SELECT COUNT(*) FROM users WHERE managed_by = ?");
            $stmt2->execute([$user['admin_code']]);
            $user['managed_count'] = $stmt2->fetchColumn();
        }
        unset($user);
        $tableTitle = 'All Small Admins';
    } elseif ($role === 'small-admi') {
        // Small-admin can only see their own users
        $stmt = $pdo->prepare("SELECT id, fullname, email, role FROM users WHERE managed_by = ?");
        $stmt->execute([$admin_code]);
        $users = $stmt->fetchAll(PDO::FETCH_ASSOC);
        $tableTitle = 'Users You Created';
    } else {
        $users = [];
        $tableTitle = 'No Access';
    }
} catch (PDOException $e) {
    die("Database connection failed: " . $e->getMessage());
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>User Management</title>
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
    .main-content {
      margin-left: 250px;
      padding: 2rem;
      background-color: transparent;
    }
    .page-header {
      background: rgba(255,255,255,0.9);
      border-radius: var(--radius-lg);
      padding: 1.5rem;
      margin-bottom: 2rem;
      box-shadow: var(--shadow-sm);
      border-left: 4px solid var(--primary);
    }
    .chart-container {
      background: rgba(255,255,255,0.9);
      border-radius: var(--radius-lg);
      padding: 1.5rem;
      margin-bottom: 2rem;
      box-shadow: var(--shadow-sm);
      backdrop-filter: blur(5px);
      border: 1px solid rgba(0,0,0,0.05);
    }
    .chart-title {
      font-weight: 600;
      margin-bottom: 1.5rem;
      color: var(--primary);
      display: flex;
      align-items: center;
      gap: 0.75rem;
    }
    .btn-primary {
      background: var(--primary);
      border: none;
    }
    .btn-primary:hover {
      background: var(--primary-light);
    }
    .table thead {
      background: var(--primary-light);
      color: #fff;
    }
    .modal-header {
      background: var(--primary-light);
      color: #fff;
    }
    @media (max-width: 992px) {
      .main-content {
        margin-left: 0;
        padding: 1rem;
      }
    }
    @media (max-width: 768px) {
      .main-content {
        padding: 1rem;
      }
      .page-header, .chart-container {
        padding: 1rem;
      }
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
    .was-validated .form-control:invalid,
    .form-control.is-invalid {
      border-color: #dc3545;
      background-image: url("data:image/svg+xml,%3csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 12 12' width='12' height='12' fill='none' stroke='%23dc3545'%3e%3ccircle cx='6' cy='6' r='4.5'/%3e%3cpath stroke-linejoin='round' d='M5.8 3.6h.4L6 6.5z'/%3e%3ccircle cx='6' cy='8.2' r='.6' fill='%23dc3545' stroke='none'/%3e%3c/svg%3e");
      background-repeat: no-repeat;
      background-position: right calc(0.375em + 0.1875rem) center;
      background-size: calc(0.75em + 0.375rem) calc(0.75em + 0.375rem);
    }
    
    .was-validated .form-control:valid,
    .form-control.is-valid {
      border-color: #28a745;
      background-image: url("data:image/svg+xml,%3csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 8 8'%3e%3cpath fill='%2328a745' d='M2.3 6.73L.6 4.53c-.4-1.04.46-1.4 1.1-.8l1.1 1.4 3.4-3.8c.6-.63 1.6-.27 1.2.7l-4 4.6c-.43.5-.8.4-1.1.1z'/%3e%3c/svg%3e");
      background-repeat: no-repeat;
      background-position: right calc(0.375em + 0.1875rem) center;
      background-size: calc(0.75em + 0.375rem) calc(0.75em + 0.375rem);
    }
    
    .invalid-feedback {
      display: none;
      width: 100%;
      margin-top: 0.25rem;
      font-size: 0.875em;
      color: #dc3545;
    }
    
    .was-validated .form-control:invalid ~ .invalid-feedback,
    .form-control.is-invalid ~ .invalid-feedback {
      display: block;
    }
    
    .password-requirements .valid {
      color: #28a745;
    }
    
    .password-requirements .valid i {
      content: "\f058";
      font-family: "Font Awesome 6 Free";
      font-weight: 900;
    }
    
    #addUserModal .modal-content {
      border-radius: 0.8rem;
      overflow: hidden;
    }
    
    #addUserModal .modal-header {
      border-bottom: none;
      padding: 1.2rem 1.5rem;
    }
    
    #addUserModal .modal-body {
      padding: 1.5rem 2rem;
    }
    
    #addUserModal .form-control {
      border-left: none;
      padding: 0.6rem 0.75rem;
      border-color: #dee2e6;
    }
    
    #addUserModal .form-control:focus {
      box-shadow: none;
      border-color: #5e35b1;
    }
    
    #addUserModal .input-group-text {
      border-right: none;
      background-color: #f8f9fa;
    }
    
    #addUserModal .toggle-password {
      border-left: none;
      background-color: #f8f9fa;
    }
    
    #addUserModal .toggle-password:hover {
      background-color: #e9ecef;
    }
    
    #addUserModal .password-requirements ul {
      padding-left: 1.5rem;
    }
    
    #addUserModal .password-requirements li {
      font-size: 0.85rem;
      margin-bottom: 0.25rem;
    }
    
    #addUserModal .modal-footer {
      border-top: 1px solid #e9ecef;
      padding: 1rem 2rem;
    }
    
    .validation-container {
      position: fixed;
      top: 20px;
      right: 20px;
      z-index: 1100;
      max-width: 400px;
    }
    
    .action-buttons {
      white-space: nowrap;
    }
    
    .action-buttons .btn {
      margin-right: 5px;
    }
    
    .action-buttons form {
      display: inline-block;
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
      <?php if (!in_array($role, ['admin', 'small-admin', 'small-admi'])): ?>
      <li class="nav-item">
        <a class="nav-link <?= basename($_SERVER['PHP_SELF']) == 'analyze.php' ? 'active' : '' ?>" href="analyze.php">
          <i class="fas fa-chart-bar"></i> Dashboard
        </a>
      </li>
      <?php endif; ?>
      <li class="nav-item">
        <a class="nav-link <?= basename($_SERVER['PHP_SELF']) == 'dashboard.php' ? 'active' : '' ?>" href="dashboard.php">
          <i class="fas fa-users-cog"></i> Dashboard
        </a>
      </li>
      <li class="nav-item">
        <a class="nav-link <?= basename($_SERVER['PHP_SELF']) == 'predict.php' ? 'active' : '' ?>" href="predict.php">
          <i class="fas fa-microscope"></i> Prediction
        </a>
      </li>
      <li class="nav-item">
        <a class="nav-link <?= basename($_SERVER['PHP_SELF']) == 'report.php' ? 'active' : '' ?>" href="report.php">
          <i class="fas fa-file-medical"></i> Report
        </a>
      </li>
      <li class="nav-item">
        <a class="nav-link <?= basename($_SERVER['PHP_SELF']) == 'user_management.php' ? 'active' : '' ?>" href="user_management.php">
          <i class="fas fa-file-medical"></i> User Management
        </a>
      </li>
    </ul>
    <!-- Profile Section -->
    <div class="profile-section">
      <img src="<?= htmlspecialchars($profileImage) ?>" class="profile-img" alt="Profile">
      <h5 class="mb-1 text-white"><?= htmlspecialchars($fullname) ?></h5>
      <p class="small mb-2 text-white-50"><?= htmlspecialchars($email) ?></p>
      <span class="badge bg-light text-primary"><?= htmlspecialchars($role) ?></span>
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
    <div class="page-header mb-4">
      <h2 class="mb-0"><i class="fas fa-users-cog"></i> User Management</h2>
    </div>
    
    <!-- Validation Messages -->
    <?php if ($success || !empty($errors)): ?>
    <div class="validation-container">
      <?php if ($success): ?>
        <div class="alert alert-success alert-dismissible fade show">
          <i class="fas fa-check-circle me-2"></i> Operation completed successfully!
          <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
      <?php endif; ?>
      <?php if (!empty($errors)): ?>
        <div class="alert alert-danger alert-dismissible fade show">
          <i class="fas fa-exclamation-triangle me-2"></i>
          <ul class="mb-0 ps-3">
            <?php foreach ($errors as $error): ?>
              <li><?= htmlspecialchars($error) ?></li>
            <?php endforeach; ?>
          </ul>
          <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
      <?php endif; ?>
    </div>
    <?php endif; ?>
    
    <div class="chart-container">
      <div class="d-flex justify-content-between align-items-center mb-3">
        <h5 class="mb-0"><?= htmlspecialchars($tableTitle) ?></h5>
        <?php if (in_array($role, ['admin', 'small-admi'])): ?>
          <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addUserModal">
            <i class="fas fa-user-plus"></i> Add New User
          </button>
        <?php endif; ?>
      </div>

      <div class="table-responsive">
        <table class="table table-bordered table-hover">
          <thead class="thead-light">
            <tr>
              <th>#</th>
              <th>Full Name</th>
              <th>Email</th>
              <?php if ($role === 'admin'): ?>
                <th>Admin Code</th>
                <th>Managed Users</th>
              <?php else: ?>
                <th>Role</th>
              <?php endif; ?>
              <?php if (in_array($role, ['small-admi', 'small-admin'])): ?>
                <th>Actions</th>
              <?php endif; ?>
            </tr>
          </thead>
          <tbody>
            <?php if (empty($users)): ?>
              <tr>
                <td colspan="<?= $role === 'admin' ? 6 : (in_array($role, ['small-admi', 'small-admin']) ? 5 : 4) ?>" class="text-center">
                  No users found
                </td>
              </tr>
            <?php else: ?>
              <?php foreach ($users as $i => $user): ?>
                <tr>
                  <td><?= $i + 1 ?></td>
                  <td><?= htmlspecialchars($user['fullname']) ?></td>
                  <td><?= htmlspecialchars($user['email']) ?></td>

                  <?php if ($role === 'admin'): ?>
                    <td><?= htmlspecialchars($user['admin_code']) ?></td>
                    <td><?= (int)($user['managed_count'] ?? 0) ?></td>
                  <?php else: ?>
                    <td><?= htmlspecialchars($user['role']) ?></td>
                  <?php endif; ?>

                  <?php if (in_array($role, ['small-admi', 'small-admin'])): ?>
                    <td class="action-buttons">
                      <a href="edit_user.php?id=<?= $user['id'] ?>" class="btn btn-sm btn-warning me-1">
                        <i class="fas fa-edit"></i> Edit
                      </a>
                      <form method="POST" action="user_management.php" style="display: inline;">
                        <input type="hidden" name="delete_user" value="1">
                        <input type="hidden" name="user_id" value="<?= $user['id'] ?>">
                        <button type="submit" class="btn btn-sm btn-danger" onclick="return confirm('Are you sure you want to delete this user?');">
                          <i class="fas fa-trash-alt"></i> Delete
                        </button>
                      </form>
                    </td>
                  <?php endif; ?>
                </tr>
              <?php endforeach; ?>
            <?php endif; ?>
          </tbody>
        </table>
      </div>
    </div>
    
    <!-- Add User Modal -->
    <div class="modal fade" id="addUserModal" tabindex="-1" aria-labelledby="addUserModalLabel" aria-hidden="true">
      <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow-lg">
          <div class="modal-header bg-primary text-white">
            <h5 class="modal-title" id="addUserModalLabel">
              <i class="fas fa-user-plus me-2"></i> Add New <?= $role === 'admin' ? 'Small Admin' : 'User' ?>
            </h5>
            <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
          </div>
          <form id="userForm" method="POST" action="user_management.php" class="needs-validation" novalidate>
            <div class="modal-body p-4">
              <input type="hidden" name="add_user" value="1">
              
              <!-- Full Name Field -->
              <div class="mb-4">
                <label for="fullname" class="form-label fw-semibold">Full Name</label>
                <div class="input-group">
                  <span class="input-group-text bg-light">
                    <i class="fas fa-user text-primary"></i>
                  </span>
                  <input type="text" class="form-control py-2" id="fullname" name="fullname" 
                         placeholder="Enter full name" required minlength="2" maxlength="100"
                         pattern="[A-Za-zÀ-ÿ\s']+" 
                         title="Only letters, spaces and apostrophes are allowed">
                </div>
                <div class="invalid-feedback">
                  Please enter a valid name (2-100 characters, only letters and spaces)
                </div>
              </div>
              
              <!-- Email Field -->
              <div class="mb-4">
                <label for="email" class="form-label fw-semibold">Email Address</label>
                <div class="input-group">
                  <span class="input-group-text bg-light">
                    <i class="fas fa-envelope text-primary"></i>
                  </span>
                  <input type="email" class="form-control py-2" id="email" name="email" 
                         placeholder="Enter email address" required maxlength="255"
                         pattern="[a-z0-9._%+-]+@[a-z0-9.-]+\.[a-z]{2,}$">
                </div>
                <div class="invalid-feedback">
                  Please enter a valid email address (e.g., user@example.com)
                </div>
              </div>
              
              <!-- Password Field -->
              <div class="mb-4">
                <label for="password" class="form-label fw-semibold">Password</label>
                <div class="input-group">
                  <span class="input-group-text bg-light">
                    <i class="fas fa-lock text-primary"></i>
                  </span>
                  <input type="password" class="form-control py-2" id="password" name="password" 
                         placeholder="Create password" required minlength="8" maxlength="64"
                         pattern="^(?=.*[a-z])(?=.*[A-Z])(?=.*\d).{8,}$">
                  <button class="btn btn-outline-secondary toggle-password" type="button">
                    <i class="fas fa-eye"></i>
                  </button>
                </div>
                <div class="invalid-feedback">
                  Password must meet all requirements below
                </div>
                <div class="password-requirements mt-2">
                  <small class="d-block text-muted">Password Requirements:</small>
                  <ul class="list-unstyled mb-0">
                    <li class="text-danger" data-requirement="length">
                      <i class="fas fa-times-circle me-1"></i> At least 8 characters
                    </li>
                    <li class="text-danger" data-requirement="uppercase">
                      <i class="fas fa-times-circle me-1"></i> 1 uppercase letter
                    </li>
                    <li class="text-danger" data-requirement="lowercase">
                      <i class="fas fa-times-circle me-1"></i> 1 lowercase letter
                    </li>
                    <li class="text-danger" data-requirement="number">
                      <i class="fas fa-times-circle me-1"></i> 1 number
                    </li>
                    <li class="text-danger" data-requirement="match">
                      <i class="fas fa-times-circle me-1"></i> Passwords must match
                    </li>
                  </ul>
                </div>
              </div>
              
              <!-- Confirm Password Field -->
              <div class="mb-4">
                <label for="confirm_password" class="form-label fw-semibold">Confirm Password</label>
                <div class="input-group">
                  <span class="input-group-text bg-light">
                    <i class="fas fa-lock text-primary"></i>
                  </span>
                  <input type="password" class="form-control py-2" id="confirm_password" name="confirm_password" 
                         placeholder="Confirm password" required minlength="8" maxlength="64">
                  <button class="btn btn-outline-secondary toggle-password" type="button">
                    <i class="fas fa-eye"></i>
                  </button>
                </div>
                <div class="invalid-feedback">
                  Passwords must match
                </div>
              </div>
            </div>
            <div class="modal-footer bg-light">
              <button type="button" class="btn btn-outline-secondary px-4" data-bs-dismiss="modal">
                <i class="fas fa-times me-2"></i>Cancel
              </button>
              <button type="submit" class="btn btn-primary px-4" id="submitBtn">
                <i class="fas fa-user-plus me-2"></i>Create Account
              </button>
            </div>
          </form>
        </div>
      </div>
    </div>
  </div>
  
  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
  <script>
document.addEventListener('DOMContentLoaded', function () {
  const form = document.getElementById('userForm');
  const password = document.getElementById('password');
  const confirmPassword = document.getElementById('confirm_password');
  const requirements = {
    length: document.querySelector('[data-requirement="length"]'),
    uppercase: document.querySelector('[data-requirement="uppercase"]'),
    lowercase: document.querySelector('[data-requirement="lowercase"]'),
    number: document.querySelector('[data-requirement="number"]'),
    match: document.querySelector('[data-requirement="match"]')
  };

  function validatePasswordStrength() {
    const value = password.value;
    const hasUpper = /[A-Z]/.test(value);
    const hasLower = /[a-z]/.test(value);
    const hasNumber = /[0-9]/.test(value);
    const hasLength = value.length >= 8;

    toggleRequirement(requirements.length, hasLength);
    toggleRequirement(requirements.uppercase, hasUpper);
    toggleRequirement(requirements.lowercase, hasLower);
    toggleRequirement(requirements.number, hasNumber);
    validatePasswordMatch();
  }

  function validatePasswordMatch() {
    const isMatch = password.value === confirmPassword.value && password.value !== '';
    toggleRequirement(requirements.match, isMatch);
    if (confirmPassword.classList.contains('was-validated')) {
      confirmPassword.setCustomValidity(isMatch ? '' : 'Passwords do not match');
    }
  }

  function toggleRequirement(element, isValid) {
    element.classList.toggle('text-danger', !isValid);
    element.classList.toggle('text-success', isValid);
    const icon = element.querySelector('i');
    if (icon) {
      icon.className = isValid ? 'fas fa-check-circle me-1' : 'fas fa-times-circle me-1';
    }
  }

  // Realtime checks
  password.addEventListener('input', validatePasswordStrength);
  confirmPassword.addEventListener('input', validatePasswordMatch);

  // Bootstrap validation
  form.addEventListener('submit', function (e) {
    if (!form.checkValidity()) {
      e.preventDefault();
      e.stopPropagation();
    }
    validatePasswordMatch();
    form.classList.add('was-validated');
  });

  // Toggle password visibility
  document.querySelectorAll('.toggle-password').forEach(button => {
    button.addEventListener('click', function () {
      const input = this.previousElementSibling;
      input.type = input.type === 'password' ? 'text' : 'password';
      this.querySelector('i').classList.toggle('fa-eye');
      this.querySelector('i').classList.toggle('fa-eye-slash');
    });
  });
});
</script>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>

  </script>
</body>
</html>