<?php
session_start();
$conn = new mysqli("localhost", "root", "", "brain_cancer_db");

if (!isset($_SESSION['user_email'])) {
  header("Location: login.html");
  exit();
}

$email = $_SESSION['user_email'];
$message = "";

if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_FILES["profile_image"])) {
  $target_dir = "uploads/";
  if (!is_dir($target_dir)) {
    mkdir($target_dir, 0777, true);
  }

  $image_name = basename($_FILES["profile_image"]["name"]);
  $target_file = $target_dir . uniqid() . "_" . $image_name;
  $imageFileType = strtolower(pathinfo($target_file, PATHINFO_EXTENSION));

  $allowed_types = ["jpg", "jpeg", "png", "gif"];
  if (in_array($imageFileType, $allowed_types)) {
    if (move_uploaded_file($_FILES["profile_image"]["tmp_name"], $target_file)) {
      $stmt = $conn->prepare("UPDATE users SET profile_image=? WHERE email=?");
      $stmt->bind_param("ss", $target_file, $email);
      $stmt->execute();
      $_SESSION['profile_image'] = $target_file;
      header("Location: home.php");
      exit();
    } else {
      $message = "Sorry, there was an error uploading your file.";
    }
  } else {
    $message = "Only JPG, JPEG, PNG & GIF files are allowed.";
  }
}
?>
<!DOCTYPE html>
<html>
<head>
  <title>Upload Profile Image</title>
  <link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css">
</head>
<body class="bg-light">
  <div class="container mt-5">
    <div class="row justify-content-center">
      <div class="col-md-6">
        <div class="card shadow-lg border-0">
          <div class="card-body">
            <h4 class="card-title text-center mb-4">
              <i class="fas fa-camera-retro text-primary mr-2"></i> Upload Profile Image
            </h4>

            <?php if (!empty($message)): ?>
              <div class="alert alert-danger text-center">
                <?php echo $message; ?>
              </div>
            <?php endif; ?>

            <form method="post" enctype="multipart/form-data">
              <div class="form-group">
                <label for="profile_image"><strong>Select Image</strong></label>
                <input type="file" class="form-control-file border p-2 rounded" name="profile_image" id="profile_image" required>
              </div>
              <div class="text-center">
                <button type="submit" class="btn btn-success px-4 mt-3">
                  <i class="fas fa-upload mr-2"></i>Upload
                </button>
              </div>
            </form>

          </div>
        </div>
      </div>
    </div>
  </div>

  <!-- Required Bootstrap & Font Awesome -->
  <link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css" />
</body>

</html>
