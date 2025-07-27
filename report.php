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
$user_email = $_SESSION['user_email'];
$admin_code = $_SESSION['admin_code'] ?? null;

// Get profile data
$sql = "SELECT fullname, profile_image FROM users WHERE email = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("s", $email);
$stmt->execute();
$user_result = $stmt->get_result();
if ($user_result->num_rows > 0) {
    $row = $user_result->fetch_assoc();
    $fullname = $row['fullname'];
    $profileImage = $row['profile_image'];
} else {
    $fullname = "User";
    $profileImage = "default-avatar.png";
}

$filter_sql = [];
if ($role === 'admin') {
    // See all
} elseif ($role === 'small-admin' || $role === 'small-admi') {
    $sub_stmt = $conn->prepare("SELECT email FROM users WHERE managed_by = ? UNION SELECT ?");
    $sub_stmt->bind_param("ss", $admin_code, $user_email);
    $sub_stmt->execute();
    $sub_result = $sub_stmt->get_result();
    $emails = [];
    while ($row = $sub_result->fetch_assoc()) {
        $emails[] = "'" . $conn->real_escape_string($row['email']) . "'";
    }
    $filter_sql[] = !empty($emails) ? "user_email IN (" . implode(",", $emails) . ")" : "1 = 0";
} else {
    $filter_sql[] = "user_email = '" . $conn->real_escape_string($user_email) . "'";
}

if (!empty($_GET['search'])) {
    $search = $conn->real_escape_string($_GET['search']);
    $filter_sql[] = "(fullname LIKE '%$search%' OR phone LIKE '%$search%')";
}
if (!empty($_GET['gender'])) {
    $gender = $conn->real_escape_string($_GET['gender']);
    $filter_sql[] = "sex = '$gender'";
}
if (!empty($_GET['start_date']) && !empty($_GET['end_date'])) {
    $start = $conn->real_escape_string($_GET['start_date']);
    $end = $conn->real_escape_string($_GET['end_date']);
    $filter_sql[] = "DATE(created_at) BETWEEN '$start' AND '$end'";
}

$search_query = (!empty($filter_sql)) ? "WHERE " . implode(" AND ", $filter_sql) : "";
$result = $conn->query("SELECT * FROM predictions $search_query ORDER BY created_at DESC");

// Delete reports (allowed for all roles)
if (isset($_GET['delete'])) {
    $id = intval($_GET['delete']);
    $conn->query("DELETE FROM predictions WHERE id = $id");
    header("Location: report.php");
    exit();
}

// Export Excel
if (isset($_GET['export']) && $_GET['export'] == 'excel') {
    header("Content-Type: application/vnd.ms-excel");
    header("Content-Disposition: attachment; filename=prediction_report.xls");
    echo "Full Name\tAge\tPhone\tGender\tPrediction\tSubmitted At\n";

    $export_result = $conn->query("SELECT * FROM predictions $search_query ORDER BY created_at DESC");
    while ($row = $export_result->fetch_assoc()) {
        echo "{$row['fullname']}\t{$row['age']}\t{$row['phone']}\t{$row['sex']}\t{$row['prediction']}\t{$row['created_at']}\n";
    }
    exit();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Prediction Report</title>
  <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
  <script src="https://code.jquery.com/jquery-3.5.1.slim.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@4.5.2/dist/js/bootstrap.bundle.min.js"></script>
<!-- jsPDF -->
<script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf/2.5.1/jspdf.umd.min.js"></script>
<!-- jsPDF AutoTable plugin -->
<script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf-autotable/3.5.23/jspdf.plugin.autotable.min.js"></script>

  <style>
    body, html {
      background: linear-gradient(135deg, #f8f9fa 60%, #e6e6fa 100%);
      min-height: 100vh;
      font-family: 'Poppins', Arial, sans-serif;
      color: #333;
    }
    .container, .content {
      max-width: 1200px;
      margin: 0 auto;
      padding-top: 40px;
    }
    h2, h3, h4 {
      font-family: 'Poppins', Arial, sans-serif;
      font-weight: 800;
      color: #3b0a85;
      letter-spacing: 1px;
    }
    .filter-card {
      background: #fff;
      padding: 2rem 1.5rem;
      border-radius: 1.5rem;
      box-shadow: 0 6px 20px rgba(59,10,133,0.08);
      margin-bottom: 2.5rem;
    }
    .filter-card .form-control, .filter-card .btn {
      min-height: 45px;
      font-size: 1.1rem;
      border-radius: 1rem;
      font-family: 'Poppins', Arial, sans-serif;
    }
    .filter-card input[type="text"], .filter-card select, .filter-card input[type="date"] {
      max-width: 200px;
      margin-right: 0.5rem;
    }
    .table-responsive {
      border-radius: 1.2rem;
      box-shadow: 0 2px 12px rgba(59,10,133,0.07);
      background: #fff;
      padding: 1.2rem 0.5rem;
    }
    table.table {
      background: #fff;
      border-radius: 1.2rem;
      overflow: hidden;
      font-family: 'Poppins', Arial, sans-serif;
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
    .btn-warning, .btn-danger, .btn-success, .btn-secondary {
      border-radius: 1.2rem;
      font-weight: 600;
      padding: 0.4rem 1.1rem;
      font-size: 1rem;
      margin-right: 0.2rem;
      font-family: 'Poppins', Arial, sans-serif;
    }
    .btn-warning i, .btn-danger i, .btn-success i, .btn-secondary i {
      margin-right: 0.3rem;
    }
    @media (max-width: 991px) {
      .container, .content { padding-top: 20px; }
      .filter-card { padding: 1.2rem 0.5rem; }
      table.table th, table.table td { font-size: 0.95rem; padding: 0.5rem 0.3rem; }
    }
    @media (max-width: 767px) {
      .container, .content { padding-top: 10px; }
      .filter-card, .table-responsive { border-radius: 1rem; }
      h2, h3, h4 { font-size: 1.1rem; }
    }
  </style>
  <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;700&display=swap" rel="stylesheet">
</head>
<body>

<!-- Sidebar Navbar (Design-Only Change from Top Navbar) -->
<div class="d-flex">
  <nav id="sidebar" class="bg-white shadow-sm vh-100 position-fixed" style="width: 250px; top: 0; left: 0; z-index: 1030;">
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

      <?php if ($role === 'admin'): ?>
        <li class="nav-item mb-2">
          <a class="nav-link <?= basename($_SERVER['PHP_SELF']) == 'dashboard.php' ? 'active text-primary font-weight-bold' : 'text-dark' ?>" href="dashboard.php">
            <i class="fas fa-users-cog mr-2"></i> Dashboard
          </a>
        </li>
      <?php elseif ($role === 'small-admin' || $role === 'small-admi'): ?>
        <li class="nav-item mb-2">
          <a class="nav-link <?= basename($_SERVER['PHP_SELF']) == 'dashboard.php' ? 'active text-primary font-weight-bold' : 'text-dark' ?>" href="dashboard.php">
            <i class="fas fa-user-friends mr-2"></i> My Users
          </a>
        </li>
      <?php endif; ?>
     

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

      

      <!-- Profile Dropdown Mimic -->
      <li class="nav-item mt-4 text-center border-top pt-4">
        <a class="nav-link collapsed text-dark" data-toggle="collapse" href="#profileDropdown" role="button" aria-expanded="false" aria-controls="profileDropdown">
          <?php if (!empty($profileImage) && $profileImage !== 'default-avatar.png'): ?>
            <img src="<?= htmlspecialchars($profileImage); ?>" class="rounded-circle mb-2" width="40" height="40" alt="User">
          <?php else: ?>
            <i class="fas fa-user-circle fa-2x mb-2 text-muted"></i>
          <?php endif; ?>
          <div><?= htmlspecialchars($fullname); ?></div>
        </a>

        <div class="collapse mt-2" id="profileDropdown">
          <small class="text-muted"><?= htmlspecialchars($email); ?></small><br>
          <small class="text-muted"><?= htmlspecialchars($role); ?></small><br><br>

          <a class="nav-link small text-dark" href="update_profile.php"><i class="fas fa-user-edit mr-2"></i>Update Profile</a>
          <a class="nav-link small text-dark" href="upload_profile_image.php"><i class="fas fa-camera mr-2"></i>Upload Image</a>
          <a class="nav-link small text-danger" href="#" onclick="confirmDelete()"><i class="fas fa-user-slash mr-2"></i>Delete Account</a>
          <a class="nav-link small text-danger font-weight-bold" href="logout.php"><i class="fas fa-sign-out-alt mr-2"></i>Logout</a>
        </div>
      </li>
    </ul>
  </nav>

  <!-- Content wrapper -->
  <div class="content" style="margin-left: 350px;">

    <!-- Page-specific content continues here -->




<div class="container mt-5">
  <h2 class="text-center mb-4">Brain Cancer Prediction Report</h2>

  <div class="filter-card">
    <form method="get" class="form-row justify-content-center align-items-end">
      <div class="form-group mx-2">
        <label for="search">Search</label>
        <input type="text" name="search" class="form-control" placeholder="Name or Phone" value="<?= isset($_GET['search']) ? htmlspecialchars($_GET['search']) : '' ?>">
      </div>
      <div class="form-group mx-2">
        <label for="gender">Gender</label>
        <select name="gender" class="form-control">
          <option value="">All</option>
          <option value="male" <?= (isset($_GET['gender']) && $_GET['gender'] == 'male') ? 'selected' : '' ?>>Male</option>
          <option value="female" <?= (isset($_GET['gender']) && $_GET['gender'] == 'female') ? 'selected' : '' ?>>Female</option>
        </select>
      </div>
      <div class="form-group mx-2">
        <label for="start_date">From</label>
        <input type="date" name="start_date" class="form-control" value="<?= isset($_GET['start_date']) ? $_GET['start_date'] : '' ?>">
      </div>
      <div class="form-group mx-2">
        <label for="end_date">To</label>
        <input type="date" name="end_date" class="form-control" value="<?= isset($_GET['end_date']) ? $_GET['end_date'] : '' ?>">
      </div>
      <div class="form-group mx-2">
        <label>&nbsp;</label><br>
        <a href="report.php" class="btn btn-secondary">Clear</a>
      </div>
      <div class="form-group mx-2">
        <label>&nbsp;</label><br>
        <a href="?export=excel" class="btn btn-success">Export Excel</a>
      </div>
      <div class="form-group mx-2">
        <label>&nbsp;</label><br>
        <button onclick="exportToPDF()" type="button" class="btn btn-danger">Export PDF</button>
      </div>
    </form>
  </div>

  <div class="table-responsive">
    <table class="table table-bordered" id="reportTable">
      <thead class="thead-dark">
        <tr>
          <th>Full Name</th><th>Age</th><th>Phone</th><th>Gender</th><th>Prediction</th><th>Submitted At</th><th>Actions</th>
        </tr>
      </thead>
      <tbody>
        <?php if ($result->num_rows > 0): ?>
          <?php while($row = $result->fetch_assoc()): ?>
          <tr>
            <td><?= htmlspecialchars($row['fullname']) ?></td>
            <td><?= htmlspecialchars($row['age']) ?></td>
            <td><?= htmlspecialchars($row['phone']) ?></td>
            <td><?= htmlspecialchars($row['sex']) ?></td>
            <td><?php
        $json = json_decode($row['prediction'], true);
        echo isset($json['diagnosis']) ? htmlspecialchars($json['diagnosis']) : htmlspecialchars($row['prediction']);
    ?></td>
            <td><?= htmlspecialchars($row['created_at']) ?></td>
            <td>
              <a href="update.php?id=<?= $row['id'] ?>" class="btn btn-sm btn-warning">Edit</a>
              <a href="report.php?delete=<?= $row['id'] ?>" class="btn btn-sm btn-danger" onclick="return confirm('Delete this record?');">Delete</a>
            </td>
          </tr>
          <?php endwhile; else: ?>
        <tr><td colspan="7" class="text-center">No records found.</td></tr>
        <?php endif; ?>
      </tbody>
    </table>
  </div>
</div>

<script>
function exportToPDF() {
  const { jsPDF } = window.jspdf;
  const doc = new jsPDF();
  doc.text("Brain Cancer Prediction Report", 10, 10);
  const headers = [["Full Name", "Age", "Phone", "Gender", "Prediction", "Submitted At"]];
  const rows = [];
  document.querySelectorAll("#reportTable tbody tr").forEach(tr => {
    const row = [];
    tr.querySelectorAll("td").forEach((td, i) => {
      if (i < 6) row.push(td.innerText);
    });
    rows.push(row);
  });
  doc.autoTable({ head: headers, body: rows, startY: 20 });
  doc.save("report.pdf");
}

document.addEventListener("DOMContentLoaded", function () {
  const genderSelect = document.querySelector("select[name='gender']");
  const startDate = document.querySelector("input[name='start_date']");
  const endDate = document.querySelector("input[name='end_date']");
  genderSelect.addEventListener("change", function () { this.form.submit(); });
  startDate.addEventListener("change", function () { if (endDate.value) this.form.submit(); });
  endDate.addEventListener("change", function () { if (startDate.value) this.form.submit(); });
});
</script>

<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script>
function confirmAccountDelete() {
  Swal.fire({
    title: 'Are you sure?',
    text: "This will permanently delete your account.",
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
  return false; // Prevent default anchor click
}
</script>


</body>
</html>
