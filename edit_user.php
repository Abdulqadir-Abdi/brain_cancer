<?php
session_start();
if (!isset($_SESSION['user_email']) || ($_SESSION['role'] !== 'small-admin' && $_SESSION['role'] !== 'small-admi')) {
    header("Location: login.html");
    exit();
}

$conn = new mysqli("localhost", "root", "", "brain_cancer_db");
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

$userId = $_GET['id'] ?? null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $fullname = $_POST['fullname'];
    $email = $_POST['email'];
    $role = $_POST['role'];

    $stmt = $conn->prepare("UPDATE users SET fullname = ?, email = ?, role = ? WHERE id = ?");
    $stmt->bind_param("sssi", $fullname, $email, $role, $userId);
    $stmt->execute();

    header("Location: dashboard.php");
    exit();
}

// Get user info
$stmt = $conn->prepare("SELECT fullname, email, role FROM users WHERE id = ?");
$stmt->bind_param("i", $userId);
$stmt->execute();
$result = $stmt->get_result();
$user = $result->fetch_assoc();
?>

<!DOCTYPE html>
<html>
<head>
  <title>Edit User</title>
  <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css">
</head>
<body class="bg-light" style="padding-top: 80px;">
<div class="container">
  <h2>Edit User</h2>
  <form method="POST" class="bg-white p-4 shadow-sm rounded">
    <div class="form-group">
      <label>Full Name</label>
      <input type="text" name="fullname" class="form-control" value="<?= htmlspecialchars($user['fullname']) ?>" required>
    </div>
    <div class="form-group">
      <label>Email</label>
      <input type="email" name="email" class="form-control" value="<?= htmlspecialchars($user['email']) ?>" required>
    </div>
    <div class="form-group">
      <label>Role</label>
      <select name="role" class="form-control">
        <option value="user" <?= $user['role'] === 'user' ? 'selected' : '' ?>>User</option>
        <option value="small-admin" <?= $user['role'] === 'small-admin' ? 'selected' : '' ?>>Small Admin</option>
      </select>
    </div>
    <button type="submit" class="btn btn-primary">Save Changes</button>
    <a href="dashboard.php" class="btn btn-secondary">Cancel</a>
  </form>
</div>
</body>
</html>
