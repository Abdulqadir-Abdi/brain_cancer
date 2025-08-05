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
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
  <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700&display=swap" rel="stylesheet">
  <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
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
    
    /* Prediction form styling */
    .prediction-container {
      max-width: 700px;
      margin: 2rem auto;
      background: white;
      border-radius: var(--radius-lg);
      box-shadow: var(--shadow);
      overflow: hidden;
      animation: fadeInUp 0.6s;
    }
    
    @keyframes fadeInUp {
      from { opacity: 0; transform: translateY(20px); }
      to { opacity: 1; transform: translateY(0); }
    }
    
    .form-header {
      background: linear-gradient(90deg, var(--primary), var(--primary-light));
      color: white;
      padding: 1.5rem;
      text-align: center;
    }
    
    .form-header h2 {
      font-weight: 700;
      margin-bottom: 0;
    }
    
    .form-body {
      padding: 2rem;
    }
    
    .form-label {
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
    
    .input-group-text {
      background-color: #f8f9fa;
      border-radius: var(--radius) 0 0 var(--radius);
    }
    
    .file-upload-wrapper {
      position: relative;
      margin-bottom: 1.5rem;
    }
    
    .file-upload-label {
      display: block;
      padding: 1.5rem;
      border: 2px dashed #dee2e6;
      border-radius: var(--radius);
      text-align: center;
      cursor: pointer;
      transition: all 0.3s;
    }
    
    .file-upload-label:hover {
      border-color: var(--primary-light);
      background-color: rgba(59, 10, 133, 0.05);
    }
    
    .file-upload-input {
      position: absolute;
      left: 0;
      top: 0;
      opacity: 0;
      width: 100%;
      height: 100%;
      cursor: pointer;
    }
    
    .file-upload-icon {
      font-size: 2.5rem;
      color: var(--primary);
      margin-bottom: 0.5rem;
    }
    
    .file-upload-text {
      font-weight: 500;
      color: var(--secondary);
    }
    
    .file-name {
      margin-top: 0.5rem;
      font-size: 0.9rem;
      color: var(--success);
      font-weight: 500;
    }
    
    .submit-btn {
      background: linear-gradient(90deg, var(--primary), var(--primary-light));
      color: white;
      border: none;
      font-weight: 600;
      padding: 0.75rem;
      border-radius: var(--radius);
      transition: all 0.3s;
      width: 100%;
      box-shadow: var(--shadow-sm);
    }
    
    .submit-btn:hover {
      background: linear-gradient(90deg, var(--primary-light), var(--primary));
      transform: translateY(-2px);
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
    }
    
    @media (max-width: 768px) {
      .form-body {
        padding: 1.5rem;
      }
      
      .prediction-container {
        margin: 1rem;
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
            <i class="fas fa-user-friends"></i> Dashboard
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

      <li class="nav-item">
        <a class="nav-link <?= basename($_SERVER['PHP_SELF']) == 'add_user.php' ? 'active' : '' ?>" href="add_user.php">
          <i class="fas fa-file-medical"></i> user manegment
        </a>
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
    <!-- Prediction Form -->
    <div class="prediction-container">
      <div class="form-header">
        <h2><i class="fas fa-microscope me-2"></i> Brain Cancer Prediction</h2>
      </div>
      
      <div class="form-body">
        <form action="predict_action.php" method="POST" enctype="multipart/form-data" id="predictionForm">
          <div class="mb-4">
            <label for="fullname" class="form-label">Full Name</label>
            <input type="text" name="fullname" id="fullname" class="form-control" 
                   placeholder="Enter patient's full name"
                   pattern="[A-Za-z\s]+" title="Only letters and spaces allowed" required>
          </div>
          
          <div class="row">
            <div class="col-md-6 mb-4">
              <label for="age" class="form-label">Age</label>
              <input type="number" name="age" id="age" class="form-control" 
                     placeholder="Enter age" min="1" max="125" required>
            </div>
            
            <div class="col-md-6 mb-4">
              <label for="sex" class="form-label">Gender</label>
              <select name="sex" id="sex" class="form-select" required>
                <option value="" selected disabled>Select gender</option>
                <option value="Male">Male</option>
                <option value="Female">Female</option>
              </select>
            </div>
          </div>
          
          <div class="mb-4">
            <label for="phone" class="form-label">Phone Number</label>
            <div class="input-group">
              <span class="input-group-text">+252</span>
              <input type="tel" name="phone" id="phone" class="form-control" 
                     placeholder="Enter phone number"
                     pattern="\d{7,12}"
                     title="Enter 7 to 12 digits after +252"
                     required>
            </div>
          </div>
          
          <div class="mb-4">
            <label class="form-label">MRI Image Upload</label>
            <div class="file-upload-wrapper">
              <label for="image" class="file-upload-label">
                <i class="fas fa-cloud-upload-alt file-upload-icon"></i>
                <div class="file-upload-text">Click to upload MRI image</div>
                <div class="file-name" id="fileName">No file selected</div>
              </label>
              <input type="file" name="image" id="image" class="file-upload-input" 
                     accept=".jpg,.jpeg,.png" required>
            </div>
            <small class="text-muted">Accepted formats: JPG, JPEG, PNG (Max 5MB)</small>
          </div>
          
          <div class="d-grid mt-4">
            <button type="submit" class="submit-btn">
              <i class="fas fa-search me-2"></i> Analyze MRI Image
            </button>
          </div>
        </form>
      </div>
    </div>
  </div>

  <script>
    // Show selected file name
    document.getElementById('image').addEventListener('change', function(e) {
      const fileName = this.files[0] ? this.files[0].name : 'No file selected';
      document.getElementById('fileName').textContent = fileName;
    });
    
    // Phone number paste handling
    document.getElementById('phone').addEventListener('paste', function(e) {
      const pasted = (e.clipboardData || window.clipboardData).getData('text');
      if (pasted.startsWith('+252')) {
        e.preventDefault();
        this.value = pasted.replace('+252', '');
      }
    });
    
    // Form submission handling
    document.getElementById('predictionForm').addEventListener('submit', function(e) {
      const fileInput = document.getElementById('image');
      if (fileInput.files[0] && fileInput.files[0].size > 5 * 1024 * 1024) {
        e.preventDefault();
        Swal.fire({
          title: 'File Too Large',
          text: 'Maximum file size is 5MB. Please choose a smaller file.',
          icon: 'error',
          confirmButtonColor: '#3b0a85'
        });
      }
    });
    
    // Delete account confirmation
    function confirmAccountDelete() {
      Swal.fire({
        title: 'Are you sure?',
        text: "This will permanently delete your account!",
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
      return false;
    }
  </script>
</body>
</html>