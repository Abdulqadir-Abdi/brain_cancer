<?php
session_start();

$host = "localhost";
$user = "root";
$password = "";
$dbname = "brain_cancer_db";

// Connect to database
$conn = new mysqli($host, $user, $password, $dbname);
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Get login input
$email = $_POST['email'] ?? '';
$password_input = $_POST['password'] ?? '';

// Validate form input
if (empty($email) || empty($password_input)) {
    echo "<script>alert('Please enter both email and password.'); window.location.href='login.html';</script>";
    exit();
}

// Check if user exists
$sql = "SELECT * FROM users WHERE email = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("s", $email);
$stmt->execute();
$result = $stmt->get_result();

// Found user
if ($result->num_rows === 1) {
    $user = $result->fetch_assoc();
    $stored_password = $user['password'];

    // ✅ Password check: handle both hashed and plain text passwords
    $valid = password_verify($password_input, $stored_password) || $password_input === $stored_password;

    if ($valid) {
        // ✅ Set session
        $_SESSION['user_id'] = $user['id'];
        $_SESSION['user_email'] = $user['email'];
        $_SESSION['user_name'] = $user['fullname'];
        $_SESSION['role'] = $user['role'];
        $_SESSION['admin_code'] = $user['admin_code'] ?? null;
        $_SESSION['profile_image'] = $user['profile_image'];

        // ✅ Define the role variable before using it
        $role = $user['role'];

        // ✅ Redirect based on role
        if ($role === 'admin' || $role === 'small-admi') {
            header("Location: dashboard.php");
        } elseif ($role === 'user') {
            header("Location: analyze.php");
        } else {
            echo "<script>alert('Your role is not allowed to login.'); window.location.href='login.html';</script>";
        }
        exit();
    } else {
        echo "<script>alert('Invalid password'); window.location.href='login.html';</script>";
    }
} else {
    echo "<script>alert('Account not found'); window.location.href='login.html';</script>";
}

$stmt->close();
$conn->close();
?>
