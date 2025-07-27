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

    $_SESSION['fullname'] = $fullname;
    $_SESSION['profile_image'] = $profileImage;
    $_SESSION['role'] = $role;
} else {
    $fullname = "Unknown";
    $profileImage = "default-avatar.png";
    $role = "user";
}
?>


<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Brain Cancer Prediction</title>
  <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
  <script src="https://code.jquery.com/jquery-3.5.1.slim.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@4.5.2/dist/js/bootstrap.bundle.min.js"></script>

  <style>
    body {
      padding-top: 0 !important;  /* Removed top space */
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
        .form-wrapper {
      max-width: 600px;
      margin: 150px auto 50px auto;  /* 🔧 100px top margin */
      background: white;
      padding: 40px;
      border-radius: 16px;
      box-shadow: 0 10px 20px rgba(0, 0, 0, 0.1);
    }

    .form-wrapper h2 {
      font-weight: bold;
      text-align: center;
      margin-bottom: 30px;
    }
    .form-wrapper label {
      font-weight: 500;
    }
    .form-control,
    .form-control-file {
      border-radius: 8px;
      padding: 12px;
      font-size: 16px;
    }
    .btn-primary {
      background-color: #3b0a85;
      border: none;
      border-radius: 8px;
      padding: 12px;
      font-weight: bold;
    }
    .btn-primary:hover {
      background-color: #5f2db4;
    }
  </style>
</head>
<body>

<!-- Sidebar Navbar (Converted from top navbar) -->
<div class="d-flex">
  <nav id="sidebar" class="bg-white shadow-sm vh-100 position-fixed" style="width: 250px; top: 0; left: 0; z-index: 1030;">
    <div class="sidebar-header text-center py-4 border-bottom">
      <a href="home.php" class="text-decoration-none font-weight-bold h5 d-block">Brain Cancer Detection</a>
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

      <!-- Profile Dropdown Styled -->
      <li class="nav-item dropdown text-center mt-4 pt-3 border-top">
        <a class="dropdown-toggle d-inline-block" href="#" id="sidebarUserDropdown" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
          <img src="<?= htmlspecialchars($profileImage); ?>" class="rounded-circle mb-2" width="50" height="50" alt="User">
          <div class="font-weight-bold text-capitalize"><?= htmlspecialchars($fullname); ?></div>
          <small class="text-muted"><?= htmlspecialchars($email); ?></small><br>
          <span class="text-secondary small"><?= htmlspecialchars($role); ?></span>
        </a>
        <div class="dropdown-menu dropdown-menu-right shadow p-3 mt-2" aria-labelledby="sidebarUserDropdown" style="min-width: 260px;">
          <a class="dropdown-item" href="update_profile.php"><i class="fas fa-user-edit mr-2"></i> Update Profile</a>
          <a class="dropdown-item" href="upload_profile_image.php"><i class="fas fa-camera mr-2"></i> Upload Image</a>
          <button class="dropdown-item text-danger" onclick="confirmDelete()"><i class="fas fa-user-slash mr-2"></i> Delete Account</button>
          <div class="dropdown-divider"></div>
          <a class="dropdown-item text-danger font-weight-bold" href="logout.php"><i class="fas fa-sign-out-alt mr-2"></i> Logout</a>
        </div>
      </li>
    </ul>
  </nav>

  <!-- Main Content -->
  <div class="content" style="margin-left: 350px;">
    <!-- Your main content will go here -->




<!-- Form Section -->
<div class="form-wrapper">
  <h2>Brain Cancer Prediction Form</h2>
  <form action="predict_action.php" method="POST" enctype="multipart/form-data">
    <div class="form-group">
        <label for="fullname">Full Name</label>
        <input type="text" name="fullname" class="form-control" placeholder="Enter full name"
               pattern="[A-Za-z\s]+" title="Only letters and spaces allowed" required>
      </div>

    <div class="form-group">
        <label for="age">Age</label>
        <input type="number" name="age" class="form-control" placeholder="Enter your age"
               min="1" max="125" required>
      </div>

    <div class="form-group">
  <label for="phone">Phone Number</label>
  <div class="input-group">
    <div class="input-group-prepend">
      <span class="input-group-text">+252</span>
    </div>
    <input type="tel" name="phone" id="phone" class="form-control" 
           placeholder="Enter your phone Number"
           pattern="\d{7,12}"
           title="Enter 7 to 12 digits after +252"
           required>
  </div>
</div>

<script>
  const phoneInput = document.getElementById('phone');
  
  // Optional: Prevent paste with +252
  phoneInput.addEventListener('paste', function (e) {
    const pasted = (e.clipboardData || window.clipboardData).getData('text');
    if (pasted.startsWith('+252')) {
      e.preventDefault();
      this.value = pasted.replace('+252', '');
    }
  });
</script>

    <div class="form-group">
  <label for="sex" class="font-weight-bold">Gender</label>
  <select name="sex" id="sex" class="form-control py-2 rounded" required>
    <option value="Male">Male</option>
    <option value="Female">Female</option>
  </select>
</div>

    <div class="form-group">
        <label for="image">MRI Image</label>
        <input type="file" name="image" class="form-control-file" accept=".jpg,.jpeg,.png" required>
      </div>

      <button type="submit" class="btn btn-primary btn-block">Predict</button>
  </form>
</div>

  <!-- Delete Confirmation -->
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script>
function confirmAccountDelete() {
  Swal.fire({
    title: 'Are you sure?',
    text: "This will permanently delete your account.!",
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

<script>
  document.getElementById('phone').addEventListener('paste', function(e) {
    const pasted = (e.clipboardData || window.clipboardData).getData('text');
    if (pasted.startsWith('+252')) {
      e.preventDefault();
      this.value = pasted.replace('+252', '');
    }
  });
</script>

</body>
</html>
