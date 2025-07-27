<?php
// ✅ Database connection
$host = "localhost";
$user = "root";
$password = "";
$dbname = "brain_cancer_db";

$conn = mysqli_connect($host, $user, $password, $dbname);

if (!$conn) {
    die("Connection failed: " . mysqli_connect_error());
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Create Account - Brain Prediction</title>
  <meta name="viewport" content="width=device-width, initial-scale=1">
  
  <!-- Bootstrap 5 -->
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
  
  <!-- FontAwesome -->
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css"/>
  
  <style>
    body {
      background-color: #f8f9fa;
    }

    .card {
      max-width: 550px;
      margin: 60px auto;
      border: none;
      border-radius: 15px;
      box-shadow: 0 0 15px rgba(0,0,0,0.1);
    }

    .card-img-top {
      height: 230px;
      object-fit: cover;
      border-top-left-radius: 15px;
      border-top-right-radius: 15px;
    }

    .avatar-icon {
      width: 75px;
      height: 75px;
      background-color: #fff;
      border: 3px solid #0d6efd;
      border-radius: 50%;
      display: flex;
      justify-content: center;
      align-items: center;
      margin: -38px auto 10px auto;
      box-shadow: 0 4px 10px rgba(0,0,0,0.15);
    }

    .avatar-icon i {
      font-size: 28px;
      color: #0d6efd;
    }

    .btn-primary {
      font-weight: bold;
    }

    .form-label {
      font-weight: 500;
    }
  </style>
</head>
<body>

  <div class="card">
    <img src="brain-banner.jpg" class="card-img-top" alt="Brain Scan Banner">
    <div class="avatar-icon">
      <i class="fas fa-user-plus"></i>
    </div>
    <div class="card-body px-4">
      <h4 class="text-center mb-4 fw-bold">Create Account</h4>

      <form method="POST" action="register.php">

        <div class="mb-3">
          <label for="fullname" class="form-label">Full Name</label>
          <input type="text" class="form-control" name="fullname" id="fullname" required>
        </div>

        <div class="mb-3">
          <label for="email" class="form-label">Email Address</label>
          <input type="email" class="form-control" name="email" id="email" required>
        </div>

        <div class="mb-3">
          <label for="password" class="form-label">Password</label>
          <input type="password" class="form-control" name="password" id="password" required>
        </div>

        <div class="mb-3">
          <label for="confirm_password" class="form-label">Confirm Password</label>
          <input type="password" class="form-control" name="confirm_password" id="confirm_password" required>
        </div>

        <div class="mb-4">
          <label for="managed_by" class="form-label">Select Your Hospital</label>
          <select name="managed_by" id="managed_by" class="form-select" required>
            <option selected disabled value="">-- Choose Hospital --</option>
            <?php
            $query = "SELECT admin_code FROM users WHERE role='small-admi' AND admin_code IS NOT NULL";
            $result = mysqli_query($conn, $query);
            if (!$result) {
              echo "<option disabled>Error: " . mysqli_error($conn) . "</option>";
            } elseif (mysqli_num_rows($result) === 0) {
              echo "<option disabled>No small-admins found</option>";
            } else {
              while ($row = mysqli_fetch_assoc($result)) {
                echo "<option value='" . htmlspecialchars($row['admin_code']) . "'>" .
                     htmlspecialchars($row['admin_code']) . "</option>";
              }
            }
            ?>
          </select>
        </div>

        <div class="d-grid">
          <button type="submit" class="btn btn-primary py-2">Sign Up</button>
        </div>

        <p class="text-center mt-3 mb-0">
          Already have an account? <a href="login.html">Log in here</a>
        </p>
      </form>
    </div>
  </div>

</body>
</html>
