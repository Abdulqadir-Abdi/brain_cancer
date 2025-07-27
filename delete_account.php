<?php
session_start();
$conn = new mysqli("localhost", "root", "", "brain_cancer_db");

if (!isset($_SESSION['user_email'])) {
  header("Location: login.html");
  exit();
}

$email = $_SESSION['user_email'];

// Delete user from the database
$stmt = $conn->prepare("DELETE FROM users WHERE email = ?");
$stmt->bind_param("s", $email);
if ($stmt->execute()) {
  session_unset();
  session_destroy();
  header("Location: goodbye.html"); // Optional goodbye page
  exit();
} else {
  echo "Failed to delete account.";
}
?>
