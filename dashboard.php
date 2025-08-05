<?php
// Start session with secure settings
session_start([
    'cookie_httponly' => true,
    'cookie_secure' => true,
    'use_strict_mode' => true
]);

// Redirect if not logged in
if (!isset($_SESSION['user_email'])) {
    header("Location: login.html");
    exit();
}

// Database configuration
define('DB_HOST', 'localhost');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_NAME', 'brain_cancer_db');

// Database connection with error handling
$conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);

if ($conn->connect_error) {
    error_log("Database connection failed: " . $conn->connect_error);
    die("System error. Please try again later.");
}

// Initialize all variables with safe defaults
$userData = [
    'email' => $_SESSION['user_email'] ?? '',
    'role' => $_SESSION['role'] ?? 'user',
    'admin_code' => $_SESSION['admin_code'] ?? null,
    'fullname' => 'Guest User',
    'profile_image' => 'default-avatar.png'
];

$stats = [
    'totalUsers' => 0,
    'totalSmallAdmins' => 0,
    'yourUsers' => 0,
    'totalCases' => 0,
    'totalCancer' => 0,
    'totalNoCancer' => 0,
    'gender' => [
        'male' => 0,
        'female' => 0,
        'male_cancer' => 0,
        'male_nocancer' => 0,
        'female_cancer' => 0,
        'female_nocancer' => 0
    ],
    'age_ranges' => [
        "1-20" => ["cancer" => 0, "no_cancer" => 0],
        "21-40" => ["cancer" => 0, "no_cancer" => 0],
        "41-80" => ["cancer" => 0, "no_cancer" => 0],
        "81-120" => ["cancer" => 0, "no_cancer" => 0]
    ]
];

/**
 * Fetch user profile data
 */
function getUserProfile($conn, $email) {
    $stmt = $conn->prepare("SELECT fullname, profile_image FROM users WHERE email = ?");
    if (!$stmt) return null;
    
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $result = $stmt->get_result();
    $profile = $result->fetch_assoc();
    $stmt->close();
    
    return $profile ?: null;
}

/**
 * Fetch statistics based on user role
 */
function getStatistics($conn, $role, $admin_code = null) {
    $stats = [
        'totalUsers' => 0,
        'totalSmallAdmins' => 0,
        'yourUsers' => 0
    ];
    
    if ($role === 'admin') {
        $result = $conn->query("SELECT COUNT(*) AS total FROM users");
        $stats['totalUsers'] = $result ? $result->fetch_assoc()['total'] : 0;
        
        $result = $conn->query("SELECT COUNT(*) AS total FROM users WHERE role IN ('small-admin', 'small-admi')");
        $stats['totalSmallAdmins'] = $result ? $result->fetch_assoc()['total'] : 0;
    } 
    elseif (in_array($role, ['small-admin', 'small-admi'])) {
        $stmt = $conn->prepare("SELECT COUNT(*) AS total FROM users WHERE managed_by = ?");
        if ($stmt) {
            $stmt->bind_param("s", $admin_code);
            $stmt->execute();
            $result = $stmt->get_result();
            $stats['yourUsers'] = $result ? $result->fetch_assoc()['total'] : 0;
            $stmt->close();
        }
    }
    
    return $stats;
}

/**
 * Fetch prediction data based on user role
 */
function getPredictions($conn, $role, $email, $admin_code = null) {
    $data = [
        'total' => 0,
        'cancer' => 0,
        'no_cancer' => 0,
        'gender' => [
            'male' => 0,
            'female' => 0,
            'male_cancer' => 0,
            'male_nocancer' => 0,
            'female_cancer' => 0,
            'female_nocancer' => 0
        ],
        'age_ranges' => [
            "1-20" => ["cancer" => 0, "no_cancer" => 0],
            "21-40" => ["cancer" => 0, "no_cancer" => 0],
            "41-80" => ["cancer" => 0, "no_cancer" => 0],
            "81-120" => ["cancer" => 0, "no_cancer" => 0]
        ]
    ];

    $stmt = null;
    $emails = [];

    if ($role === 'admin') {
        $query = "SELECT prediction, sex, age FROM predictions";
        $stmt = $conn->prepare($query);
    }
    elseif (in_array($role, ['small-admin', 'small-admi'])) {
        // Get all users managed by the small admin
        $stmtUsers = $conn->prepare("SELECT email FROM users WHERE managed_by = ?");
        if ($stmtUsers) {
            $stmtUsers->bind_param("s", $admin_code);
            $stmtUsers->execute();
            $result = $stmtUsers->get_result();
            while ($row = $result->fetch_assoc()) {
                $emails[] = $row['email'];
            }
            $stmtUsers->close();

            // Add small admin's own email
            $emails[] = $email;

            if (!empty($emails)) {
                $placeholders = implode(',', array_fill(0, count($emails), '?'));
                $query = "SELECT prediction, sex, age FROM predictions WHERE user_email IN ($placeholders)";
                $stmt = $conn->prepare($query);
                if ($stmt) {
                    $types = str_repeat('s', count($emails));
                    $stmt->bind_param($types, ...$emails);
                }
            }
        }
    }
    else {
        $query = "SELECT prediction, sex, age FROM predictions WHERE user_email = ?";
        $stmt = $conn->prepare($query);
        if ($stmt) {
            $stmt->bind_param("s", $email);
        }
    }

    if ($stmt && $stmt->execute()) {
        $result = $stmt->get_result();

        while ($row = $result->fetch_assoc()) {
            $prediction = strtolower(trim($row['prediction'] ?? ''));
            $gender = strtolower(trim($row['sex'] ?? ''));
            $age = (int)($row['age'] ?? 0);

            if ($prediction === 'cancer detected') {
                $data['cancer']++;
                $gender === 'female' ? $data['gender']['female_cancer']++ : $data['gender']['male_cancer']++;
            } elseif ($prediction === 'no cancer detected') {
                $data['no_cancer']++;
                $gender === 'female' ? $data['gender']['female_nocancer']++ : $data['gender']['male_nocancer']++;
            }

            $gender === 'female' ? $data['gender']['female']++ : $data['gender']['male']++;

            foreach ($data['age_ranges'] as $range => &$counts) {
                [$min, $max] = explode('-', $range);
                if ($age >= $min && $age <= $max) {
                    $key = $prediction === 'cancer detected' ? 'cancer' : 'no_cancer';
                    $counts[$key]++;
                }
            }
        }
        $stmt->close();
    }

    $data['total'] = $data['cancer'] + $data['no_cancer'];
    return $data;
}


// Fetch all data
$profile = getUserProfile($conn, $userData['email']);
if ($profile) {
    $userData['fullname'] = $profile['fullname'] ?? $userData['fullname'];
    $userData['profile_image'] = $profile['profile_image'] ?? $userData['profile_image'];
}

$roleStats = getStatistics($conn, $userData['role'], $userData['admin_code']);
$stats = array_merge($stats, $roleStats);

$predictionData = getPredictions($conn, $userData['role'], $userData['email'], $userData['admin_code']);
$stats = array_merge($stats, [
    'totalCases' => $predictionData['total'],
    'totalCancer' => $predictionData['cancer'],
    'totalNoCancer' => $predictionData['no_cancer'],
    'gender' => $predictionData['gender'],
    'age_ranges' => $predictionData['age_ranges']
]);

// Fetch user management data if needed
$userManagementData = [];
if (in_array($userData['role'], ['small-admin', 'small-admi', 'admin'])) {
    if (in_array($userData['role'], ['small-admin', 'small-admi'])) {
        $stmt = $conn->prepare("SELECT id, fullname, email, role FROM users WHERE managed_by = ?");
        if ($stmt) {
            $stmt->bind_param("s", $userData['admin_code']);
            $stmt->execute();
            $result = $stmt->get_result();
            while ($row = $result->fetch_assoc()) {
                $userManagementData[] = $row;
            }
            $stmt->close();
        }
    }
    
    if ($userData['role'] === 'admin') {
        $adminData = [];
        $result = $conn->query("SELECT id, fullname, email, admin_code FROM users WHERE role = 'small-admin' OR role = 'small-admi'");
        while ($row = $result->fetch_assoc()) {
            $adminCode = $row['admin_code'];
            $stmt = $conn->prepare("SELECT COUNT(*) AS total FROM users WHERE managed_by = ?");
            $stmt->bind_param("s", $adminCode);
            $stmt->execute();
            $userCount = $stmt->get_result()->fetch_assoc()['total'] ?? 0;
            $stmt->close();
            
            $row['user_count'] = $userCount;
            $adminData[] = $row;
        }
    }
}

// Don't close the connection yet - we'll use it in the HTML part
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Brain Cancer Detection Dashboard</title>
  
  <!-- Bootstrap 5 CSS -->
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
  
  <!-- Font Awesome -->
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
  
  <!-- Chart.js -->
  <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
  <script src="https://cdn.jsdelivr.net/npm/chartjs-plugin-datalabels@2"></script>
  
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
    
    /* Sidebar with gradient background */
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
    
    /* Profile section with glass effect */
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
    
    /* Page header with subtle background */
    .page-header {
      background: rgba(255,255,255,0.9);
      border-radius: var(--radius-lg);
      padding: 1.5rem;
      margin-bottom: 2rem;
      box-shadow: var(--shadow-sm);
      border-left: 4px solid var(--primary);
    }
    
    /* Stat cards with gradient backgrounds */
    .stat-card {
      border-radius: var(--radius);
      padding: 1.5rem;
      color: white;
      box-shadow: var(--shadow-sm);
      transition: transform 0.3s;
      height: 100%;
      border: none;
      overflow: hidden;
      position: relative;
    }
    
    .stat-card::before {
      content: '';
      position: absolute;
      top: -50%;
      right: -50%;
      width: 200%;
      height: 200%;
      background: radial-gradient(circle, rgba(255,255,255,0.2) 0%, rgba(255,255,255,0) 70%);
      transform: rotate(30deg);
      transition: all 0.5s;
    }
    
    .stat-card:hover::before {
      transform: rotate(45deg);
    }
    
    .stat-card:hover {
      transform: translateY(-5px);
      box-shadow: var(--shadow);
    }
    
    .stat-card.primary {
      background: linear-gradient(135deg, var(--primary) 0%, var(--primary-light) 100%);
    }
    
    .stat-card.cancer {
      background: linear-gradient(135deg, #d32f2f 0%, #b71c1c 100%);
    }
    
    .stat-card.no-cancer {
      background: linear-gradient(135deg, #388e3c 0%, #1b5e20 100%);
    }
    
    .stat-card.users {
      background: linear-gradient(135deg, #0288d1 0%, #01579b 100%);
    }
    
    .stat-card.warning {
      background: linear-gradient(135deg, #ffa000 0%, #ff6f00 100%);
    }
    
    .stat-icon {
      font-size: 1.5rem;
      margin-bottom: 1rem;
      color: rgba(255,255,255,0.8);
    }
    
    .stat-number {
      font-size: 2rem;
      font-weight: 700;
      color: white;
    }
    
    /* Chart containers with glass effect */
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
    
    /* Gender cards with soft backgrounds */
    .gender-card {
      border-radius: var(--radius);
      padding: 1.5rem;
      background: rgba(255,255,255,0.9);
      box-shadow: var(--shadow-sm);
      height: 100%;
    }
    
    .gender-card.male {
      border-left: 4px solid #1976d2;
    }
    
    .gender-card.female {
      border-left: 4px solid #c2185b;
    }
    
    /* Age range items */
    .age-range-item {
      background: rgba(255,255,255,0.9);
      border-radius: var(--radius);
      padding: 1rem;
      text-align: center;
      box-shadow: var(--shadow-sm);
      transition: all 0.3s;
    }
    
    .age-range-item:hover {
      transform: translateY(-3px);
      box-shadow: var(--shadow);
    }
    
    /* Responsive adjustments */
    @media (max-width: 992px) {
      .sidebar {
        width: 100%;
        position: relative;
        min-height: auto;
      }
      
      .main-content {
        margin-left: 0;
      }
      
      .stat-card {
        margin-bottom: 1rem;
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
  </style>
</head>
<body>
  <!-- Sidebar -->
  <div class="sidebar">
    <div class="sidebar-brand">
      <i class="fas fa-brain me-2"></i>Brain Cancer Detection
    </div>
    
    <ul class="nav flex-column sidebar-nav">
      <?php if (!in_array($userData['role'], ['admin', 'small-admin', 'small-admi'])): ?>
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
        <a class="nav-link <?= basename($_SERVER['PHP_SELF']) == 'add_user.php' ? 'active' : '' ?>" href="add_user.php">
          <i class="fas fa-file-medical"></i> user manegment
        </a>
      </li>
    </ul>
     
   
    
    <!-- Profile Section -->
    <div class="profile-section">
      <img src="<?= htmlspecialchars($userData['profile_image']) ?>" class="profile-img" alt="Profile">
      <h5 class="mb-1 text-white"><?= htmlspecialchars($userData['fullname']) ?></h5>
      <p class="small mb-2 text-white-50"><?= htmlspecialchars($userData['email']) ?></p>
      <span class="badge bg-light text-primary"><?= htmlspecialchars($userData['role']) ?></span>
      
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
    <!-- Page Header -->
    <div class="page-header mb-4">
      <h1 class="mb-3">
        <i class="fas fa-tachometer-alt me-2"></i>
        <?= in_array($userData['role'], ['admin', 'small-admin', 'small-admi']) ? 'Hospital Dashboard' : 'My Dashboard' ?>
      </h1>
      <p class="text-muted mb-0">Welcome back! Here's your latest statistics and insights.</p>
    </div>
    
    <!-- Stats Cards -->
    <div class="row mb-4">
      <?php if ($userData['role'] === 'admin'): ?>
        <div class="col-md-3 mb-4">
          <div class="stat-card users">
            <div class="stat-icon">
              <i class="fas fa-users"></i>
            </div>
            <div class="stat-number"><?= $stats['totalUsers'] ?></div>
            <div>Total Users</div>
          </div>
        </div>
        <div class="col-md-3 mb-4">
          <div class="stat-card warning">
            <div class="stat-icon">
              <i class="fas fa-user-shield"></i>
            </div>
            <div class="stat-number"><?= $stats['totalSmallAdmins'] ?></div>
            <div>Hospital Admins</div>
          </div>
        </div>
      <?php elseif (in_array($userData['role'], ['small-admin', 'small-admi'])): ?>
        <div class="col-md-3 mb-4">
          <div class="stat-card users">
            <div class="stat-icon">
              <i class="fas fa-user-friends"></i>
            </div>
            <div class="stat-number"><?= $stats['yourUsers'] ?></div>
            <div>Your Users</div>
          </div>
        </div>
      <?php endif; ?>
      
      <div class="col-md-3 mb-4">
        <div class="stat-card primary">
          <div class="stat-icon">
            <i class="fas fa-chart-line"></i>
          </div>
          <div class="stat-number"><?= $stats['totalCases'] ?></div>
          <div>Total Cases</div>
        </div>
      </div>
      
      <div class="col-md-3 mb-4">
        <div class="stat-card cancer">
          <div class="stat-icon">
            <i class="fas fa-exclamation-triangle"></i>
          </div>
          <div class="stat-number"><?= $stats['totalCancer'] ?></div>
          <div>Cancer Cases</div>
        </div>
      </div>
      
      <div class="col-md-3 mb-4">
        <div class="stat-card no-cancer">
          <div class="stat-icon">
            <i class="fas fa-check-circle"></i>
          </div>
          <div class="stat-number"><?= $stats['totalNoCancer'] ?></div>
          <div>No Cancer</div>
        </div>
      </div>
    </div>
    
    <!-- Charts Section -->
    <div class="row">
      <!-- Cancer Pie Chart -->
      <div class="col-md-6 mb-4">
        <div class="chart-container">
          <h3 class="chart-title">
            <i class="fas fa-chart-pie text-danger"></i>
            Cancer Detection Rate
          </h3>
          <canvas id="cancerPie"></canvas>
        </div>
      </div>
      
      <!-- Gender Pie Chart -->
      <div class="col-md-6 mb-4">
        <div class="chart-container">
          <h3 class="chart-title">
            <i class="fas fa-venus-mars text-primary"></i>
            Gender Distribution
          </h3>
          <canvas id="genderPie"></canvas>
        </div>
      </div>
    </div>
    
    <!-- Gender Stats -->
    <div class="row mb-4">
      <div class="col-md-6 mb-4">
        <div class="gender-card male">
          <h3 class="chart-title">
            <i class="fas fa-mars text-primary"></i>
            Male Patients
          </h3>
          <div class="d-flex justify-content-around mt-3">
            <div class="text-center">
              <h2 class="text-danger"><?= $stats['gender']['male_cancer'] ?></h2>
              <small class="text-muted">Cancer Cases</small>
            </div>
            <div class="text-center">
              <h2 class="text-success"><?= $stats['gender']['male_nocancer'] ?></h2>
              <small class="text-muted">No Cancer</small>
            </div>
          </div>
        </div>
      </div>
      
      <div class="col-md-6 mb-4">
        <div class="gender-card female">
          <h3 class="chart-title">
            <i class="fas fa-venus text-danger"></i>
            Female Patients
          </h3>
          <div class="d-flex justify-content-around mt-3">
            <div class="text-center">
              <h2 class="text-danger"><?= $stats['gender']['female_cancer'] ?></h2>
              <small class="text-muted">Cancer Cases</small>
            </div>
            <div class="text-center">
              <h2 class="text-success"><?= $stats['gender']['female_nocancer'] ?></h2>
              <small class="text-muted">No Cancer</small>
            </div>
          </div>
        </div>
      </div>
    </div>
    
    <!-- Age Range Analysis -->
    <div class="chart-container">
      <h3 class="chart-title">
        <i class="fas fa-chart-bar text-primary"></i>
        Age Range Analysis
      </h3>
      <div class="row">
        <?php foreach ($stats['age_ranges'] as $range => $data): ?>
        <div class="col-md-3 col-6 mb-3">
          <div class="age-range-item">
            <h4><?= $range ?> Years</h4>
            <div class="d-flex justify-content-around mt-2">
              <div class="text-danger">
                <h3><?= $data['cancer'] ?></h3>
                <small class="text-muted">Cancer</small>
              </div>
              <div class="text-success">
                <h3><?= $data['no_cancer'] ?></h3>
                <small class="text-muted">No Cancer</small>
              </div>
            </div>
          </div>
        </div>
        <?php endforeach; ?>
      </div>
    </div>
    
    <!-- Age Distribution Chart -->
    <div class="chart-container">
      <h3 class="chart-title">
        <i class="fas fa-chart-area text-primary"></i>
        Age Distribution Chart
      </h3>
      <canvas id="ageBarChart"></canvas>
    </div>
    
    <!-- User Management Section -->
    <div class="chart-container mt-4">
      <h3 class="chart-title">
        <i class="fas fa-users-cog text-primary"></i>
        User Management
      </h3>
      
      <?php if (in_array($userData['role'], ['admin', 'small-admin', 'small-admi'])): ?>
        <a href="add_user.php" class="btn btn-primary mb-3">
          <i class="fas fa-user-plus"></i> Add New User
        </a>
      <?php endif; ?>
      
      <?php if (in_array($userData['role'], ['small-admin', 'small-admi']) && !empty($userManagementData)): ?>
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
              <?php foreach ($userManagementData as $i => $row): ?>
              <tr>
                <td><?= $i + 1 ?></td>
                <td><?= htmlspecialchars($row['fullname']) ?></td>
                <td><?= htmlspecialchars($row['email']) ?></td>
                <td><?= htmlspecialchars($row['role']) ?></td>
                <td>
                  <a href="edit_user.php?id=<?= $row['id'] ?>" class="btn btn-sm btn-warning">
                    <i class="fas fa-edit"></i> Edit
                  </a>
                  <a href="delete_user.php?id=<?= $row['id'] ?>" 
                     onclick="return confirm('Are you sure?')" 
                     class="btn btn-sm btn-danger">
                    <i class="fas fa-trash"></i> Delete
                  </a>
                </td>
              </tr>
              <?php endforeach; ?>
            </tbody>
          </table>
        </div>
      <?php endif; ?>
      
      <?php if ($userData['role'] === 'admin' && isset($adminData) && !empty($adminData)): ?>
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
              <?php foreach ($adminData as $i => $row): ?>
              <tr>
                <td><?= $i + 1 ?></td>
                <td><?= htmlspecialchars($row['fullname']) ?></td>
                <td><?= htmlspecialchars($row['email']) ?></td>
                <td><?= htmlspecialchars($row['admin_code']) ?></td>
                <td><?= $row['user_count'] ?></td>
              </tr>
              <?php endforeach; ?>
            </tbody>
          </table>
        </div>
      <?php endif; ?>
    </div>
  </div>

  <!-- Bootstrap JS Bundle with Popper -->
  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
  <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
  
  <script>
    // Register the plugin
    Chart.register(ChartDataLabels);

    // Cancer Pie Chart with percentage labels
    new Chart(document.getElementById('cancerPie'), {
      type: 'pie',
      data: {
        labels: ['Cancer Detected', 'No Cancer Detected'],
        datasets: [{
          data: [<?= $stats['totalCancer'] ?>, <?= $stats['totalNoCancer'] ?>],
          backgroundColor: ['#dc3545', '#28a745'],
          borderWidth: 1
        }]
      },
      options: {
        responsive: true,
        plugins: {
          legend: { 
            position: 'bottom',
            labels: {
              font: {
                size: 14
              }
            }
          },
          tooltip: {
            callbacks: {
              label: function(context) {
                const label = context.label || '';
                const value = context.raw || 0;
                const total = context.dataset.data.reduce((a, b) => a + b, 0);
                const percentage = Math.round((value / total) * 100);
                return `${label}: ${value} (${percentage}%)`;
              }
            }
          },
          datalabels: {
            formatter: (value, ctx) => {
              let sum = ctx.dataset.data.reduce((a, b) => a + b, 0);
              let percentage = (value * 100 / sum).toFixed(1) + "%";
              return percentage;
            },
            color: '#fff',
            font: {
              weight: 'bold',
              size: 16
            },
            anchor: 'center',
            align: 'center'
          }
        }
      },
      plugins: [ChartDataLabels]
    });
    
    // Gender Pie Chart with percentage labels
    new Chart(document.getElementById('genderPie'), {
      type: 'pie',
      data: {
        labels: ['Male', 'Female'],
        datasets: [{
          data: [<?= $stats['gender']['male'] ?>, <?= $stats['gender']['female'] ?>],
          backgroundColor: ['#007bff', '#e83e8c'],
          borderWidth: 1
        }]
      },
      options: {
        responsive: true,
        plugins: {
          legend: { 
            position: 'bottom',
            labels: {
              font: {
                size: 14
              }
            }
          },
          tooltip: {
            callbacks: {
              label: function(context) {
                const label = context.label || '';
                const value = context.raw || 0;
                const total = context.dataset.data.reduce((a, b) => a + b, 0);
                const percentage = Math.round((value / total) * 100);
                return `${label}: ${value} (${percentage}%)`;
              }
            }
          },
          datalabels: {
            formatter: (value, ctx) => {
              let sum = ctx.dataset.data.reduce((a, b) => a + b, 0);
              let percentage = (value * 100 / sum).toFixed(1) + "%";
              return percentage;
            },
            color: '#fff',
            font: {
              weight: 'bold',
              size: 16
            },
            anchor: 'center',
            align: 'center'
          }
        }
      },
      plugins: [ChartDataLabels]
    });
    
    // Age Bar Chart
    new Chart(document.getElementById('ageBarChart'), {
      type: 'bar',
      data: {
        labels: ['1-20', '21-40', '41-80', '81-120'],
        datasets: [
          {
            label: 'Cancer Detected',
            data: [
              <?= $stats['age_ranges']['1-20']['cancer'] ?>,
              <?= $stats['age_ranges']['21-40']['cancer'] ?>,
              <?= $stats['age_ranges']['41-80']['cancer'] ?>,
              <?= $stats['age_ranges']['81-120']['cancer'] ?>
            ],
            backgroundColor: '#dc3545'
          },
          {
            label: 'No Cancer Detected',
            data: [
              <?= $stats['age_ranges']['1-20']['no_cancer'] ?>,
              <?= $stats['age_ranges']['21-40']['no_cancer'] ?>,
              <?= $stats['age_ranges']['41-80']['no_cancer'] ?>,
              <?= $stats['age_ranges']['81-120']['no_cancer'] ?>
            ],
            backgroundColor: '#28a745'
          }
        ]
      },
      options: {
        responsive: true,
        plugins: {
          legend: { 
            position: 'bottom',
            labels: {
              font: {
                size: 14
              }
            }
          },
        },
        scales: {
          y: {
            beginAtZero: true,
            title: { 
              display: true, 
              text: 'Number of Cases',
              font: {
                size: 14
              }
            }
          },
          x: {
            title: { 
              display: true, 
              text: 'Age Range',
              font: {
                size: 14
              }
            }
          }
        }
      }
    });

    // Account deletion confirmation
    function confirmDelete() {
      Swal.fire({
        title: 'Delete Your Account?',
        text: "This will permanently remove your account and all associated data.",
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#d33',
        cancelButtonColor: '#6c757d',
        confirmButtonText: 'Yes, delete it!'
      }).then((result) => {
        if (result.isConfirmed) {
          window.location.href = 'delete_account.php';
        }
      });
    }
  </script>
</body>
</html>
<?php
// Close the connection at the very end
$conn->close();
?>