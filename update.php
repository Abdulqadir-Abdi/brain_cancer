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
  <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;700&display=swap" rel="stylesheet">
  <style>
    body {
      background: linear-gradient(135deg, #f8f9fa 60%, #e6e6fa 100%);
      min-height: 100vh;
      font-family: 'Poppins', Arial, sans-serif;
      color: #333;
    }
    .edit-card {
      max-width: 500px;
      margin: 60px auto;
      border: none;
      border-radius: 1.5rem;
      box-shadow: 0 0 24px rgba(59,10,133,0.10);
      background: #fff;
      padding: 2.5rem 2rem 2rem 2rem;
    }
    h2 {
      font-family: 'Poppins', Arial, sans-serif;
      font-weight: 800;
      color: #3b0a85;
      letter-spacing: 1px;
      text-align: center;
      margin-bottom: 2rem;
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
      .edit-card { padding: 1.2rem 0.5rem; max-width: 98vw; }
      h2 { font-size: 1.2rem; }
      .form-control, .form-select { font-size: 1rem; padding: 0.7rem 0.7rem; }
    }
  </style>
</head>
<body>

<div class="edit-card">
  <h2>Edit Prediction Record</h2>
  <form method="POST">
    <input type="hidden" name="id" value="<?= htmlspecialchars($row['id']) ?>">

    <div class="mb-3">
      <label class="form-label">Full Name</label>
      <input type="text" name="fullname" class="form-control" value="<?= htmlspecialchars($row['fullname']) ?>" required>
    </div>

    <div class="mb-3">
      <label class="form-label">Age</label>
      <input type="number" name="age" class="form-control" value="<?= htmlspecialchars($row['age']) ?>" min="1" required>
    </div>

    <div class="mb-3">
      <label class="form-label">Phone Number</label>
      <input type="tel" name="phone" class="form-control" value="<?= htmlspecialchars($row['phone']) ?>" required>
    </div>

    <div class="mb-3">
      <label class="form-label">Gender</label>
      <select name="sex" class="form-select" required>
        <option value="Male" <?= ($row['sex'] == 'Male') ? 'selected' : '' ?>>Male</option>
        <option value="Female" <?= ($row['sex'] == 'Female') ? 'selected' : '' ?>>Female</option>
      </select>
    </div>

    <div class="mb-3">
      <label class="form-label">Prediction</label>
      <input type="text" class="form-control" value="<?= htmlspecialchars($row['prediction']) ?>" readonly disabled>
    </div>

    <button type="submit" class="btn btn-primary">Update Record</button>
  </form>
</div>

</body>
</html>
