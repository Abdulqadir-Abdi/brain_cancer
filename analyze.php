<?php
if (session_status() === PHP_SESSION_NONE) session_start();

if (!isset($_SESSION['user_email'])) {
    echo "<script>alert('You must log in to access this page.'); window.location.href='login.html';</script>";
    exit();
}

$conn = new mysqli("localhost", "root", "", "brain_cancer_db");
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// USER SESSION
$email = $_SESSION['user_email'];
$admin_code = $_SESSION['admin_code'] ?? null;

// Fetch full user details from the database
$user_sql = "SELECT fullname, profile_image, role FROM users WHERE email = ?";
$user_stmt = $conn->prepare($user_sql);
$user_stmt->bind_param("s", $email);
$user_stmt->execute();
$user_result = $user_stmt->get_result();

if ($user_row = $user_result->fetch_assoc()) {
    $fullname = $user_row['fullname'];
    $profileImage = !empty($user_row['profile_image']) ? $user_row['profile_image'] : 'default-avatar.png';
    $role = $user_row['role'];

    // ✅ Update session to reflect fresh data
    $_SESSION['fullname'] = $fullname;
    $_SESSION['profile_image'] = $profileImage;
    $_SESSION['role'] = $role;
} else {
    $fullname = "Unknown";
    $profileImage = "default-avatar.png";
    $role = "user";
}

// FETCH ANALYSIS DATA
$cancer = $no_cancer = $female = $male = 0;
$female_cancer = $female_nocancer = $male_cancer = $male_nocancer = 0;
$age_ranges = [
    "1-20" => ["cancer" => 0, "no_cancer" => 0],
    "21-40" => ["cancer" => 0, "no_cancer" => 0],
    "41-80" => ["cancer" => 0, "no_cancer" => 0],
    "81-120" => ["cancer" => 0, "no_cancer" => 0]
];

// SQL based on role
if ($role === 'admin') {
    $sql = "SELECT prediction, sex, age FROM predictions";
    $stmt = $conn->prepare($sql);
} elseif ($role === 'small-admin' || $role === 'small-admi') {
    $managed_sql = "SELECT email FROM users WHERE managed_by = ? UNION SELECT ?";
    $managed_stmt = $conn->prepare($managed_sql);
    $managed_stmt->bind_param("ss", $admin_code, $email);
    $managed_stmt->execute();
    $managed_result = $managed_stmt->get_result();
    $emails = [];
    while ($row = $managed_result->fetch_assoc()) {
        $emails[] = $row['email'];
    }
    if (count($emails) > 0) {
        $in_placeholders = implode(',', array_fill(0, count($emails), '?'));
        $sql = "SELECT prediction, sex, age FROM predictions WHERE user_email IN ($in_placeholders)";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param(str_repeat('s', count($emails)), ...$emails);
    } else {
        $stmt = $conn->prepare("SELECT prediction, sex, age FROM predictions WHERE 0");
    }
} else {
    $sql = "SELECT prediction, sex, age FROM predictions WHERE user_email = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("s", $email);
}

$stmt->execute();
$result = $stmt->get_result();

while ($row = $result->fetch_assoc()) {
    $prediction = strtolower(trim($row['prediction']));
    $gender = strtolower(trim($row['sex']));
    $age = (int)$row['age'];

    if ($prediction === 'cancer detected') {
        $cancer++;
        ($gender === 'female') ? $female_cancer++ : $male_cancer++;
    } elseif ($prediction === 'no cancer detected') {
        $no_cancer++;
        ($gender === 'female') ? $female_nocancer++ : $male_nocancer++;
    }

    ($gender === 'female') ? $female++ : $male++;

    foreach ($age_ranges as $range => &$data) {
        [$min, $max] = explode('-', $range);
        if ($age >= $min && $age <= $max) {
            $data[$prediction === 'cancer detected' ? 'cancer' : 'no_cancer']++;
        }
    }
}
$total = $cancer + $no_cancer;
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Analyze - Brain Cancer Report</title>
  <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
  <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
  <script src="https://cdn.jsdelivr.net/npm/chartjs-plugin-datalabels@2"></script>
  <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
  <script src="https://code.jquery.com/jquery-3.5.1.slim.min.js"></script>
  <script src="https://cdn.jsdelivr.net/npm/popper.js@1.16.1/dist/umd/popper.min.js"></script>
  <script src="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/js/bootstrap.min.js"></script>

  <style>
   body {
  padding-top: 80px !important;  /* Removed top space */
  background-color: #f8f9fa;
}

.navbar {
  background: #fff;
  box-shadow: 0 4px 8px rgba(0,0,0,0.05);
  transition: all 0.3s ease;
  padding: 20px 10px;
}

.navbar.shrink {
  padding: 8px 10px;
  box-shadow: 0 4px 12px rgba(0,0,0,0.2);
}

.nav-link {
  font-weight: 500;
  margin-right: 15px;
  transition: background-color 0.3s ease;
}

/* ✅ Hover effect added */
.nav-link:hover {
  background-color: #007bff;
  color: white !important;
  border-radius: 6px;
}

/* ✅ Active link (e.g., current page) */
.nav-link.active {
  background-color: #3b0a85;
  color: white !important;
  border-radius: 6px;
  padding: 6px 12px;
}

.dropdown-toggle img {
  border-radius: 50%;
  object-fit: cover;
  border: 2px solid #ccc;
}

    .card-box { padding: 20px; background: white; border-radius: 10px; box-shadow: 0 2px 6px rgba(0,0,0,0.1); } 
    Section Heading Style (e.g., Prediction Statistics)
.section-header {
   margin-top: 100px; space below navbar
  text-align: center;
  font-weight: 600;
  font-size: 28px;
}

/* Optional: Subheading */
.section-subtext {
  text-align: center;
  color: #6c757d;
  font-size: 16px;
  margin-top: 5px;
  margin-bottom: 40px;
}

  </style>
</head>
<body>

<?php
// Ensure session is started and user role info is loaded
if (session_status() === PHP_SESSION_NONE) session_start();
$role = $_SESSION['role'] ?? 'guest';
$fullname = $_SESSION['fullname'] ?? 'User';
$profileImage = $_SESSION['profile_image'] ?? 'default-avatar.png';
$email = $_SESSION['user_email'] ?? 'unknown@example.com';
?>

<!-- Sidebar Navigation -->
<div class="d-flex">
  <nav class="bg-white shadow-sm position-fixed vh-100" style="width: 250px; z-index: 1030;">
    <div class="sidebar-header text-center py-4 border-bottom">
      <a href="home.php" class="font-weight-bold h5 text-decoration-none">Brain Cancer Detection</a>
    </div>

    <ul class="nav flex-column px-3 pt-3">
      <li class="nav-item mb-2">
        <a class="nav-link <?= basename($_SERVER['PHP_SELF']) == 'home.php' ? 'active text-primary font-weight-bold' : 'text-dark' ?>" href="home.php">
          <i class="fas fa-home mr-2"></i> Home
        </a>
      </li>

      <li class="nav-item mb-2">
        <a class="nav-link <?= basename($_SERVER['PHP_SELF']) == 'predict.php' ? 'active text-primary font-weight-bold' : 'text-dark' ?>" href="predict.php">
          <i class="fas fa-microscope mr-2"></i> Prediction
        </a>
      </li>

      <li class="nav-item mb-2">
        <a class="nav-link <?= basename($_SERVER['PHP_SELF']) == 'report.php' ? 'active text-primary font-weight-bold' : 'text-dark' ?>" href="report.php">
          <i class="fas fa-file-medical mr-2"></i> Report
        </a>
      </li>

      <?php if ($role !== 'admin' && $role !== 'small-admi' && $role !== 'small-admin'): ?>
      <li class="nav-item mb-2">
        <a class="nav-link <?= basename($_SERVER['PHP_SELF']) == 'analyze.php' ? 'active text-primary font-weight-bold' : 'text-dark' ?>" href="analyze.php">
          <i class="fas fa-chart-bar mr-2"></i> Dashboard
        </a>
      </li>
      <?php endif; ?>

      <?php if ($role === 'admin'): ?>
        <li class="nav-item mb-2">
          <a class="nav-link <?= basename($_SERVER['PHP_SELF']) == 'dashboard.php' ? 'active text-primary font-weight-bold' : 'text-dark' ?>" href="dashboard.php">
            <i class="fas fa-users-cog mr-2"></i> Dashboard
          </a>
        </li>
      <?php elseif ($role === 'small-admi' || $role === 'small-admin'): ?>
        <li class="nav-item mb-2">
          <a class="nav-link <?= basename($_SERVER['PHP_SELF']) == 'dashboard.php' ? 'active text-primary font-weight-bold' : 'text-dark' ?>" href="dashboard.php">
            <i class="fas fa-user-friends mr-2"></i> My Users
          </a>
        </li>
      <?php endif; ?>

      <!-- Profile Collapsible -->
      <li class="nav-item text-center mt-4 pt-3 border-top">
        <a class="d-block text-dark" data-toggle="collapse" href="#profileCollapse" role="button" aria-expanded="false" aria-controls="profileCollapse">
          <img src="<?= htmlspecialchars($profileImage); ?>" class="rounded-circle mb-2" width="50" height="50" alt="User">
          <div class="font-weight-bold text-capitalize"><?= htmlspecialchars($fullname); ?></div>
          <small class="text-muted"><?= htmlspecialchars($email); ?></small><br>
          <span class="text-secondary small"><?= htmlspecialchars($role); ?></span>
          <i class="fas fa-chevron-down mt-1 d-block"></i>
        </a>

        <div class="collapse px-3 mt-2" id="profileCollapse">
          <a class="nav-link small text-dark" href="update_profile.php"><i class="fas fa-user-edit mr-2"></i> Update Profile</a>
          <a class="nav-link small text-dark" href="upload_profile_image.php"><i class="fas fa-camera mr-2"></i> Upload Image</a>
          <a class="nav-link small text-danger" href="#" onclick="confirmDelete()"><i class="fas fa-user-slash mr-2"></i> Delete Account</a>
          <a class="nav-link small text-danger font-weight-bold" href="logout.php"><i class="fas fa-sign-out-alt mr-2"></i> Logout</a>
        </div>
      </li>
    </ul>
  </nav>

  <!-- Main content wrapper (adjust padding) -->
  <div class="content" style="margin-left: 250px; padding: 30px;">
    <!-- Your page content goes here -->



<!-- Delete Confirmation -->
<script>
function confirmDelete() {
  Swal.fire({
    title: 'Are you sure?',
    text: 'This will permanently delete your account.',
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

<!-- Add this section before charts -->
<!-- DR Dashboard Heading + Stat Cards -->
<div class="container mt-5">
  <h3 class="text-center mb-3">DR Dashboards</h3>
  <h6 class="text-muted text-center mb-4">
    <?= $role === 'admin' ? 'All Users (Admin)' : ''; ?>
  </h6>

  <!-- Stat Cards -->
  <div class="row">
    <div class="col-md-4 mb-3">
      <div class="card text-white bg-secondary">
        <div class="card-body">
          <h5 class="card-title">Total Cases</h5>
          <h2><?= $total ?></h2>
        </div>
      </div>
    </div>
    <div class="col-md-4 mb-3">
      <div class="card text-white bg-danger">
        <div class="card-body">
          <h5 class="card-title">Cancer Cases</h5>
          <h2><?= $cancer ?></h2>
        </div>
      </div>
    </div>
    <div class="col-md-4 mb-3">
      <div class="card text-white bg-success">
        <div class="card-body">
          <h5 class="card-title">No Cancer Cases</h5>
          <h2><?= $no_cancer ?></h2>
        </div>
      </div>
    </div>
  </div>
</div>


<!-- Analysis Stats -->
<div class="container mt-5">
  <h3 class="text-center mb-3">Prediction Statistics</h3>
  <h6 class="text-muted text-center mb-4">
    Viewing <?= $role === 'admin' ? 'All Users (Admin)' : 'Your Personal Report'; ?>
  </h6>

  <div class="row">
    <div class="col-md-6 mb-4">
      <div class="card-box">
        <h5>Total Cases: <?= $total ?></h5>
        <p><strong>Cancer Detected:</strong> <?= $cancer ?></p>
        <p><strong>No Cancer Detected:</strong> <?= $no_cancer ?></p>
        <canvas id="cancerPie"></canvas>
      </div>
    </div>
    <div class="col-md-6 mb-4">
      <div class="card-box">
        <h5>Gender Distribution</h5>
        <p><strong>Male:</strong> <?= $male ?> (Cancer: <?= $male_cancer ?>, No Cancer: <?= $male_nocancer ?>)</p>
        <p><strong>Female:</strong> <?= $female ?> (Cancer: <?= $female_cancer ?>, No Cancer: <?= $female_nocancer ?>)</p>
        <canvas id="genderPie"></canvas>
      </div>
    </div>
    <div class="col-md-12 mb-4">
      <div class="card-box">
        <h5>Age Range Distribution</h5>
        <canvas id="ageBarChart"></canvas>
      </div>
    </div>
  </div>
</div>

<script>
const cancerData = [<?= $cancer ?>, <?= $no_cancer ?>];
const genderData = [<?= $male ?>, <?= $female ?>];

new Chart(document.getElementById('cancerPie'), {
  type: 'pie',
  data: {
    labels: ['Cancer Detected', 'No Cancer Detected'],
    datasets: [{ data: cancerData, backgroundColor: ['#dc3545', '#28a745'] }]
  },
  options: { plugins: { legend: { position: 'top' } } },
  plugins: [ChartDataLabels]
});

new Chart(document.getElementById('genderPie'), {
  type: 'pie',
  data: {
    labels: ['Male', 'Female'],
    datasets: [{ data: genderData, backgroundColor: ['#007bff', '#e83e8c'] }]
  },
  options: { plugins: { legend: { position: 'top' } } },
  plugins: [ChartDataLabels]
});

new Chart(document.getElementById('ageBarChart'), {
  type: 'bar',
  data: {
    labels: ['1-20', '21-40', '41-80', '81-120'],
    datasets: [
      {
        label: 'Cancer Detected',
        data: [<?= $age_ranges["1-20"]["cancer"] ?>, <?= $age_ranges["21-40"]["cancer"] ?>, <?= $age_ranges["41-80"]["cancer"] ?>, <?= $age_ranges["81-120"]["cancer"] ?>],
        backgroundColor: '#dc3545'
      },
      {
        label: 'No Cancer Detected',
        data: [<?= $age_ranges["1-20"]["no_cancer"] ?>, <?= $age_ranges["21-40"]["no_cancer"] ?>, <?= $age_ranges["41-80"]["no_cancer"] ?>, <?= $age_ranges["81-120"]["no_cancer"] ?>],
        backgroundColor: '#28a745'
      }
    ]
  },
  options: { responsive: true, plugins: { legend: { position: 'top' } }, scales: { y: { beginAtZero: true } } }
});
</script>

</body>
</html>
