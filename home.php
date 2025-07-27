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
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Brain Cancer Detection</title>
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;700&display=swap" rel="stylesheet">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css" />
  <style>
    body, html {
      margin: 0;
      padding: 0;
      min-height: 100vh;
      font-family: 'Poppins', sans-serif;
      background: linear-gradient(135deg, #f8f9fa 60%, #e6e6fa 100%);
      scroll-behavior: smooth;
    }
    .navbar {
      background: #fff;
      box-shadow: 0 4px 16px rgba(59,10,133,0.07);
      padding: 1rem 2rem;
      border-bottom: 2px solid #f3f3f3;
    }
    .navbar-brand {
      font-weight: 700;
      color: #3b0a85 !important;
      letter-spacing: 1px;
      font-size: 1.5rem;
    }
    .nav-link {
      font-weight: 500;
      margin-right: 1rem;
      color: #333 !important;
      transition: color 0.2s;
      position: relative;
    }
    .nav-link.active, .nav-link:hover {
      color: #3b0a85 !important;
    }
    .nav-link.active::after, .nav-link:hover::after {
      content: '';
      display: block;
      width: 60%;
      height: 2px;
      background: linear-gradient(90deg, #3b0a85, #ff8800);
      margin: 0.2rem auto 0 auto;
      border-radius: 2px;
    }
    .hero {
      position: relative;
      background-image: url('Home image.jpg');
      background-size: cover;
      background-position: center;
      min-height: 80vh;
      display: flex;
      align-items: center;
      justify-content: center;
      color: #fff;
      text-align: center;
      overflow: hidden;
    }
    .hero::before {
      content: '';
      position: absolute;
      top: 0; left: 0; width: 100%; height: 100%;
      background: linear-gradient(120deg, rgba(59,10,133,0.85) 60%, rgba(255,136,0,0.7) 100%);
      z-index: 1;
      animation: heroFadeIn 1.2s;
    }
    @keyframes heroFadeIn {
      from { opacity: 0; }
      to { opacity: 1; }
    }
    .hero-content {
      position: relative;
      z-index: 2;
      background: rgba(255,255,255,0.98);
      color: #222;
      padding: 3.5rem 2.5rem;
      border-radius: 2rem;
      max-width: 650px;
      margin: 2rem auto;
      box-shadow: 0 8px 32px rgba(59,10,133,0.13);
      animation: fadeInUp 1.2s;
    }
    @keyframes fadeInUp {
      from { opacity: 0; transform: translateY(40px); }
      to { opacity: 1; transform: translateY(0); }
    }
    .anthem {
      background: linear-gradient(90deg, #ff8800, #3b0a85);
      color: #fff;
      font-weight: bold;
      padding: 0.5rem 1.5rem;
      border-radius: 2rem;
      display: inline-block;
      margin-bottom: 1.2rem;
      font-size: 1rem;
      letter-spacing: 1px;
      box-shadow: 0 2px 8px rgba(59,10,133,0.08);
      animation: fadeIn 2s;
    }
    @keyframes fadeIn {
      from { opacity: 0; }
      to { opacity: 1; }
    }
    .hero-content h2 {
      font-weight: 800;
      margin-bottom: 1.2rem;
      color: #3b0a85;
      font-size: 2.5rem;
      letter-spacing: 1px;
    }
    .hero-content p {
      color: #555;
      font-size: 1.15rem;
      margin-bottom: 2.2rem;
    }
    .btn-purple {
      background: linear-gradient(90deg, #3b0a85, #5f2db4);
      color: #fff;
      border: none;
      font-size: 1.15rem;
      font-weight: 700;
      padding: 0.85rem 2.7rem;
      border-radius: 2rem;
      transition: background 0.3s, transform 0.2s;
      box-shadow: 0 2px 8px rgba(59,10,133,0.10);
      letter-spacing: 1px;
    }
    .btn-purple:hover {
      background: linear-gradient(90deg, #5f2db4, #3b0a85);
      color: #fff;
      transform: translateY(-2px) scale(1.04);
    }
    .scroll-down {
      position: absolute;
      bottom: 2.5rem;
      left: 50%;
      transform: translateX(-50%);
      font-size: 2.2rem;
      color: #fff;
      animation: bounce 2s infinite;
      z-index: 2;
      cursor: pointer;
      opacity: 0.85;
    }
    @keyframes bounce {
      0%, 20%, 50%, 80%, 100% { transform: translateX(-50%) translateY(0); }
      40% { transform: translateX(-50%) translateY(-12px); }
      60% { transform: translateX(-50%) translateY(-6px); }
    }
    .section-title {
      font-weight: 800;
      color: #3b0a85;
      margin-bottom: 2.5rem;
      text-align: center;
      letter-spacing: 1px;
    }
    .card-img-top, .card-img {
      width: 100%;
      height: 120px;
      object-fit: cover;
      border-radius: 1rem 1rem 0 0;
      transition: transform 0.3s;
    }
    .card {
      border: none;
      border-radius: 1rem;
      box-shadow: 0 2px 12px rgba(59,10,133,0.07);
      transition: transform 0.2s, box-shadow 0.2s;
      overflow: hidden;
      background: #fff;
    }
    .card:hover {
      transform: translateY(-6px) scale(1.025);
      box-shadow: 0 12px 32px rgba(59,10,133,0.15);
    }
    .card-header {
      border-radius: 1rem 1rem 0 0;
      font-weight: 700;
      font-size: 1.1rem;
      letter-spacing: 0.5px;
    }
    .footer {
      background: #3b0a85;
      color: #fff;
      padding: 2rem 0 1rem 0;
      margin-top: 3rem;
      border-top-left-radius: 2rem;
      border-top-right-radius: 2rem;
      box-shadow: 0 -2px 16px rgba(59,10,133,0.07);
    }
    .footer a { color: #fff; opacity: 0.8; transition: opacity 0.2s; }
    .footer a:hover { opacity: 1; }
    .team-card img {
      height: 180px;
      object-fit: cover;
      border-radius: 1rem 1rem 0 0;
      filter: grayscale(10%);
      transition: filter 0.3s, transform 0.3s;
    }
    .team-card:hover img {
      filter: grayscale(0%);
      transform: scale(1.04);
    }
    .team-card .card-title {
      font-size: 1.1rem;
      font-weight: 700;
      color: #3b0a85;
      letter-spacing: 0.5px;
    }
    .team-card .social-icons {
      margin-top: 0.5rem;
    }
    .team-card .social-icons a {
      color: #3b0a85;
      margin: 0 0.3rem;
      font-size: 1.2rem;
      opacity: 0.7;
      transition: opacity 0.2s, color 0.2s;
    }
    .team-card .social-icons a:hover {
      color: #ff8800;
      opacity: 1;
    }
    @media (max-width: 767px) {
      .hero-content { padding: 2rem 1rem; }
      .card-img-top, .card-img { height: 80px; }
      .team-card img { height: 120px; }
      .footer { border-radius: 0; }
    }
    html {
      scroll-behavior: smooth;
    }
    /* Fixes card layout to wrap around content correctly */
.card {
  max-width: 100%;
  height: auto;
  border-radius: 0.5rem;
  overflow: hidden;
  box-shadow: 0 2px 6px rgba(0,0,0,0.1);
}

/* Fix for regular content card images */
.card-img {
  width: 100%;
  height: 200px;
  object-fit: cover;
  border-radius: 0.35rem;
}

/* Used for flexible custom cards like pie chart, MRI etc. */
.card-img-custom {
  width: 100%;
  height: 200px;
  object-fit: cover;
  border-radius: 0.35rem;
}

/* Team images */
.card-img-top {
  width: 100%;
  height: 280px;
  object-fit: cover;
  object-position: center;
  border-radius: 0.5rem 0.5rem 0 0;
}

/* Text inside cards */
.card-body p {
  font-size: 0.95rem;
  margin-bottom: 0;
}

  </style>
</head>
<body>
<?php if ($logged_in): ?>
<!-- Navbar for logged-in users -->
<nav class="navbar navbar-expand-lg navbar-light sticky-top">
  <div class="container-fluid">
    <a class="navbar-brand" href="home.php"><i class="fas fa-brain me-2"></i>Brain Cancer Detection</a>
    <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav" aria-controls="navbarNav" aria-expanded="false" aria-label="Toggle navigation">
      <span class="navbar-toggler-icon"></span>
    </button>
    <div class="collapse navbar-collapse" id="navbarNav">
      <ul class="navbar-nav ms-auto mb-2 mb-lg-0">
        <li class="nav-item"><a class="nav-link<?= basename($_SERVER['PHP_SELF']) == 'analyze.php' ? ' active' : '' ?>" href="analyze.php">Dashboard</a></li>
        <li class="nav-item dropdown">
          <a class="nav-link dropdown-toggle" href="#" id="userDropdown" role="button" data-bs-toggle="dropdown" aria-expanded="false">
            <?php if (has_valid_image($profile_image)): ?>
              <img src="<?= htmlspecialchars($profile_image); ?>" class="rounded-circle" width="32" height="32" alt="Profile Image">
            <?php else: ?>
              <i class="fas fa-user-circle"></i>
            <?php endif; ?>
            <?= htmlspecialchars($user_name); ?>
          </a>
          <ul class="dropdown-menu dropdown-menu-end" aria-labelledby="userDropdown">
            <li><a class="dropdown-item" href="update_profile.php"><i class="fas fa-user-edit me-2"></i>Update Profile</a></li>
            <li><a class="dropdown-item" href="upload_profile_image.php"><i class="fas fa-camera me-2"></i>Upload Image</a></li>
            <li><hr class="dropdown-divider"></li>
            <li><a class="dropdown-item text-danger" href="#" onclick="return confirmAccountDelete();"><i class="fas fa-trash-alt me-2"></i>Delete Account</a></li>
            <li><a class="dropdown-item text-danger fw-bold" href="logout.php"><i class="fas fa-sign-out-alt me-2"></i>Logout</a></li>
          </ul>
        </li>
      </ul>
    </div>
  </div>
</nav>
<?php else: ?>
<!-- Navbar for guests (only Login button) -->
<nav class="navbar navbar-expand-lg navbar-light sticky-top">
  <div class="container-fluid">
    <a class="navbar-brand" href="home.php"><i class="fas fa-brain me-2"></i>Brain Cancer Detection</a>
    <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav" aria-controls="navbarNav" aria-expanded="false" aria-label="Toggle navigation">
      <span class="navbar-toggler-icon"></span>
    </button>
    <div class="collapse navbar-collapse" id="navbarNav">
      <ul class="navbar-nav ms-auto mb-2 mb-lg-0">
        <li class="nav-item"><a class="btn btn-outline-primary ms-2" href="login.html">Login</a></li>
      </ul>
    </div>
  </div>
</nav>
<?php endif; ?>
<!-- Hero Section -->
<section class="hero d-flex align-items-center justify-content-center">
  <div class="hero-content shadow-lg">
    <div class="anthem mb-2">LISTEN TO OUR NEW ANTHEM</div>
    <h2>Find the most Exciting Online Brain Cancer Prediction</h2>
    <p>This platform uses Machine Learning to detect brain cancer from MRI images, helping doctors with early diagnosis and treatment.</p>
    <a href="predict.php" class="btn btn-purple">Start Prediction</a>
  </div>
  <div class="scroll-down">
    <a href="#about-section"><i class="fas fa-chevron-down"></i></a>
  </div>
</section>
<!-- About Section -->
<section class="container my-5" id="about-section">
  <div class="row justify-content-center">
    <div class="col-lg-10">
      <div class="card shadow-lg border-0">
        <div class="card-body p-5 text-center">
          <h2 class="section-title">About This Project</h2>
          <p class="fs-5 text-muted mb-4">
            This project is a web-based brain cancer detection system powered by machine learning.<br>
            It analyzes MRI images uploaded by users to predict the presence of cancer.<br>
            The system provides instant feedback using a pre-trained deep learning model.
          </p>
          <p class="fs-5 text-muted">
            Users can view prediction summaries, gender-based insights, and age range statistics.<br>
            The dashboard includes real-time charts and an option to export results as PDF.<br>
            It is designed to assist doctors, researchers, and patients in early diagnosis.
          </p>
        </div>
      </div>
    </div>
  </div>
</section>
<!-- Sample Dataset Images -->
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
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
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
  return false;
}
</script>
</body>
</html>
