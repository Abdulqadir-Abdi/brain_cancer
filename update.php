<?php
session_start();
$logged_in = isset($_SESSION['user_email']);
$is_admin = $logged_in && isset($_SESSION['role']) && $_SESSION['role'] === 'admin';


$conn = new mysqli("localhost", "root", "", "brain_cancer_db");

if (isset($_GET['id'])) {
  $id = intval($_GET['id']);

  // Fetch the current record to edit
  $result = $conn->query("SELECT * FROM predictions WHERE id = $id");
  if ($result->num_rows == 0) {
    echo "<p>Record not found.</p>";
    exit();
  }
  $row = $result->fetch_assoc();
}

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
  // Get updated data (excluding prediction)
  $fullname = $_POST['fullname'];
  $age = $_POST['age'];
  $phone = $_POST['phone'];
  $sex = $_POST['sex'];
  $id = $_POST['id'];

  // Update the record in the database
  $stmt = $conn->prepare("UPDATE predictions SET fullname = ?, age = ?, phone = ?, sex = ? WHERE id = ?");
  $stmt->bind_param("sissi", $fullname, $age, $phone, $sex, $id);
  $stmt->execute();
  $stmt->close();
  ?>
  <!DOCTYPE html>
  <html lang="en">
  <head>
    <meta charset="UTF-8">
    <title>Update Successful</title>
    <link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css">
    <script src="https://code.jquery.com/jquery-3.5.1.slim.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@4.5.2/dist/js/bootstrap.bundle.min.js"></script>
  </head>
  <body>

  <!-- Modal -->
  <div class="modal fade show" id="successModal" tabindex="-1" role="dialog" aria-labelledby="modalLabel" style="display: block;" aria-modal="true">
    <div class="modal-dialog modal-dialog-centered" role="document">
      <div class="modal-content border-success">
        <div class="modal-header bg-success text-white">
          <h5 class="modal-title" id="modalLabel">✅ Update Successful</h5>
        </div>
        <div class="modal-body text-center">
          <p>The record was updated successfully.</p>
          <a href="report.php" class="btn btn-success">Back to Report</a>
        </div>
      </div>
    </div>
  </div>

  <!-- Modal backdrop -->
  <div class="modal-backdrop fade show"></div>

  </body>
  </html>
  <?php
  exit();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Edit Prediction Record</title>
  <link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css">
</head>
<body>

<nav class="navbar navbar-expand-lg navbar-light bg-light">
  <a class="navbar-brand" href="#">Brain Cancer Detection</a>
  <div class="collapse navbar-collapse">
    <ul class="navbar-nav ml-auto">
      <li class="nav-item"><a class="nav-link" href="home.php">Home</a></li>
      <li class="nav-item"><a class="nav-link" href="predict.php">Prediction</a></li>
      <li class="nav-item"><a class="nav-link" href="report.php">Report</a></li>
      <li class="nav-item"><a class="nav-link" href="about.php">About</a></li>
    </ul>
  </div>
</nav>

<div class="container mt-5">
  <h2>Edit Prediction Record</h2>

  <form method="POST">
    <input type="hidden" name="id" value="<?= htmlspecialchars($row['id']) ?>">

    <div class="form-group">
      <label>Full Name</label>
      <input type="text" name="fullname" class="form-control" value="<?= htmlspecialchars($row['fullname']) ?>" required>
    </div>

    <div class="form-group">
      <label>Age</label>
      <input type="number" name="age" class="form-control" value="<?= htmlspecialchars($row['age']) ?>" min="1" required>
    </div>

    <div class="form-group">
      <label>Phone Number</label>
      <input type="tel" name="phone" class="form-control" value="<?= htmlspecialchars($row['phone']) ?>" required>
    </div>

    <div class="form-group">
      <label>Gender</label>
      <select name="sex" class="form-control" required>
        <option value="Male" <?= ($row['sex'] == 'Male') ? 'selected' : '' ?>>Male</option>
        <option value="Female" <?= ($row['sex'] == 'Female') ? 'selected' : '' ?>>Female</option>
      </select>
    </div>

    <div class="form-group">
      <label>Prediction</label>
      <input type="text" class="form-control" value="<?= htmlspecialchars($row['prediction']) ?>" readonly disabled>
    </div>

    <button type="submit" class="btn btn-primary btn-block">Update Record</button>
  </form>
</div>

</body>
</html>
