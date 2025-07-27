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

// Get form inputs
$fullname = $_POST['fullname'] ?? '';
$email = $_POST['email'] ?? '';
$password = $_POST['password'] ?? '';
$confirm_password = $_POST['confirm_password'] ?? '';
$managed_by = $_POST['managed_by'] ?? '';

// Check password match
if ($password !== $confirm_password) {
    showAlert('warning', 'Password Mismatch', 'Passwords do not match.', 'signup.php');
    exit();
}

// Check for existing email
$sql = "SELECT * FROM users WHERE email=?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("s", $email);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows > 0) {
    showAlert('error', 'Email Already Exists', 'This email is already registered.', 'signup.php');
    exit();
}

// Hash password and insert new user with managed_by and default role
$hashed_password = password_hash($password, PASSWORD_DEFAULT);
$role = 'user';

$sql = "INSERT INTO users (fullname, email, password, role, managed_by) VALUES (?, ?, ?, ?, ?)";
$stmt = $conn->prepare($sql);
$stmt->bind_param("sssss", $fullname, $email, $hashed_password, $role, $managed_by);

if ($stmt->execute()) {
    showAlert('success', 'Registration Successful', 'You can now log in.', 'login.html');
} else {
    showAlert('error', 'Registration Failed', 'An error occurred while registering.', 'signup.php');
}

$stmt->close();
$conn->close();


// ✅ Reusable SweetAlert2 function wrapped in valid HTML
function showAlert($icon, $title, $text, $redirect) {
    echo "
    <!DOCTYPE html>
    <html>
    <head>
        <meta charset='UTF-8'>
        <title>Redirecting...</title>
        <script src='https://cdn.jsdelivr.net/npm/sweetalert2@11'></script>
    </head>
    <body>
        <script>
            Swal.fire({
                icon: '$icon',
                title: '$title',
                text: '$text',
                confirmButtonColor: '#3085d6'
            }).then(() => {
                window.location.href = '$redirect';
            });
        </script>
    </body>
    </html>";
}
?>
