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
      background: linear-gradient(135deg, #f8f9fa 60%, #e6e6fa 100%);
      min-height: 100vh;
      font-family: 'Poppins', Arial, sans-serif;
      color: #333;
    }
    .card {
      max-width: 500px;
      margin: 60px auto;
      border: none;
      border-radius: 1.5rem;
      box-shadow: 0 0 24px rgba(59,10,133,0.10);
      background: #fff;
    }
    .card-img-top {
      height: 200px;
      object-fit: cover;
      border-top-left-radius: 1.5rem;
      border-top-right-radius: 1.5rem;
    }
    .avatar-icon {
      width: 75px;
      height: 75px;
      background-color: #fff;
      border: 3px solid #3b0a85;
      border-radius: 50%;
      display: flex;
      justify-content: center;
      align-items: center;
      margin: -38px auto 10px auto;
      box-shadow: 0 4px 10px rgba(59,10,133,0.10);
    }
    .avatar-icon i {
      font-size: 2rem;
      color: #3b0a85;
    }
    h4 {
      font-family: 'Poppins', Arial, sans-serif;
      font-weight: 800;
      color: #3b0a85;
      letter-spacing: 1px;
    }
    .form-label {
      font-weight: 600;
      font-size: 1.1rem;
      font-family: 'Poppins', Arial, sans-serif;
    }
    .form-control, .form-select {
      font-size: 1.1rem;
      padding: 0.85rem 1rem;
      border-radius: 1rem;
      margin-bottom: 1rem;
      font-family: 'Poppins', Arial, sans-serif;
    }
    .btn-primary {
      background: linear-gradient(90deg, #3b0a85, #5f2db4);
      color: #fff;
      border: none;
      font-size: 1.15rem;
      font-weight: 700;
      padding: 0.9rem 0;
      border-radius: 2rem;
      transition: background 0.3s, transform 0.2s;
      box-shadow: 0 2px 12px rgba(59,10,133,0.10);
      letter-spacing: 1px;
      width: 100%;
      font-family: 'Poppins', Arial, sans-serif;
    }
    .btn-primary:hover {
      background: linear-gradient(90deg, #5f2db4, #3b0a85);
      color: #fff;
      transform: translateY(-2px) scale(1.04);
    }
    p, label, input, select, button {
      font-family: 'Poppins', Arial, sans-serif;
    }
    @media (max-width: 767px) {
      .card { padding: 1.2rem 0.5rem; max-width: 98vw; }
      .card-img-top { height: 120px; }
      h4 { font-size: 1.2rem; }
      .form-control, .form-select { font-size: 1rem; padding: 0.7rem 0.7rem; }
    }
  </style>
  <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;700&display=swap" rel="stylesheet">
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
