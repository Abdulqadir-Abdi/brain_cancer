<?php
session_start();
if (!isset($_SESSION['user_email'])) {
    header("Location: login.html");
    exit();
}

$conn = new mysqli("localhost", "root", "", "brain_cancer_db");
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

$email = $_SESSION['user_email'];
$role = $_SESSION['role'] ?? 'user';
$admin_code = $_SESSION['admin_code'] ?? null;

$stmt = $conn->prepare("SELECT fullname, profile_image FROM users WHERE email = ?");
$stmt->bind_param("s", $email);
$stmt->execute();
$res = $stmt->get_result();
$profile = $res->fetch_assoc();
$fullname = $profile['fullname'] ?? 'Unknown';
$profile_image = $profile['profile_image'] ?? 'default-avatar.png';

$totalUsers = $conn->query("SELECT COUNT(*) AS total FROM users")->fetch_assoc()['total'];
$totalSmallAdmins = $conn->query("SELECT COUNT(*) AS total FROM users WHERE role = 'small-admin' OR role = 'small-admi'")->fetch_assoc()['total'];
$yourUsers = 0;
if ($role === 'small-admin' || $role === 'small-admi') {
    $stmt = $conn->prepare("SELECT COUNT(*) AS total FROM users WHERE managed_by = ?");
    $stmt->bind_param("s", $admin_code);
    $stmt->execute();
    $yourUsers = $stmt->get_result()->fetch_assoc()['total'];
}

$total = 0;
$cancer = 0;
$no_cancer = 0;
$male = 0;
$female = 0;
$male_cancer = 0;
$male_nocancer = 0;
$female_cancer = 0;
$female_nocancer = 0;

$totalCases = 0;
$totalCancer = 0;
$totalNoCancer = 0;

$age_ranges = [
    "1-20" => ["cancer" => 0, "no_cancer" => 0],
    "21-40" => ["cancer" => 0, "no_cancer" => 0],
    "41-80" => ["cancer" => 0, "no_cancer" => 0],
    "81-120" => ["cancer" => 0, "no_cancer" => 0]
];

if ($role === 'admin') {
    $sql = "SELECT prediction, sex, age FROM predictions";
    $stmt = $conn->prepare($sql);
} elseif ($role === 'small-admin' || $role === 'small-admi') {
    $stmt = $conn->prepare("SELECT email FROM users WHERE managed_by = ?");
    $stmt->bind_param("s", $admin_code);
    $stmt->execute();
    $result = $stmt->get_result();
    $emails = [];
    while ($row = $result->fetch_assoc()) {
        $emails[] = $row['email'];
    }
    if (!empty($emails)) {
        $in_placeholders = implode(',', array_fill(0, count($emails), '?'));
        $sql = "SELECT prediction, sex, age FROM predictions WHERE user_email IN ($in_placeholders)";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param(str_repeat('s', count($emails)), ...$emails);
    } else {
        $stmt = $conn->prepare("SELECT prediction, sex, age FROM predictions WHERE 1=0");
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
$totalCases = $total;
$totalCancer = $cancer;
$totalNoCancer = $no_cancer;
?>


<!DOCTYPE html>
<html>
<head>
  <title>Dashboard</title>
  <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
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
    background: linear-gradient(135deg, #f8f9fa 60%, #e6e6fa 100%);
    min-height: 100vh;
    font-family: 'Poppins', sans-serif;
    color: #333;
  }
  .container, .content {
    max-width: 1200px;
    margin: 0 auto;
    padding-top: 40px;
  }
  h2, h3, h4 {
    font-weight: 800;
    color: #3b0a85;
    letter-spacing: 1px;
  }
  .card-summary {
    border-radius: 1.5rem;
    box-shadow: 0 6px 20px rgba(59,10,133,0.08);
    background: #fff;
    transition: transform 0.3s;
    margin-bottom: 1.5rem;
  }
  .card-summary:hover {
    transform: translateY(-4px) scale(1.03);
  }
  .card-summary .card-body h5 {
    font-size: 1.1rem;
    font-weight: 600;
    color: #666;
  }
  .card-summary .card-body h2 {
    font-size: 2rem;
    font-weight: bold;
    color: #3b0a85;
  }
  .card-box {
    background: #fff;
    border-radius: 1.5rem;
    padding: 2rem 1.5rem;
    box-shadow: 0 4px 16px rgba(59,10,133,0.07);
    margin-bottom: 2rem;
  }
  table.table {
    background: #fff;
    border-radius: 1.2rem;
    box-shadow: 0 2px 12px rgba(59,10,133,0.07);
    overflow: hidden;
  }
  table.table th, table.table td {
    vertical-align: middle;
    font-size: 1.05rem;
    padding: 0.85rem 0.7rem;
  }
  table.table thead th {
    background: #3b0a85;
    color: #fff;
    border: none;
    font-weight: 700;
    letter-spacing: 0.5px;
  }
  .btn-warning, .btn-danger {
    border-radius: 1.2rem;
    font-weight: 600;
    padding: 0.4rem 1.1rem;
    font-size: 1rem;
  }
  .btn-warning i, .btn-danger i {
    margin-right: 0.3rem;
  }
  @media (max-width: 991px) {
    .container, .content { padding-top: 20px; }
    .card-box { padding: 1.2rem 0.5rem; }
    .card-summary .card-body h2 { font-size: 1.3rem; }
    table.table th, table.table td { font-size: 0.95rem; padding: 0.5rem 0.3rem; }
  }
  @media (max-width: 767px) {
    .container, .content { padding-top: 10px; }
    .card-summary, .card-box { border-radius: 1rem; }
    .card-summary .card-body h2 { font-size: 1.1rem; }
    h2, h3, h4 { font-size: 1.1rem; }
  }
  </style>

<!-- Google Fonts for Modern Typography -->
<link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;600&display=swap" rel="stylesheet">

</head>
<body>

<!-- ✅ Navigation -->
 <!-- ✅ Navigation -->
<div class="d-flex">
  <nav id="sidebar" class="bg-white shadow-sm position-fixed vh-100" style="width: 250px; top: 0; left: 0; z-index: 1030; ">
    <div id="sidebar" class="bg-white shadow-sm vh-100 position-fixed d-flex flex-column" style="width: 250px; top: 0; left: 0; z-index: 1030;">
    <!-- Sidebar Header -->
  <div class="sidebar-header text-center py-4 border-bottom" style="margin-top: 0;">
    <a href="home.php" class="text-decoration-none font-weight-bold h5 d-block text-primary" style="font-family: 'Segoe UI', sans-serif;">
      <i class="fas fa-brain mr-2"></i>Brain Cancer Detection
    </a>
  </div>

    <ul class="nav flex-column px-3 pt-3">
    <?php if ($role !== 'admin' && $role !== 'small-admin' && $role !== 'small-admi'): ?>
      <li class="nav-item mb-2">
        <a class="nav-link <?= basename($_SERVER['PHP_SELF']) == 'analyze.php' ? 'active text-primary font-weight-bold' : 'text-dark' ?>" href="analyze.php">
          <i class="fas fa-chart-bar mr-2"></i> Dashboard
        </a>
      </li>
      <?php endif; ?>

      <li class="nav-item mb-2">
        <a class="nav-link <?= basename($_SERVER['PHP_SELF']) == 'dashboard.php' ? 'active text-primary font-weight-bold' : 'text-dark' ?>" href="dashboard.php">
          <i class="fas fa-users-cog mr-2"></i> My Users
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

      

      <!-- Profile Dropdown (Styled Section) -->
      <!-- Fixed Profile Collapse Section -->
<li class="nav-item text-center mt-4 pt-3 border-top">
  <a class="d-inline-block" data-toggle="collapse" href="#profileCollapse" role="button" aria-expanded="false" aria-controls="profileCollapse">
    <img src="<?= htmlspecialchars($profile_image); ?>" class="rounded-circle mb-2" width="50" height="50" alt="User">
    <div class="font-weight-bold text-capitalize"><?= htmlspecialchars($fullname); ?></div>
    <small class="text-muted"><?= htmlspecialchars($email); ?></small><br>
    <span class="text-secondary small"><?= htmlspecialchars($role); ?></span>
    <i class="fas fa-chevron-down d-block mt-1"></i>
  </a>

  <div class="collapse mt-2 px-3" id="profileCollapse">
    <a class="nav-link small text-dark" href="update_profile.php"><i class="fas fa-user-edit mr-2"></i> Update Profile</a>
    <a class="nav-link small text-dark" href="upload_profile_image.php"><i class="fas fa-camera mr-2"></i> Upload Image</a>
    <a class="nav-link small text-danger" href="#" onclick="confirmDelete()"><i class="fas fa-user-slash mr-2"></i> Delete Account</a>
    <a class="nav-link small text-danger font-weight-bold" href="logout.php"><i class="fas fa-sign-out-alt mr-2"></i> Logout</a>
  </div>
</li>

    </ul>
  </nav>

  <!-- Main Content -->
  <div class="content" style="margin-left: 350px;">
    <!-- Your page content goes here -->


<!-- ... existing nav code ... -->

<div class="container mt-5 pt-5">
  <h2 class="text-center mb-4">
  <?php if ($role === 'admin'): ?>
    Hospital Dashboard
  <?php elseif ($role === 'small-admin' || $role === 'small-admi'): ?>
     Hospital Dashboard
  <?php else: ?>
    Welcome, <?= ucfirst(htmlspecialchars($role)) ?>
  <?php endif; ?>
</h2>


  <!-- Cards -->
  <div class="row mb-4">
    <?php if ($role === 'admin'): ?>
      <div class="col-md-4 mb-3">
        <div class="card bg-primary text-white card-summary">
          <div class="card-body">
            <h5>Total Users</h5><h2><?= $totalUsers ?></h2>
          </div>
        </div>
      </div>
      <div class="col-md-4 mb-3">
        <div class="card bg-info text-white card-summary">
          <div class="card-body">
            <h5>Small Admins</h5><h2><?= $totalSmallAdmins ?></h2>
          </div>
        </div>
      </div>
      <div class="col-md-3 mb-3">
    <div class="card bg-secondary text-white card-summary">
      <div class="card-body">
        <h5>Total Cases</h5><h2><?= $totalCases ?></h2>
      </div>
    </div>
  </div>
  <div class="col-md-3 mb-3">
    <div class="card bg-danger text-white card-summary">
      <div class="card-body">
        <h5>Total Cancer Cases</h5><h2><?= $totalCancer ?></h2>
      </div>
    </div>
  </div>
  <div class="col-md-3 mb-3">
    <div class="card bg-dark text-white card-summary">
      <div class="card-body">
        <h5>Total No Cancer Cases</h5><h2><?= $totalNoCancer ?></h2>
      </div>
    </div>
  </div>
    <?php endif; ?>

    <?php if ($role === 'small-admin' || $role === 'small-admi'): ?>
      <div class="col-md-3 mb-3">
        <div class="card bg-success text-white card-summary">
          <div class="card-body">
            <h5>Your Users</h5><h2><?= $yourUsers ?></h2>
          </div>
        </div>
      </div>
      <div class="col-md-3 mb-3">
        <div class="card bg-secondary text-white card-summary">
          <div class="card-body">
            <h5>Total Cases</h5><h2><?= $totalCases ?></h2>
          </div>
        </div>
      </div>
      <div class="col-md-3 mb-3">
        <div class="card bg-danger text-white card-summary">
          <div class="card-body">
            <h5>Total Cancer Cases</h5><h2><?= $totalCancer ?></h2>
          </div>
        </div>
      </div>
      <div class="col-md-3 mb-3">
        <div class="card bg-dark text-white card-summary">
          <div class="card-body">
            <h5>Total No Cancer</h5><h2><?= $totalNoCancer ?></h2>
          </div>
        </div>
      </div>
    <?php endif; ?>
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
</script>


  

  <!-- ✅ Managed Users Table -->
  <?php if ($role === 'small-admin' || $role === 'small-admi'): ?>
    <h4>Your Managed Users</h4>
    <table class="table table-bordered bg-white shadow-sm">
      <thead><tr><th>#</th><th>Name</th><th>Email</th><th>Role</th><th>Actions</th></tr></thead>
      <tbody>
        <?php
        $i = 1;
        $stmt = $conn->prepare("SELECT id, fullname, email, role FROM users WHERE managed_by = ?");
        $stmt->bind_param("s", $admin_code);
        $stmt->execute();
        $res = $stmt->get_result();
        while ($row = $res->fetch_assoc()):
        ?>
        <tr>
          <td><?= $i++ ?></td>
          <td><?= htmlspecialchars($row['fullname']) ?></td>
          <td><?= htmlspecialchars($row['email']) ?></td>
          <td><?= htmlspecialchars($row['role']) ?></td>
          <td>
            <a href="edit_user.php?id=<?= $row['id'] ?>" class="btn btn-sm btn-warning"><i class="fas fa-edit"></i> Edit</a>
            <a href="delete_user.php?id=<?= $row['id'] ?>" onclick="return confirm('Are you sure?')" class="btn btn-sm btn-danger"><i class="fas fa-trash"></i> Delete</a>
          </td>
        </tr>
        <?php endwhile; ?>
      </tbody>
    </table>
  <?php endif; ?>

  <!-- ✅ Admin View: List all small-admins and how many users they manage -->
  <?php if ($role === 'admin'): ?>
    <h4>All Small Admins</h4>
    <table class="table table-bordered bg-white shadow-sm">
      <thead><tr><th>#</th><th>Full Name</th><th>Email</th><th>Admin Code</th><th>Managed Users</th></tr></thead>
      <tbody>
        <?php
        $result = $conn->query("SELECT id, fullname, email, admin_code FROM users WHERE role = 'small-admin' OR role = 'small-admi'");
        $i = 1;
        while ($row = $result->fetch_assoc()):
          $adminCode = $row['admin_code'];
          $stmt = $conn->prepare("SELECT COUNT(*) AS total FROM users WHERE managed_by = ?");
          $stmt->bind_param("s", $adminCode);
          $stmt->execute();
          $userCount = $stmt->get_result()->fetch_assoc()['total'] ?? 0;
        ?>
        <tr>
          <td><?= $i++ ?></td>
          <td><?= htmlspecialchars($row['fullname']) ?></td>
          <td><?= htmlspecialchars($row['email']) ?></td>
          <td><?= htmlspecialchars($adminCode) ?></td>
          <td><?= $userCount ?></td>
        </tr>
        <?php endwhile; ?>
      </tbody>
    </table>
  <?php endif; ?>



<script src="https://code.jquery.com/jquery-3.5.1.slim.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@4.5.2/dist/js/bootstrap.bundle.min.js"></script>
<!-- ✅ REQUIRED JS for Bootstrap dropdown to work -->
<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/@popperjs/core@2.11.6/dist/umd/popper.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@4.6.2/dist/js/bootstrap.min.js"></script>

</body>
</html>
