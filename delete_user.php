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

if ($userId) {
    // Prevent deleting yourself
    $stmt = $conn->prepare("SELECT email FROM users WHERE id = ?");
    $stmt->bind_param("i", $userId);
    $stmt->execute();
    $emailToDelete = $stmt->get_result()->fetch_assoc()['email'];

    if ($emailToDelete !== $_SESSION['user_email']) {
        $stmt = $conn->prepare("DELETE FROM users WHERE id = ?");
        $stmt->bind_param("i", $userId);
        $stmt->execute();
    }
}

header("Location: dashboard.php");
exit();
