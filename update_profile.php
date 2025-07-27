<?php
session_start();
$conn = new mysqli("localhost", "root", "", "brain_cancer_db");
if (!isset($_SESSION['user_email'])) {
  header("Location: login.html");
  exit();
}

$current_email = $_SESSION['user_email'];
$current_name = $_SESSION['user_name'];
$message = "";

// Handle POST request
if ($_SERVER["REQUEST_METHOD"] == "POST") {
  $new_name = trim($_POST['fullname']);
  $new_email = trim($_POST['email']);
  $pass1 = $_POST['password'];
  $pass2 = $_POST['confirm'];

  if (empty($new_name) || empty($new_email)) {
    $message = "Name and Email cannot be empty.";
  } elseif ($pass1 !== $pass2) {
    $message = "Passwords do not match.";
  } else {
    $new_password = password_hash($pass1, PASSWORD_DEFAULT);

    // Update user info
    $stmt = $conn->prepare("UPDATE users SET fullname=?, email=?, password=? WHERE email=?");
    $stmt->bind_param("ssss", $new_name, $new_email, $new_password, $current_email);
    if ($stmt->execute()) {
      // Update session
      $_SESSION['user_name'] = $new_name;
      $_SESSION['user_email'] = $new_email;

      header("Location: home.php");
      exit();
    } else {
      $message = "Error updating profile.";
    }
  }
}
?>
<!DOCTYPE html>
<html>
<head>
  <title>Update Profile</title>
  <link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css">
</head>
<body class="bg-light">
  <div class="container mt-5">
    <div class="row justify-content-center">
      <div class="col-md-6">
        <div class="card shadow-lg border-0">
          <div class="card-body">
            <h4 class="card-title text-center mb-4">
              <i class="fas fa-user-edit text-primary mr-2"></i> Update Profile
            </h4>

            <?php if (!empty($message)): ?>
              <div class="alert alert-danger text-center"><?php echo $message; ?></div>
            <?php endif; ?>

            <form method="post">
              <div class="form-group">
                <label for="fullname"><strong>Full Name</strong></label>
                <input type="text" class="form-control" name="fullname" id="fullname" required value="<?php echo htmlspecialchars($current_name); ?>">
              </div>
              <div class="form-group">
                <label for="email"><strong>Email Address</strong></label>
                <input type="email" class="form-control" name="email" id="email" required value="<?php echo htmlspecialchars($current_email); ?>">
              </div>
              <div class="form-group">
                <label for="password"><strong>New Password</strong></label>
                <input type="password" class="form-control" name="password" id="password" required>
              </div>
              <div class="form-group">
                <label for="confirm"><strong>Confirm Password</strong></label>
                <input type="password" class="form-control" name="confirm" id="confirm" required>
              </div>
              <div class="text-center">
                <button type="submit" class="btn btn-primary px-4 mt-2">
                  <i class="fas fa-save mr-2"></i>Update All
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
