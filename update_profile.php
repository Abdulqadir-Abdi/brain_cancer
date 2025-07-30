<?php
session_start();
$conn = new mysqli("localhost", "root", "", "brain_cancer_db");
if (!isset($_SESSION['user_email'])) {
  header("Location: login.html");
  exit();
}

$current_email = $_SESSION['user_email'];
$current_name = $_SESSION['user_name'];
$current_profile_pic = $_SESSION['profile_image'] ?? 'assets/images/default-avatar.png';
$message = "";

// Check if profile_image column exists
$column_exists = false;
$result = $conn->query("SHOW COLUMNS FROM users LIKE 'profile_image'");
if ($result->num_rows > 0) {
    $column_exists = true;
}

// Handle POST request
if ($_SERVER["REQUEST_METHOD"] == "POST") {
  $new_name = trim($_POST['fullname']);
  $new_email = trim($_POST['email']);
  $pass1 = $_POST['password'];
  $pass2 = $_POST['confirm'];

  if (empty($new_name) || empty($new_email)) {
    $message = "Name and Email cannot be empty.";
  } elseif (!empty($pass1) && $pass1 !== $pass2) {
    $message = "Passwords do not match.";
  } else {
    // Handle file upload
    $profile_pic_path = $current_profile_pic;
    if (isset($_FILES['profile_pic']) && $_FILES['profile_pic']['error'] === UPLOAD_ERR_OK) {
      $upload_dir = 'assets/uploads/profile_pics/';
      if (!file_exists($upload_dir)) {
        mkdir($upload_dir, 0777, true);
      }
      
      $file_ext = pathinfo($_FILES['profile_pic']['name'], PATHINFO_EXTENSION);
      $allowed_ext = ['jpg', 'jpeg', 'png', 'gif'];
      $max_size = 2 * 1024 * 1024; // 2MB
      
      if (in_array(strtolower($file_ext), $allowed_ext)) {
        if ($_FILES['profile_pic']['size'] <= $max_size) {
          // Delete old profile picture if it's not the default
          if ($current_profile_pic !== 'assets/images/default-avatar.png' && file_exists($current_profile_pic)) {
            unlink($current_profile_pic);
          }
          
          $new_filename = uniqid('profile_', true) . '.' . $file_ext;
          $upload_path = $upload_dir . $new_filename;
          
          if (move_uploaded_file($_FILES['profile_pic']['tmp_name'], $upload_path)) {
            $profile_pic_path = $upload_path;
          } else {
            $message = "Error uploading profile picture.";
          }
        } else {
          $message = "File size too large. Maximum 2MB allowed.";
        }
      } else {
        $message = "Invalid file format. Only JPG, PNG, and GIF are allowed.";
      }
    }

    $new_password = !empty($pass1) ? password_hash($pass1, PASSWORD_DEFAULT) : null;

    // Prepare the SQL query based on what needs to be updated
    if ($new_password) {
      if ($column_exists) {
        $stmt = $conn->prepare("UPDATE users SET fullname=?, email=?, password=?, profile_image=? WHERE email=?");
        $stmt->bind_param("sssss", $new_name, $new_email, $new_password, $profile_pic_path, $current_email);
      } else {
        $stmt = $conn->prepare("UPDATE users SET fullname=?, email=?, password=? WHERE email=?");
        $stmt->bind_param("ssss", $new_name, $new_email, $new_password, $current_email);
      }
    } else {
      if ($column_exists) {
        $stmt = $conn->prepare("UPDATE users SET fullname=?, email=?, profile_image=? WHERE email=?");
        $stmt->bind_param("ssss", $new_name, $new_email, $profile_pic_path, $current_email);
      } else {
        $stmt = $conn->prepare("UPDATE users SET fullname=?, email=? WHERE email=?");
        $stmt->bind_param("sss", $new_name, $new_email, $current_email);
      }
    }

    if ($stmt->execute()) {
      // Update session
      $_SESSION['user_name'] = $new_name;
      $_SESSION['user_email'] = $new_email;
      if ($column_exists) {
        $_SESSION['profile_image'] = $profile_pic_path;
      }

      // Success message with SweetAlert
      $_SESSION['update_success'] = true;
      header("Location: update_profile.php");
      exit();
    } else {
      $message = "Error updating profile: " . $conn->error;
    }
  }
}

// Display success message if redirected after successful update
if (isset($_SESSION['update_success'])) {
  $message = "success:Profile updated successfully!";
  unset($_SESSION['update_success']);
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Update Profile | Brain Cancer Detection System</title>
  
  <!-- Bootstrap 5 CSS -->
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
  
  <!-- Font Awesome -->
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
  
  <!-- Custom CSS -->
  <style>
    :root {
      --primary: #3b0a85;
      --primary-light: #5e35b1;
      --primary-dark: #280a5e;
      --secondary: #6c757d;
      --success: #28a745;
      --danger: #dc3545;
      --light: #f8f9fa;
      --dark: #343a40;
      --gray-100: #f8f9fa;
      --gray-200: #e9ecef;
      --gray-300: #dee2e6;
    }
    
    body {
      background-color: #f5f7fa;
      font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
      min-height: 100vh;
    }
    
    .profile-card {
      border-radius: 16px;
      box-shadow: 0 10px 30px rgba(0, 0, 0, 0.1);
      overflow: hidden;
      border: none;
    }
    
    .profile-header {
      background: linear-gradient(135deg, var(--primary) 0%, var(--primary-light) 100%);
      padding: 2rem;
      color: white;
      text-align: center;
    }
    
    .profile-body {
      padding: 2rem;
      background-color: white;
    }
    
    .profile-pic-container {
      position: relative;
      width: 150px;
      height: 150px;
      margin: -75px auto 1rem;
    }
    
    .profile-pic {
      width: 100%;
      height: 100%;
      object-fit: cover;
      border-radius: 50%;
      border: 5px solid white;
      box-shadow: 0 5px 15px rgba(0, 0, 0, 0.1);
      transition: all 0.3s ease;
    }
    
    .profile-pic:hover {
      transform: scale(1.05);
    }
    
    .file-upload-btn {
      position: absolute;
      bottom: 10px;
      right: 10px;
      width: 40px;
      height: 40px;
      background-color: var(--primary);
      color: white;
      border-radius: 50%;
      display: flex;
      align-items: center;
      justify-content: center;
      cursor: pointer;
      transition: all 0.3s ease;
      border: 2px solid white;
    }
    
    .file-upload-btn:hover {
      background-color: var(--primary-dark);
      transform: scale(1.1);
    }
    
    .file-upload-input {
      display: none;
    }
    
    .form-control {
      border-radius: 8px;
      padding: 12px 15px;
      border: 1px solid var(--gray-300);
      transition: all 0.3s;
    }
    
    .form-control:focus {
      border-color: var(--primary);
      box-shadow: 0 0 0 0.25rem rgba(59, 10, 133, 0.25);
    }
    
    .btn-primary {
      background-color: var(--primary);
      border-color: var(--primary);
      padding: 10px 24px;
      border-radius: 8px;
      font-weight: 500;
      transition: all 0.3s;
    }
    
    .btn-primary:hover {
      background-color: var(--primary-dark);
      border-color: var(--primary-dark);
      transform: translateY(-2px);
    }
    
    .btn-block {
      display: block;
      width: 100%;
    }
    
    .divider {
      display: flex;
      align-items: center;
      margin: 20px 0;
    }
    
    .divider::before, .divider::after {
      content: "";
      flex: 1;
      border-bottom: 1px solid var(--gray-200);
    }
    
    .divider-text {
      padding: 0 10px;
      color: var(--secondary);
      font-size: 14px;
    }
    
    .password-toggle {
      position: absolute;
      right: 10px;
      top: 50%;
      transform: translateY(-50%);
      cursor: pointer;
      color: var(--secondary);
    }
    
    @media (max-width: 768px) {
      .profile-card {
        margin-top: 20px;
      }
      
      .profile-header {
        padding: 1.5rem;
      }
      
      .profile-body {
        padding: 1.5rem;
      }
    }
  </style>
</head>
<body>
  <div class="container py-5">
    <div class="row justify-content-center">
      <div class="col-lg-8">
        <div class="profile-card">
          <!-- Profile Header -->
          <div class="profile-header">
            <h2><i class="fas fa-user-edit me-2"></i> Update Profile</h2>
            <p class="mb-0">Manage your account information</p>
          </div>
          
          <!-- Profile Body -->
          <div class="profile-body">
            <?php if (!empty($message)): ?>
              <?php if (strpos($message, 'success:') === 0): ?>
                <div class="alert alert-success alert-dismissible fade show" role="alert">
                  <?= substr($message, 8) ?>
                  <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
              <?php else: ?>
                <div class="alert alert-danger alert-dismissible fade show" role="alert">
                  <?= $message ?>
                  <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
              <?php endif; ?>
            <?php endif; ?>
            
            <form method="post" enctype="multipart/form-data">
              <!-- Profile Picture Upload -->
              <div class="profile-pic-container">
                <img src="<?= htmlspecialchars($current_profile_pic) ?>" class="profile-pic" id="profile-pic-preview" alt="Profile Picture">
                <label class="file-upload-btn" for="profile-pic-upload" title="Change photo">
                  <i class="fas fa-camera"></i>
                </label>
                <input type="file" class="file-upload-input" id="profile-pic-upload" name="profile_pic" accept="image/*">
              </div>
              
              <!-- Personal Information Section -->
              <h5 class="mb-3 text-center"><i class="fas fa-user-circle me-2"></i>Personal Information</h5>
              
              <div class="mb-3">
                <label for="fullname" class="form-label">Full Name</label>
                <input type="text" class="form-control" id="fullname" name="fullname" required value="<?= htmlspecialchars($current_name) ?>">
              </div>
              
              <div class="mb-3">
                <label for="email" class="form-label">Email Address</label>
                <input type="email" class="form-control" id="email" name="email" required value="<?= htmlspecialchars($current_email) ?>">
              </div>
              
              <!-- Password Section -->
              <div class="divider">
                <span class="divider-text"><i class="fas fa-lock me-1"></i>Password Update</span>
              </div>
              
              <div class="mb-3 position-relative">
                <label for="password" class="form-label">New Password</label>
                <input type="password" class="form-control" id="password" name="password" placeholder="Leave blank to keep current password">
                <span class="password-toggle" onclick="togglePassword('password')">
                  <i class="fas fa-eye"></i>
                </span>
              </div>
              
              <div class="mb-4 position-relative">
                <label for="confirm" class="form-label">Confirm Password</label>
                <input type="password" class="form-control" id="confirm" name="confirm" placeholder="Leave blank to keep current password">
                <span class="password-toggle" onclick="togglePassword('confirm')">
                  <i class="fas fa-eye"></i>
                </span>
              </div>
              
              <!-- Submit Button -->
              <div class="button-group">
                <button type="submit" class="btn btn-primary">
                  <i class="fas fa-save me-2"></i>Save Changes
                </button>
                <a href="dashboard.php" class="btn btn-outline-primary">
                  <i class="fas fa-arrow-left me-2"></i>Back to Dashboard
                </a>
              </div>
              
            </form>
          </div>
        </div>
      </div>
    </div>
  </div>

  <!-- Bootstrap JS Bundle with Popper -->
  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
  
  <!-- SweetAlert2 for beautiful alerts -->
  <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
  
  <script>
    // Update profile picture preview when a new image is selected
    document.getElementById('profile-pic-upload').addEventListener('change', function(e) {
      const file = e.target.files[0];
      if (file) {
        // Validate file size (client-side)
        const maxSize = 2 * 1024 * 1024; // 2MB
        if (file.size > maxSize) {
          Swal.fire({
            icon: 'error',
            title: 'File too large',
            text: 'Maximum file size is 2MB',
          });
          this.value = ''; // Clear the file input
          return;
        }
        
        // Validate file type (client-side)
        const validTypes = ['image/jpeg', 'image/png', 'image/gif'];
        if (!validTypes.includes(file.type)) {
          Swal.fire({
            icon: 'error',
            title: 'Invalid file type',
            text: 'Only JPG, PNG, and GIF images are allowed',
          });
          this.value = ''; // Clear the file input
          return;
        }
        
        // Show preview
        const reader = new FileReader();
        reader.onload = function(event) {
          document.getElementById('profile-pic-preview').src = event.target.result;
        };
        reader.readAsDataURL(file);
      }
    });
    
    // Toggle password visibility
    function togglePassword(fieldId) {
      const field = document.getElementById(fieldId);
      const icon = field.nextElementSibling.querySelector('i');
      
      if (field.type === 'password') {
        field.type = 'text';
        icon.classList.remove('fa-eye');
        icon.classList.add('fa-eye-slash');
      } else {
        field.type = 'password';
        icon.classList.remove('fa-eye-slash');
        icon.classList.add('fa-eye');
      }
    }
    
    // Show success message if redirected after update
    <?php if (strpos($message, 'success:') === 0): ?>
    document.addEventListener('DOMContentLoaded', function() {
      Swal.fire({
        icon: 'success',
        title: 'Profile Updated',
        text: '<?= substr($message, 8) ?>',
        timer: 3000,
        showConfirmButton: false
      });
    });
    <?php endif; ?>
  </script>
</body>
</html>