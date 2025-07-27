<?php
session_start();
$logged_in = isset($_SESSION['user_email']);
$user_name = $_SESSION['user_name'] ?? 'User Name';
$user_email = $_SESSION['user_email'] ?? 'user@example.com';
$user_role = $_SESSION['role'] ?? 'user';
$profile_image = $_SESSION['profile_image'] ?? '';

function has_valid_image($image_path) {
    return !empty($image_path) && $image_path !== 'images/default-avatar.png';
}
?>



<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Brain Cancer Detection</title>
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;700&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css" />
  <style>
     body, html {
      margin: 0;
      padding: 0;
      height: 100%;
      scroll-behavior: smooth;
      font-family: 'Poppins', sans-serif;
    }
    body {
      padding-top: 80px;
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
    }
    .nav-link:hover {
      color: #007bff;
    }
    .hero {
      position: relative;
      background-image: url('Home image.jpg');
      background-size: cover;
      background-position: center;
      height: 100vh;
      display: flex;
      align-items: center;
      justify-content: center;
      color: white;
      text-align: center;
    }
    .overlay {
      position: absolute;
      top: 0;
      left: 0;
      height: 100%;
      width: 100%;
      background: rgba(0,0,0,0.5);
      z-index: 1;
    }
    .hero-content {
      position: relative;
      z-index: 2;
      background-color: rgba(255, 255, 255, 0.95);
      padding: 40px;
      border-radius: 12px;
      max-width: 650px;
      text-align: center;
    }
    .anthem {
      background: linear-gradient(to right, #ff8800, #3b0a85);
      color: white;
      font-weight: bold;
      padding: 8px 20px;
      border-radius: 20px;
      display: inline-block;
      margin-bottom: 20px;
      font-size: 14px;
      letter-spacing: 1px;
    }
    .hero-content h2 {
      font-weight: bold;
      margin-bottom: 20px;
      color: #111;
    }
    .hero-content p {
      color: #555;
      font-size: 18px;
      margin-bottom: 30px;
    }
    .btn-purple {
      background-color: #3b0a85;
      border: none;
      font-size: 18px;
      font-weight: bold;
      padding: 10px 25px;
      border-radius: 30px;
      transition: background-color 0.3s ease;
    }
    .btn-purple:hover {
      background-color: #5f2db4;
    }
    .scroll-down {
      position: absolute;
      bottom: 20px;
      left: 50%;
      transform: translateX(-50%);
      font-size: 30px;
      color: white;
      animation: bounce 2s infinite;
      z-index: 2;
      cursor: pointer;
    }
    @keyframes bounce {
      0%, 20%, 50%, 80%, 100% { transform: translateX(-50%) translateY(0); }
      40% { transform: translateX(-50%) translateY(-10px); }
      60% { transform: translateX(-50%) translateY(-5px); }
    }
    .card-img-top {
      width: 100%;
      height: 320px;
      object-fit: cover;
      object-position: center;
    }
    .card-title {
      font-weight: 700;
      font-size: 18px;
      margin-top: 10px;
      color: #333;
      text-align: center;
    
    }
    .dropdown-menu {
      min-width: 250px;
    }
    .dropdown-menu {
  left: 10% !important;
  right: auto;
}

    .fa-user-circle, .profile-icon {
      font-size: 35px;
      color: #6c757d;
    }
    .fa-user-circle.large {
      font-size: 60px;
    }
    .rounded-circle {
      object-fit: cover;
    }
    .card-img {
      max-height: 250px;
      object-fit: contain;
    }
    .card {
      max-width: 100%;
      height: 100%;
    }
    .card-body p {
      font-size: 0.9rem;
    }
  </style>

  

</head>
<body>

<!-- Sidebar Navbar -->
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

      <?php if ($logged_in): ?>
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
        <?php if ($user_role !== 'admin' && $user_role !== 'small-admin' && $user_role !== 'small-admi'): ?>
  <li class="nav-item mb-2">
    <a class="nav-link <?= basename($_SERVER['PHP_SELF']) == 'analyze.php' ? 'active text-primary font-weight-bold' : 'text-dark' ?>" href="analyze.php">
      <i class="fas fa-chart-bar mr-2"></i> Dashboard
    </a>
  </li>
<?php endif; ?>


        <?php if ($user_role === 'admin'): ?>
          <li class="nav-item mb-2">
            <a class="nav-link <?= basename($_SERVER['PHP_SELF']) == 'dashboard.php' ? 'active text-primary font-weight-bold' : 'text-dark' ?>" href="dashboard.php">
              <i class="fas fa-users-cog mr-2"></i> Dashboard
            </a>
          </li>
        <?php elseif ($user_role === 'small-admin' || $user_role === 'small-admi'): ?>
          <li class="nav-item mb-2">
            <a class="nav-link <?= basename($_SERVER['PHP_SELF']) == 'dashboard.php' ? 'active text-primary font-weight-bold' : 'text-dark' ?>" href="dashboard.php">
              <i class="fas fa-user-friends mr-2"></i> My Users
            </a>
          </li>
        <?php endif; ?>
      <?php else: ?>
        <li class="nav-item mt-4">
          <a class="btn btn-outline-primary w-100" href="login.html">Login</a>
        </li>
      <?php endif; ?>

      <?php if ($logged_in): ?>
  <!-- Sidebar User Dropdown -->
  <li class="nav-item dropdown text-center mt-4 border-top pt-4">
    <!-- Toggle Button -->
    <a class="nav-link dropdown-toggle d-block p-0" href="#" id="sidebarUserDropdown" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
      <?php if (has_valid_image($profile_image)): ?>
        <img src="<?= htmlspecialchars($profile_image); ?>" class="rounded-circle mb-2" width="60" height="60" alt="Profile Image">
      <?php else: ?>
        <i class="fas fa-user-circle fa-3x text-muted mb-2"></i>
      <?php endif; ?>
      <div class="font-weight-bold text-capitalize"><?= htmlspecialchars($user_name); ?></div>
      <small class="text-muted"><?= htmlspecialchars($user_email); ?></small><br>
      <span class="text-secondary small"><?= htmlspecialchars($user_role); ?></span>
    </a>

    <!-- Dropdown Menu -->
    <div class="dropdown-menu shadow mt-2" aria-labelledby="sidebarUserDropdown" style="min-width: 230px;">
      <a class="dropdown-item" href="update_profile.php"><i class="fas fa-user-edit mr-2"></i> Update Profile</a>
      <a class="dropdown-item" href="upload_profile_image.php"><i class="fas fa-camera mr-2"></i> Upload Image</a>
      <a class="dropdown-item text-danger" href="#" onclick="return confirmAccountDelete();"><i class="fas fa-trash-alt mr-2"></i> Delete Account</a>
      <div class="dropdown-divider"></div>
      <a class="dropdown-item text-danger font-weight-bold" href="logout.php"><i class="fas fa-sign-out-alt mr-2"></i> Logout</a>
    </div>
  </li>
<?php endif; ?>





    </ul>
  </nav>

  <!-- Main Content Area -->
  <div class="content pl-4" style="margin-left: 350px;">
    <!-- Your main page content goes here -->



<!-- Username Modal -->
<div class="modal fade" id="usernameModal" tabindex="-1" aria-labelledby="usernameModalLabel" aria-hidden="true">
  <div class="modal-dialog">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title">Change Username</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <div class="modal-body">
        <input type="text" id="username-input" class="form-control" placeholder="Enter new username">
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-primary" onclick="handleUsernameChange()">Save</button>
      </div>
    </div>
  </div>
</div>

<!-- Password Modal -->
<div class="modal fade" id="passwordModal" tabindex="-1" aria-labelledby="passwordModalLabel" aria-hidden="true">
  <div class="modal-dialog">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title">Change Password</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <div class="modal-body">
        <input type="password" id="password-input" class="form-control" placeholder="Enter new password">
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-primary" onclick="handlePasswordChange()">Save</button>
      </div>
    </div>
  </div>
</div>

<!-- Hero Section -->
<div class="hero" id="hero-section" style="position: relative; background-image: url('Home image.jpg'); background-size: cover; background-position: center; height: 100vh; display: flex; align-items: center; justify-content: center; color: white; text-align: center;">
  <div class="overlay" style="position: absolute; top: 0; left: 0; height: 100%; width: 100%; background: rgba(0,0,0,0.5); z-index: 1;"></div>
  <div class="hero-content" style="position: relative; z-index: 2; background-color: rgba(255,255,255,0.95); padding: 40px; border-radius: 12px; max-width: 650px;">
    <div class="anthem">LISTEN TO OUR NEW ANTHEM</div>
    <h2>Find the most Exciting Online Brain Cancer Prediction</h2>
    <p>This platform uses Machine Learning to detect brain cancer from MRI images, helping doctors with early diagnosis and treatment.</p>
    <a href="predict.php" class="btn btn-purple">Start Prediction</a>
  </div>
  <div class="scroll-down">
    <a href="#about-section"><i class="fas fa-chevron-down"></i></a>
  </div>
</div>

<!-- About Section as Bootstrap Card -->
<div class="container my-5" id="about-section">
  <div class="row justify-content-center">
    <div class="col-lg-10">
      <div class="card shadow-lg border-0">
        <div class="card-body p-5 text-center">
          <h2 class="card-title fw-bold mb-4" style="font-size: 2rem;">About This Project</h2>
          <p class="card-text fs-5 text-muted mb-4">
            This project is a web-based brain cancer detection system powered by machine learning.<br>
            It analyzes MRI images uploaded by users to predict the presence of cancer.<br>
            The system provides instant feedback using a pre-trained deep learning model.
          </p>
          <p class="card-text fs-5 text-muted">
            Users can view prediction summaries, gender-based insights, and age range statistics.<br>
            The dashboard includes real-time charts and an option to export results as PDF.<br>
            It is designed to assist doctors, researchers, and patients in early diagnosis.
          </p>
        </div>
      </div>
    </div>
  </div>
</div>

<div class="container py-4">
  <h2 class="text-center mb-4">Sample Dataset Images: Cancer and No-Cancer MRI Scans</h2>

  <!-- Row 1: Cancer Images -->
  <div class="row justify-content-center g-4 mb-3">
    <!-- Cancer Image 1 -->
    <div class="col-md-6 col-lg-5">
      <div class="card shadow-sm h-100">
        <div class="card-header bg-danger text-white text-center">
          Cancer Image 
        </div>
        <div class="card-body text-center">
          <img src="images/Yes1.jpg" alt="Cancer MRI 1" class="img-fluid card-img mb-2">
          <p>This MRI scan shows evidence of a brain Cancer.</p>
        </div>
      </div>
    </div>

    <!-- Cancer Image 2 -->
    <div class="col-md-6 col-lg-5">
      <div class="card shadow-sm h-100">
        <div class="card-header bg-danger text-white text-center">
          Cancer Image 
        </div>
        <div class="card-body text-center">
          <img src="images/Yes2.jpg" alt="Cancer MRI 2" class="img-fluid card-img mb-2">
          <p>Another MRI scan with visible cancerous regions.</p>
        </div>
      </div>
    </div>
  </div>

  <!-- Row 2: No-Cancer Images -->
  <div class="row justify-content-center g-4">
    <!-- No-Cancer Image 1 -->
    <div class="col-md-6 col-lg-5">
      <div class="card shadow-sm h-100">
        <div class="card-header bg-success text-white text-center">
          No-Cancer Image 
        </div>
        <div class="card-body text-center">
          <img src="images/No1.jpg" alt="No-Cancer MRI 1" class="img-fluid card-img mb-2">
          <p>This MRI scan appears normal with no signs of brain cancer.</p>
        </div>
      </div>
    </div>

    <!-- No-Cancer Image 2 -->
    <div class="col-md-6 col-lg-5">
      <div class="card shadow-sm h-100">
        <div class="card-header bg-success text-white text-center">
          No-Cancer Image 
        </div>
        <div class="card-body text-center">
          <img src="images/No2.jpg" alt="No-Cancer MRI 2" class="img-fluid card-img mb-2">
          <p>Healthy brain MRI with no detected abnormalities.</p>
        </div>
      </div>
    </div>
  </div>
</div>



<div class="container py-4">
    <h2 class="text-center mb-4">Dataset Distribution And Model Accuracy Comparison Charts</h2>

    <div class="row justify-content-center g-4">
      
      <!-- Pie Chart Card -->
      <div class="col-md-6 col-lg-5">
        <div class="card shadow-sm h-100">
          <div class="card-header bg-primary text-white text-center">
            Dataset Distribution
          </div>
          <div class="card-body text-center">
            <img src="images/pie_chart.png" alt="Brain MRI Dataset Distribution" class="img-fluid card-img mb-2">
            <p>This pie chart shows the percentage of MRI images with and without cancer.</p>
          </div>
        </div>
      </div>

      <!-- Accuracy Chart Card -->
      <div class="col-md-6 col-lg-5">
        <div class="card shadow-sm h-100">
          <div class="card-header bg-success text-white text-center">
            Model Accuracy Comparison
          </div>
          <div class="card-body text-center">
            <img src="images/accuracy_chart.png" alt="Model Accuracy Comparison" class="img-fluid card-img mb-2">
            <p>Accuracy comparison of different Machine learning models for brain Cancer detection.</p>
          </div>
        </div>
      </div>

    </div>
  </div>

<!-- Team Cards Section -->
<div class="container mt-5">
  <h4 class="text-center text-primary mb-4 fw-bold">About Us</h4>
  <div class="row justify-content-center">
    <div class="col-md-6 col-lg-3 mb-4">
      <div class="card h-100 shadow-sm">
        <img src="images/Abdirahman.jpg" class="card-img-top" alt="Eng Abdirahman Ahmed Mohamed">
        <div class="card-body">
          <h5 class="card-title">Eng Abdirahman Ahmed Mohamed</h5>
        </div>
      </div>
    </div>
    <div class="col-md-6 col-lg-3 mb-4">
      <div class="card h-100 shadow-sm">
        <img src="images/abdalle.jpg" class="card-img-top" alt="Eng Abdulkadir Abdi Adan">
        <div class="card-body">
          <h5 class="card-title">Eng Abdulkadir Abdi Adan</h5>
        </div>
      </div>
    </div>
    <div class="col-md-6 col-lg-3 mb-4">
      <div class="card h-100 shadow-sm">
        <img src="images/Libaan.jpg" class="card-img-top" alt="Eng Liban Osman Ali">
        <div class="card-body">
          <h5 class="card-title">Eng Liban Osman Ali</h5>
        </div>
      </div>
    </div>
    <div class="col-md-6 col-lg-3 mb-4">
      <div class="card h-100 shadow-sm">
        <img src="images/Moha.jpg" class="card-img-top" alt="Eng Mohamed Isse Mohamed">
        <div class="card-body">
          <h5 class="card-title">Eng Mohamed Isse Mohamed</h5>
        </div>
      </div>
    </div>
  </div>
</div>

<!-- Footer Section -->
<footer class="bg-dark text-light p-4 mt-5">
  <div class="container">
    <div class="row text-center text-md-left">
      
      <div class="col-md-3 mb-3">
        <h5><i class="far fa-clock mr-2"></i>Timing</h5>
        <p>07:30 am to 16:30 pm</p>
      </div>

      <div class="col-md-3 mb-3">
        <h5><i class="far fa-envelope mr-2"></i>Email</h5>
        <p>info@gmail.com</p>
      </div>

      <div class="col-md-3 mb-3">
        <h5><i class="fas fa-phone-alt mr-2"></i>Phone</h5>
        <p>7896541239</p>
      </div>

      <div class="col-md-3 mb-3">
        <h5><i class="fas fa-share-alt mr-2"></i>Socials</h5>
        <a href="#"><i class="fab fa-facebook fa-lg mr-3"></i></a>
        <a href="#"><i class="fab fa-twitter fa-lg mr-3"></i></a>
        <a href="#"><i class="fab fa-instagram fa-lg mr-3"></i></a>
        <a href="#"><i class="fab fa-youtube fa-lg"></i></a>
      </div>

    </div>
  </div>
</footer>

<script>
const handleUsernameChange = async () => {
  const newUsername = document.getElementById("username-input").value.trim();

  if (!newUsername) {
    alert("Please enter a new username.");
    return;
  }

  try {
    const res = await fetch("update_username.php", {
      method: "POST",
      headers: { "Content-Type": "application/json" },
      body: JSON.stringify({ username: newUsername })
    });

    const data = await res.json();
    if (data.success) {
      alert("Username updated successfully.");
      var usernameModal = bootstrap.Modal.getInstance(document.getElementById("usernameModal"));
      usernameModal.hide();
    } else {
      alert("Failed to update username.");
    }
  } catch (err) {
    alert("Error occurred.");
    console.error(err);
  }
};

const handlePasswordChange = async () => {
  const newPassword = document.getElementById("password-input").value.trim();

  if (!newPassword) {
    alert("Please enter a new password.");
    return;
  }

  try {
    const res = await fetch("update_password.php", {
      method: "POST",
      headers: { "Content-Type": "application/json" },
      body: JSON.stringify({ password: newPassword })
    });

    const data = await res.json();
    if (data.success) {
      alert("Password updated successfully.");
      var passwordModal = bootstrap.Modal.getInstance(document.getElementById("passwordModal"));
      passwordModal.hide();
    } else {
      alert("Failed to update password.");
    }
  } catch (err) {
    alert("Error occurred.");
    console.error(err);
  }
};
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


<!-- Sidebar Profile Section -->
<!-- Sidebar Profile Section -->






<!-- Scripts -->
 
<script src="https://code.jquery.com/jquery-3.5.1.slim.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@4.5.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
