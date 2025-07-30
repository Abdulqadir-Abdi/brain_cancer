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

// Initialize filter conditions
$filter_sql = [];
$params = [];

// Role-based filtering
if ($role === 'admin') {
    // Admin can see all records
} elseif ($role === 'small-admin' || $role === 'small-admi') {
    $sub_stmt = $conn->prepare("SELECT email FROM users WHERE managed_by = ? UNION SELECT ?");
    $sub_stmt->bind_param("ss", $admin_code, $user_email);
    $sub_stmt->execute();
    $sub_result = $sub_stmt->get_result();
    $emails = [];
    while ($row = $sub_result->fetch_assoc()) {
        $emails[] = $conn->real_escape_string($row['email']);
    }
    if (!empty($emails)) {
        $placeholders = implode("','", $emails);
        $filter_sql[] = "user_email IN ('$placeholders')";
    } else {
        $filter_sql[] = "1 = 0"; // No results if no emails found
    }
} else {
    $filter_sql[] = "user_email = '" . $conn->real_escape_string($user_email) . "'";
}

// Search filters
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

// Build the query
$search_query = !empty($filter_sql) ? "WHERE " . implode(" AND ", $filter_sql) : "";
$query = "SELECT * FROM predictions $search_query ORDER BY created_at DESC";
$result = $conn->query($query);

// Handle delete action
if (isset($_GET['delete'])) {
    $id = intval($_GET['delete']);
    // Verify user has permission to delete this record
    $check_sql = "SELECT user_email FROM predictions WHERE id = $id";
    $check_result = $conn->query($check_sql);
    
    if ($check_result->num_rows > 0) {
        $prediction = $check_result->fetch_assoc();
        $can_delete = false;
        
        if ($role === 'admin') {
            $can_delete = true;
        } elseif ($role === 'small-admin' || $role === 'small-admi') {
            $check_user = $conn->query("SELECT email FROM users WHERE email = '{$prediction['user_email']}' AND managed_by = '$admin_code'");
            $can_delete = ($check_user->num_rows > 0) || ($prediction['user_email'] === $user_email);
        } else {
            $can_delete = ($prediction['user_email'] === $user_email);
        }
        
        if ($can_delete) {
            $conn->query("DELETE FROM predictions WHERE id = $id");
            header("Location: report.php");
            exit();
        }
    }
}

// Handle Excel export
if (isset($_GET['export']) && $_GET['export'] == 'excel') {
    header("Content-Type: application/vnd.ms-excel");
    header("Content-Disposition: attachment; filename=prediction_report.xls");
    echo "Full Name\tAge\tPhone\tGender\tPrediction\tSubmitted At\n";

    $export_result = $conn->query($query);
    while ($row = $export_result->fetch_assoc()) {
        $json = json_decode($row['prediction'], true);
        $diagnosis = isset($json['diagnosis']) ? $json['diagnosis'] : $row['prediction'];
        echo "{$row['fullname']}\t{$row['age']}\t{$row['phone']}\t{$row['sex']}\t$diagnosis\t{$row['created_at']}\n";
    }
    exit();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Prediction Report - Brain Cancer Detection</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
  <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700&display=swap" rel="stylesheet">
  <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
  <script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf/2.5.1/jspdf.umd.min.js"></script>
  <script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf-autotable/3.5.23/jspdf.plugin.autotable.min.js"></script>
  <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

  <style>
    :root {
      --primary: #3b0a85;
      --primary-light: #5e35b1;
      --secondary: #6c757d;
      --success: #28a745;
      --danger: #dc3545;
      --info: #17a2b8;
      --light: #f8f9fa;
      --dark: #343a40;
      --radius-sm: 0.25rem;
      --radius: 0.5rem;
      --radius-lg: 1rem;
      --shadow-sm: 0 0.125rem 0.25rem rgba(0, 0, 0, 0.075);
      --shadow: 0 0.5rem 1rem rgba(0, 0, 0, 0.15);
    }
    
    body {
      background: linear-gradient(135deg, #f8f9fa 0%, #eef2f5 100%);
      min-height: 100vh;
      font-family: 'Poppins', sans-serif;
      color: var(--dark);
    }
    
    /* Sidebar styles */
    .sidebar {
      width: 280px;
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
    
    /* Main content area */
    .main-content {
      margin-left: 280px;
      padding: 2rem;
      background-color: transparent;
    }
    
    /* Report container */
    .report-container {
      background: white;
      border-radius: var(--radius-lg);
      box-shadow: var(--shadow);
      overflow: hidden;
    }
    
    .report-header {
      background: linear-gradient(90deg, var(--primary), var(--primary-light));
      color: white;
      padding: 1.5rem;
      text-align: center;
    }
    
    .report-header h2 {
      font-weight: 700;
      margin-bottom: 0;
    }
    
    /* Filter section */
    .filter-section {
      padding: 1.5rem;
      background: white;
      border-bottom: 1px solid rgba(0,0,0,0.05);
    }
    
    .filter-label {
      font-weight: 600;
      color: var(--dark);
      margin-bottom: 0.5rem;
    }
    
    .form-control, .form-select {
      padding: 0.75rem 1rem;
      border-radius: var(--radius);
      border: 1px solid #ced4da;
      transition: all 0.3s;
    }
    
    .form-control:focus, .form-select:focus {
      border-color: var(--primary-light);
      box-shadow: 0 0 0 0.25rem rgba(59, 10, 133, 0.25);
    }
    
    /* Table styling */
    .table-responsive {
      padding: 1.5rem;
    }
    
    .table {
      border-radius: var(--radius);
      overflow: hidden;
    }
    
    .table thead th {
      background: linear-gradient(90deg, var(--primary), var(--primary-light));
      color: white;
      font-weight: 600;
      border: none;
    }
    
    .table tbody tr {
      transition: all 0.2s;
    }
    
    .table tbody tr:hover {
      background-color: rgba(59, 10, 133, 0.05);
    }
    
    /* Badge styling */
    .badge-cancer {
      background-color: rgba(220, 53, 69, 0.1);
      color: var(--danger);
      font-weight: 600;
      padding: 0.5rem 0.75rem;
      border-radius: var(--radius);
    }
    
    .badge-no-cancer {
      background-color: rgba(40, 167, 69, 0.1);
      color: var(--success);
      font-weight: 600;
      padding: 0.5rem 0.75rem;
      border-radius: var(--radius);
    }
    
    /* Button styling */
    .btn-action {
      border-radius: var(--radius);
      font-weight: 600;
      padding: 0.5rem 1rem;
      transition: all 0.3s;
    }
    
    .btn-edit {
      background-color: rgba(255, 193, 7, 0.1);
      color: #ffc107;
      border: none;
    }
    
    .btn-edit:hover {
      background-color: rgba(255, 193, 7, 0.2);
      color: #ffc107;
    }
    
    .btn-delete {
      background-color: rgba(220, 53, 69, 0.1);
      color: var(--danger);
      border: none;
    }
    
    .btn-delete:hover {
      background-color: rgba(220, 53, 69, 0.2);
      color: var(--danger);
    }
    
    .btn-export {
      border-radius: var(--radius);
      font-weight: 600;
      padding: 0.75rem 1.25rem;
      transition: all 0.3s;
    }
    
    /* Empty state */
    .empty-state {
      padding: 3rem 0;
      text-align: center;
    }
    
    .empty-state i {
      font-size: 3rem;
      color: var(--secondary);
      margin-bottom: 1rem;
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
    }
    
    @media (max-width: 768px) {
      .filter-section {
        padding: 1rem;
      }
      
      .table-responsive {
        padding: 1rem;
      }
      
      .btn-action {
        margin-bottom: 0.5rem;
        display: block;
        width: 100%;
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
      <?php if (!in_array($role, ['admin', 'small-admin', 'small-admi'])): ?>
      <li class="nav-item">
        <a class="nav-link <?= basename($_SERVER['PHP_SELF']) == 'analyze.php' ? 'active' : '' ?>" href="analyze.php">
          <i class="fas fa-chart-bar"></i> Dashboard
        </a>
      </li>
      <?php endif; ?>
      
      <?php if ($role === 'admin'): ?>
        <li class="nav-item">
          <a class="nav-link <?= basename($_SERVER['PHP_SELF']) == 'dashboard.php' ? 'active' : '' ?>" href="dashboard.php">
            <i class="fas fa-users-cog"></i> Dashboard
          </a>
        </li>
      <?php elseif (in_array($role, ['small-admin', 'small-admi'])): ?>
        <li class="nav-item">
          <a class="nav-link <?= basename($_SERVER['PHP_SELF']) == 'dashboard.php' ? 'active' : '' ?>" href="dashboard.php">
            <i class="fas fa-user-friends"></i> My Users
          </a>
        </li>
      <?php endif; ?>
      
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
    <!-- Report Container -->
    <div class="report-container">
      <div class="report-header">
        <h2><i class="fas fa-file-medical me-2"></i> Brain Cancer Prediction Report</h2>
      </div>
      
      <!-- Filter Section -->
      <div class="filter-section">
        <form method="get" class="row g-3 align-items-end">
          <div class="col-md-3">
            <label for="search" class="filter-label">Search</label>
            <input type="text" name="search" id="search" class="form-control" 
                   placeholder="Name or phone" 
                   value="<?= isset($_GET['search']) ? htmlspecialchars($_GET['search']) : '' ?>">
          </div>
          
          <div class="col-md-2">
            <label for="gender" class="filter-label">Gender</label>
            <select name="gender" id="gender" class="form-select">
              <option value="">All Genders</option>
              <option value="male" <?= (isset($_GET['gender']) && $_GET['gender'] == 'male') ? 'selected' : '' ?>>Male</option>
              <option value="female" <?= (isset($_GET['gender']) && $_GET['gender'] == 'female') ? 'selected' : '' ?>>Female</option>
            </select>
          </div>
          
          <div class="col-md-2">
            <label for="start_date" class="filter-label">From Date</label>
            <input type="date" name="start_date" id="start_date" class="form-control" 
                   value="<?= isset($_GET['start_date']) ? $_GET['start_date'] : '' ?>">
          </div>
          
          <div class="col-md-2">
            <label for="end_date" class="filter-label">To Date</label>
            <input type="date" name="end_date" id="end_date" class="form-control" 
                   value="<?= isset($_GET['end_date']) ? $_GET['end_date'] : '' ?>">
          </div>
          
          <div class="col-md-3 d-flex align-items-end gap-2">
            <a href="report.php" class="btn btn-outline-secondary btn-export">
              <i class="fas fa-sync-alt"></i> Reset
            </a>
            <a href="?export=excel" class="btn btn-success btn-export">
              <i class="fas fa-file-excel"></i> Excel
            </a>
            <button type="button" onclick="exportToPDF()" class="btn btn-danger btn-export">
              <i class="fas fa-file-pdf"></i> PDF
            </button>
          </div>
        </form>
      </div>
      
      <!-- Table Section -->
      <div class="table-responsive">
        <table class="table table-hover" id="reportTable">
          <thead>
            <tr>
              <th>Full Name</th>
              <th>Age</th>
              <th>Phone</th>
              <th>Gender</th>
              <th>Prediction</th>
              <th>Submitted At</th>
              <th>Actions</th>
            </tr>
          </thead>
          <tbody>
            <?php if ($result && $result->num_rows > 0): ?>
              <?php while($row = $result->fetch_assoc()): ?>
              <tr>
                <td><?= htmlspecialchars($row['fullname']) ?></td>
                <td><?= htmlspecialchars($row['age']) ?></td>
                <td><?= htmlspecialchars($row['phone']) ?></td>
                <td><?= ucfirst(htmlspecialchars($row['sex'])) ?></td>
                <td>
                  <?php
                    $json = json_decode($row['prediction'], true);
                    $diagnosis = isset($json['diagnosis']) ? $json['diagnosis'] : $row['prediction'];
                    $badgeClass = strpos(strtolower($diagnosis), 'no cancer') !== false ? 'badge-no-cancer' : 'badge-cancer';
                  ?>
                  <span class="<?= $badgeClass ?>">
                    <i class="fas <?= strpos(strtolower($diagnosis), 'no cancer') !== false ? 'fa-check-circle' : 'fa-exclamation-triangle' ?> me-1"></i>
                    <?= htmlspecialchars($diagnosis) ?>
                  </span>
                </td>
                <td><?= date('M j, Y g:i A', strtotime($row['created_at'])) ?></td>
                <td>
                  <a href="update.php?id=<?= $row['id'] ?>" class="btn btn-sm btn-action btn-edit">
                    <i class="fas fa-edit"></i> Edit
                  </a>
                  <a href="report.php?delete=<?= $row['id'] ?>" class="btn btn-sm btn-action btn-delete" onclick="return confirmDelete(<?= $row['id'] ?>)">
                    <i class="fas fa-trash-alt"></i> Delete
                  </a>
                </td>
              </tr>
              <?php endwhile; ?>
            <?php else: ?>
              <tr>
                <td colspan="7" class="text-center py-4">
                  <div class="empty-state">
                    <i class="fas fa-info-circle"></i>
                    <h5 class="text-muted">No records found</h5>
                    <p class="text-muted">Try adjusting your search filters</p>
                  </div>
                </td>
              </tr>
            <?php endif; ?>
          </tbody>
        </table>
      </div>
    </div>
  </div>

  <script>
    // Export to PDF function
    function exportToPDF() {
      const { jsPDF } = window.jspdf;
      const doc = new jsPDF();
      
      // Report title
      doc.setFontSize(18);
      doc.setTextColor(59, 10, 133);
      doc.text("Brain Cancer Prediction Report", 105, 15, { align: 'center' });
      
      // Report date
      doc.setFontSize(10);
      doc.setTextColor(100, 100, 100);
      doc.text(`Generated on: ${new Date().toLocaleDateString()}`, 105, 22, { align: 'center' });
      
      // Filter information
      doc.setFontSize(10);
      let filterText = "All records";
      if (window.location.search) {
        const params = new URLSearchParams(window.location.search);
        const filters = [];
        if (params.get('search')) filters.push(`Search: ${params.get('search')}`);
        if (params.get('gender')) filters.push(`Gender: ${params.get('gender')}`);
        if (params.get('start_date') && params.get('end_date')) {
          filters.push(`Date range: ${params.get('start_date')} to ${params.get('end_date')}`);
        }
        if (filters.length) filterText = filters.join(", ");
      }
      doc.text(`Filters: ${filterText}`, 14, 30);
      
      // Table data
      const headers = [["Full Name", "Age", "Phone", "Gender", "Prediction", "Date"]];
      const rows = [];
      
      document.querySelectorAll("#reportTable tbody tr").forEach(tr => {
        if (tr.cells.length >= 6) { // Ensure we have enough cells
          const row = [
            tr.cells[0].innerText,
            tr.cells[1].innerText,
            tr.cells[2].innerText,
            tr.cells[3].innerText,
            tr.cells[4].innerText.replace(/Edit|Delete/g, '').trim(),
            tr.cells[5].innerText
          ];
          rows.push(row);
        }
      });
      
      // AutoTable
      doc.autoTable({
        head: headers,
        body: rows,
        startY: 40,
        styles: {
          fontSize: 9,
          cellPadding: 2,
          valign: 'middle'
        },
        headStyles: {
          fillColor: [59, 10, 133],
          textColor: 255,
          fontStyle: 'bold'
        },
        alternateRowStyles: {
          fillColor: [240, 240, 240]
        },
        columnStyles: {
          0: { cellWidth: 'auto' },
          1: { cellWidth: 'auto' },
          2: { cellWidth: 'auto' },
          3: { cellWidth: 'auto' },
          4: { cellWidth: 'auto' },
          5: { cellWidth: 'auto' }
        }
      });
      
      // Save the PDF
      doc.save(`BrainCancerReport_${new Date().toISOString().slice(0,10)}.pdf`);
    }
    
    // Delete confirmation
    function confirmDelete(id) {
      Swal.fire({
        title: 'Delete Record?',
        text: "You won't be able to revert this!",
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#d33',
        cancelButtonColor: '#6c757d',
        confirmButtonText: 'Yes, delete it!'
      }).then((result) => {
        if (result.isConfirmed) {
          window.location.href = `report.php?delete=${id}`;
        }
      });
      return false;
    }
    
    // Auto-submit form when filters change
    document.addEventListener("DOMContentLoaded", function() {
      const genderSelect = document.getElementById("gender");
      const startDate = document.getElementById("start_date");
      const endDate = document.getElementById("end_date");
      
      genderSelect.addEventListener("change", function() {
        this.form.submit();
      });
      
      startDate.addEventListener("change", function() {
        if (endDate.value) this.form.submit();
      });
      
      endDate.addEventListener("change", function() {
        if (startDate.value) this.form.submit();
      });
    });
  </script>
</body>
</html>