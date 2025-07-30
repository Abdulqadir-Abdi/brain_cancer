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
  <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css" />
  <style>
    :root {
      --primary-color: #3b0a85;
      --secondary-color: #ff8800;
      --accent-color: #5f2db4;
      --light-bg: #f8f9fa;
      --dark-bg: #1a1a2e;
      --text-dark: #333;
      --text-light: #f8f9fa;
      --success-color: #28a745;
      --danger-color: #dc3545;
      --info-color: #17a2b8;
    }
    
    /* Base Styles */
    body, html {
      margin: 0;
      padding: 0;
      min-height: 100vh;
      font-family: 'Poppins', sans-serif;
      background: linear-gradient(135deg, var(--light-bg) 60%, #e6e6fa 100%);
      scroll-behavior: smooth;
      color: var(--text-dark);
    }
    
    /* Typography */
    h1, h2, h3, h4, h5, h6 {
      font-weight: 700;
      color: var(--primary-color);
    }
    
    .section-title {
      font-size: 2.2rem;
      margin-bottom: 3rem;
      text-align: center;
      position: relative;
      padding-bottom: 1rem;
    }
    
    .section-title::after {
      content: '';
      position: absolute;
      bottom: 0;
      left: 50%;
      transform: translateX(-50%);
      width: 80px;
      height: 4px;
      background: linear-gradient(90deg, var(--primary-color), var(--secondary-color));
      border-radius: 2px;
    }
    
    /* Navigation */
    .navbar {
      background: #fff;
      box-shadow: 0 4px 16px rgba(59,10,133,0.07);
      padding: 1rem 2rem;
      border-bottom: 2px solid #f3f3f3;
    }
    
    .navbar-brand {
      font-weight: 700;
      color: var(--primary-color) !important;
      letter-spacing: 1px;
      font-size: 1.5rem;
      display: flex;
      align-items: center;
    }
    
    .navbar-brand i {
      margin-right: 0.5rem;
    }
    
    .nav-link {
      font-weight: 500;
      margin-right: 1rem;
      color: var(--text-dark) !important;
      transition: all 0.2s;
      position: relative;
    }
    
    .nav-link.active, .nav-link:hover {
      color: var(--primary-color) !important;
    }
    
    .nav-link.active::after, .nav-link:hover::after {
      content: '';
      display: block;
      width: 60%;
      height: 2px;
      background: linear-gradient(90deg, var(--primary-color), var(--secondary-color));
      margin: 0.2rem auto 0 auto;
      border-radius: 2px;
    }
    
    /* Hero Section */
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
      color: var(--text-dark);
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
      background: linear-gradient(90deg, var(--secondary-color), var(--primary-color));
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
      color: var(--primary-color);
      font-size: 2.5rem;
      letter-spacing: 1px;
    }
    
    .hero-content p {
      color: #555;
      font-size: 1.15rem;
      margin-bottom: 2.2rem;
    }
    
    .btn-purple {
      background: linear-gradient(90deg, var(--primary-color), var(--accent-color));
      color: #fff;
      border: none;
      font-size: 1.15rem;
      font-weight: 700;
      padding: 0.85rem 2.7rem;
      border-radius: 2rem;
      transition: all 0.3s;
      box-shadow: 0 2px 8px rgba(59,10,133,0.10);
      letter-spacing: 1px;
    }
    
    .btn-purple:hover {
      background: linear-gradient(90deg, var(--accent-color), var(--primary-color));
      color: #fff;
      transform: translateY(-2px) scale(1.04);
      box-shadow: 0 4px 12px rgba(59,10,133,0.15);
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
    
    /* Card System - Updated */
    .card {
      border: none;
      border-radius: 1rem;
      box-shadow: 0 4px 12px rgba(0, 0, 0, 0.08);
      transition: all 0.3s ease;
      overflow: hidden;
      background: #fff;
      height: 100%;
      display: flex;
      flex-direction: column;
    }
    
    .card:hover {
      transform: translateY(-8px);
      box-shadow: 0 12px 24px rgba(59,10,133,0.15);
    }
    
    .card-img-container {
      position: relative;
      width: 100%;
      height: 250px; /* Fixed height for consistency */
      overflow: hidden;
    }
    
    .card-img-container.square {
      height: 300px; /* Slightly taller for team cards */
    }
    
    .card-img, .card-img-top {
      width: 100%;
      height: 100%;
      object-fit: cover;
      transition: transform 0.5s ease;
    }
    
    .card:hover .card-img,
    .card:hover .card-img-top {
      transform: scale(1.05);
    }
    
    .card-header {
      background-color: var(--primary-color);
      color: white;
      padding: 1rem;
      font-weight: 600;
      text-align: center;
      border-bottom: none;
    }
    
    .card-header.bg-danger { background-color: var(--danger-color); }
    .card-header.bg-success { background-color: var(--success-color); }
    .card-header.bg-primary { background-color: var(--primary-color); }
    .card-header.bg-info { background-color: var(--info-color); }
    
    .card-body {
      padding: 1.5rem;
      flex: 1;
      display: flex;
      flex-direction: column;
    }
    
    .card-title {
      font-weight: 700;
      color: var(--primary-color);
      margin-bottom: 0.75rem;
    }
    
    .card-text {
      color: #555;
      margin-bottom: 1rem;
      flex-grow: 1;
    }
    
    /* Team Cards */
    .team-card .card-img-container {
      height: 300px; /* Square images for team */
    }
    
    .team-card .card-body {
      text-align: center;
    }
    
    .team-card .card-title {
      font-size: 1.25rem;
      margin-bottom: 0.5rem;
    }
    
    .team-card .social-icons {
      margin-top: 1rem;
    }
    
    .team-card .social-icons a {
      color: var(--primary-color);
      margin: 0 0.5rem;
      font-size: 1.2rem;
      opacity: 0.7;
      transition: all 0.2s;
    }
    
    .team-card .social-icons a:hover {
      color: var(--secondary-color);
      opacity: 1;
      transform: translateY(-2px);
    }
    
    /* Footer */
    .footer {
      background: var(--dark-bg);
      color: var(--text-light);
      padding: 3rem 0 1.5rem;
      margin-top: 5rem;
    }
    
    .footer h5 {
      font-weight: 600;
      margin-bottom: 1.2rem;
      display: flex;
      align-items: center;
    }
    
    .footer h5 i {
      margin-right: 0.7rem;
      color: var(--secondary-color);
    }
    
    .footer a {
      color: var(--text-light);
      opacity: 0.8;
      transition: all 0.2s;
      text-decoration: none;
    }
    
    .footer a:hover {
      opacity: 1;
      color: var(--secondary-color);
    }
    
    .footer .social-icons a {
      font-size: 1.3rem;
      margin-right: 1rem;
    }
    
    /* Responsive Adjustments */
    @media (max-width: 767px) {
      .hero-content {
        padding: 2rem 1.5rem;
        margin: 1rem;
      }
      
      .section-title {
        font-size: 1.8rem;
      }
      
      .card-img-container {
        height: 200px; /* Adjusted for mobile */
      }
      
      .team-card .card-img-container {
        height: 250px; /* Adjusted for mobile */
      }
      
      .footer {
        text-align: center;
      }
      
      .footer h5 {
        justify-content: center;
      }
    }
  </style>
</head>
<body>
<?php if ($logged_in): ?>
<!-- Navbar for logged-in users -->
<nav class="navbar navbar-expand-lg navbar-light sticky-top">
  <div class="container-fluid">
    <a class="navbar-brand" href="home.php"><i class="fas fa-brain"></i>Brain Cancer Detection</a>
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
    <a class="navbar-brand" href="home.php"><i class="fas fa-brain"></i>Brain Cancer Detection</a>
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
    <h2>Advanced Brain Cancer Detection Platform</h2>
    <p>Our AI-powered system analyzes MRI scans to detect early signs of brain cancer with high accuracy, assisting medical professionals in diagnosis and treatment planning.</p>
    <a href="predict.php" class="btn btn-purple">Start Prediction</a>
  </div>
  <div class="scroll-down">
    <a href="#about-section"><i class="fas fa-chevron-down"></i></a>
  </div>
</section>

<!-- About Section -->
<section class="container my-5 py-5" id="about-section">
  <div class="row justify-content-center">
    <div class="col-lg-10">
      <div class="card shadow-lg border-0">
        <div class="card-body p-5 text-center">
          <h2 class="section-title">About This Project</h2>
          <p class="fs-5 text-muted mb-4">
            This innovative platform combines cutting-edge machine learning with medical imaging to revolutionize brain cancer detection.
            Our system provides rapid analysis of MRI scans, delivering results with clinical-grade accuracy to support healthcare professionals.
          </p>
          <p class="fs-5 text-muted">
            The platform offers comprehensive insights including tumor classification, growth patterns, and risk assessment.
            Designed for both clinical and research use, it enables early intervention and improves patient outcomes through advanced AI diagnostics.
          </p>
        </div>
      </div>
    </div>
  </div>
</section>

<!-- Sample Images Section -->
<section class="container py-5">
  <h2 class="section-title">MRI Scan Examples: Cancer vs. Healthy</h2>

  <div class="row g-4 justify-content-center">
    <!-- Cancer Images -->
    <div class="col-md-6 col-lg-5">
      <div class="card h-100">
        <div class="card-img-container">
          <img src="images/Yes1.jpg" alt="Cancer MRI Scan" class="card-img">
        </div>
        <div class="card-header bg-danger">Cancerous Tumor</div>
        <div class="card-body text-center">
          <p class="card-text">This MRI clearly shows a malignant brain tumor with irregular borders and contrast enhancement.</p>
        </div>
      </div>
    </div>

    <div class="col-md-6 col-lg-5">
      <div class="card h-100">
        <div class="card-img-container">
          <img src="images/Yes2.jpg" alt="Cancer MRI Scan" class="card-img">
        </div>
        <div class="card-header bg-danger">Advanced Glioma</div>
        <div class="card-body text-center">
          <p class="card-text">Example of a high-grade glioma showing mass effect and surrounding edema.</p>
        </div>
      </div>
    </div>

    <!-- Healthy Images -->
    <div class="col-md-6 col-lg-5 mt-4">
      <div class="card h-100">
        <div class="card-img-container">
          <img src="images/No1.jpg" alt="Healthy MRI Scan" class="card-img">
        </div>
        <div class="card-header bg-success">Normal Brain Scan</div>
        <div class="card-body text-center">
          <p class="card-text">Healthy MRI showing normal brain anatomy without any pathological findings.</p>
        </div>
      </div>
    </div>

    <div class="col-md-6 col-lg-5 mt-4">
      <div class="card h-100">
        <div class="card-img-container">
          <img src="images/No2.jpg" alt="Healthy MRI Scan" class="card-img">
        </div>
        <div class="card-header bg-success">Normal Variant</div>
        <div class="card-body text-center">
          <p class="card-text">Another example of a normal MRI with typical anatomical structures clearly visible.</p>
        </div>
      </div>
    </div>
  </div>
</section>

<!-- Data Visualization Section -->
<section class="container py-5">
  <h2 class="section-title">Dataset & Model Performance</h2>

  <div class="row g-4 justify-content-center">
    <div class="col-md-6 col-lg-5">
      <div class="card h-100">
        <div class="card-img-container">
          <img src="images/pie_chart.png" alt="Dataset Distribution" class="card-img">
        </div>
        <div class="card-header bg-primary">Dataset Composition</div>
        <div class="card-body text-center">
          <p class="card-text">Our curated dataset contains thousands of validated MRI scans with balanced representation of pathological and normal cases.</p>
        </div>
      </div>
    </div>

    <div class="col-md-6 col-lg-5">
      <div class="card h-100">
        <div class="card-img-container">
          <img src="images/accuracy_chart.png" alt="Model Accuracy" class="card-img">
        </div>
        <div class="card-header bg-info">Model Performance</div>
        <div class="card-body text-center">
          <p class="card-text">Comparative analysis showing our deep learning model outperforming traditional approaches in accuracy and specificity.</p>
        </div>
      </div>
    </div>
  </div>
</section>

<!-- Team Section -->
<section class="container py-5">
  <h2 class="section-title">Our Team</h2>

  <div class="row g-4 justify-content-center">
    <div class="col-md-6 col-lg-3">
      <div class="card team-card h-100">
        <div class="card-img-container square">
          <img src="images/Abdirahman.jpg" class="card-img-top" alt="Eng Abdirahman Ahmed Mohamed">
        </div>
        <div class="card-body">
          <h5 class="card-title">Eng Abdirahman Ahmed Mohamed</h5>
          <p class="card-text">Lead AI Engineer</p>
          <div class="social-icons">
            <a href="#"><i class="fab fa-linkedin"></i></a>
            <a href="#"><i class="fab fa-github"></i></a>
            <a href="#"><i class="fas fa-envelope"></i></a>
          </div>
        </div>
      </div>
    </div>

    <div class="col-md-6 col-lg-3">
      <div class="card team-card h-100">
        <div class="card-img-container square">
          <img src="images/abdalle.jpg" class="card-img-top" alt="Eng Abdulkadir Abdi Adan">
        </div>
        <div class="card-body">
          <h5 class="card-title">Eng Abdulkadir Abdi Adan</h5>
          <p class="card-text">Backend Developer</p>
          <div class="social-icons">
            <a href="#"><i class="fab fa-linkedin"></i></a>
            <a href="#"><i class="fab fa-github"></i></a>
            <a href="#"><i class="fas fa-envelope"></i></a>
          </div>
        </div>
      </div>
    </div>

    <div class="col-md-6 col-lg-3">
      <div class="card team-card h-100">
        <div class="card-img-container square">
          <img src="images/Libaan.jpg" class="card-img-top" alt="Eng Liban Osman Ali">
        </div>
        <div class="card-body">
          <h5 class="card-title">Eng Liban Osman Ali</h5>
          <p class="card-text">Frontend Developer</p>
          <div class="social-icons">
            <a href="#"><i class="fab fa-linkedin"></i></a>
            <a href="#"><i class="fab fa-github"></i></a>
            <a href="#"><i class="fas fa-envelope"></i></a>
          </div>
        </div>
      </div>
    </div>

    <div class="col-md-6 col-lg-3">
      <div class="card team-card h-100">
        <div class="card-img-container square">
          <img src="images/Moha.jpg" class="card-img-top" alt="Eng Mohamed Isse Mohamed">
        </div>
        <div class="card-body">
          <h5 class="card-title">Eng Mohamed Isse Mohamed</h5>
          <p class="card-text">Data Scientist</p>
          <div class="social-icons">
            <a href="#"><i class="fab fa-linkedin"></i></a>
            <a href="#"><i class="fab fa-github"></i></a>
            <a href="#"><i class="fas fa-envelope"></i></a>
          </div>
        </div>
      </div>
    </div>
  </div>
</section>

<!-- Footer -->
<footer class="footer">
  <div class="container">
    <div class="row">
      <div class="col-md-3 mb-4">
        <h5><i class="far fa-clock"></i> Working Hours</h5>
        <p>Monday - Friday: 7:30 AM - 4:30 PM</p>
        <p>Weekends: Closed</p>
      </div>

      <div class="col-md-3 mb-4">
        <h5><i class="far fa-envelope"></i> Contact Us</h5>
        <p><a href="mailto:info@braincancerdetection.com">info@braincancerdetection.com</a></p>
        <p><a href="mailto:support@braincancerdetection.com">support@braincancerdetection.com</a></p>
      </div>

      <div class="col-md-3 mb-4">
        <h5><i class="fas fa-phone-alt"></i> Phone</h5>
        <p><a href="tel:+1234567890">+1 (234) 567-890</a></p>
        <p><a href="tel:+1987654321">+1 (987) 654-321</a></p>
      </div>

      <div class="col-md-3 mb-4">
        <h5><i class="fas fa-share-alt"></i> Follow Us</h5>
        <div class="social-icons">
          <a href="#"><i class="fab fa-facebook"></i></a>
          <a href="#"><i class="fab fa-twitter"></i></a>
          <a href="#"><i class="fab fa-linkedin"></i></a>
          <a href="#"><i class="fab fa-instagram"></i></a>
          <a href="#"><i class="fab fa-youtube"></i></a>
        </div>
      </div>
    </div>
    
    <div class="row mt-4">
      <div class="col-12 text-center">
        <p class="mb-0">&copy; <?php echo date('Y'); ?> Brain Cancer Detection System. All rights reserved.</p>
      </div>
    </div>
  </div>
</footer>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script>
function confirmAccountDelete() {
  Swal.fire({
    title: 'Confirm Account Deletion',
    text: "This will permanently remove your account and all associated data. This action cannot be undone.",
    icon: 'warning',
    showCancelButton: true,
    confirmButtonColor: '#dc3545',
    cancelButtonColor: '#6c757d',
    confirmButtonText: 'Yes, delete my account',
    cancelButtonText: 'Cancel',
    reverseButtons: true
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