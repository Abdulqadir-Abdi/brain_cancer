<?php
session_start();
if (!isset($_SESSION['user_email'])) {
    header("Location: login.html");
    exit();
}


$conn = new mysqli("localhost", "root", "", "brain_cancer_db");

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_FILES['image'])) {
    $fullname = $_POST['fullname'];
    $age = $_POST['age'];
    $phone = $_POST['phone'];
    $sex = $_POST['sex'];

    $target_dir = "uploads/";
    if (!is_dir($target_dir)) {
        mkdir($target_dir, 0777, true);
    }

    $target_file = $target_dir . basename($_FILES['image']['name']);
    move_uploaded_file($_FILES['image']['tmp_name'], $target_file);

    $image_path = realpath($target_file);
    $api_url = 'http://127.0.0.1:5000/predict';

    $curl = curl_init();
    curl_setopt_array($curl, array(
        CURLOPT_URL => $api_url,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_POST => true,
        CURLOPT_POSTFIELDS => array('file' => new CURLFile($image_path))
    ));

    $response = curl_exec($curl);
    curl_close($curl);

    if ($response === false) {
        echo "<p>❌ Failed to connect to Flask API.</p>";
        exit();
    }

    $result = json_decode($response, true);

    // Reject non-MRI scans and stop early
    if (!isset($result['result']) || $result['result'] !== 'Brain MRI Scan') {
        ?>
        <!DOCTYPE html>
        <html lang="en">
        <head>
            <meta charset="UTF-8">
            <title>Invalid Image</title>
            <link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css">
        </head>
        <body class="bg-light d-flex align-items-center justify-content-center" style="min-height: 100vh;">
        <div class="card p-4 shadow" style="max-width: 500px;">
            <h4 class="mb-3">🚫 Invalid Image</h4>
            <p>This image is <strong>not recognized as a Brain MRI Scan</strong>.</p>
            <a href="home.php" class="btn btn-warning mt-3">Back to Home</a>
        </div>
        </body>
        </html>
        <?php
        // Optionally delete uploaded non-MRI image
        if (file_exists($target_file)) {
            unlink($target_file);
        }
        exit();
    }

    // Proceed if MRI is valid
    $prediction = $result['diagnosis'];  // Must exist if valid
    $user_email = $_SESSION['user_email'];  
    $stmt = $conn->prepare("INSERT INTO predictions (fullname, age, phone, sex, prediction, image_path, user_email, created_at) VALUES (?, ?, ?, ?, ?, ?, ?, NOW())");
    $stmt->bind_param("sisssss", $fullname, $age, $phone, $sex, $prediction, $target_file, $user_email);
    $stmt->execute();
    $stmt->close();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Prediction Result</title>
    <link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css">
    <style>
        body {
            background-color: #f8f9fa;
        }
        .result-container {
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        .result-box {
            background: white;
            padding: 30px;
            border-radius: 10px;
            box-shadow: 0 4px 10px rgba(0,0,0,0.1);
            text-align: center;
            max-width: 500px;
        }
        img {
            width: 100%;
            max-width: 300px;
            margin-top: 20px;
            border-radius: 10px;
        }
    </style>
</head>
<body>

<div class="container result-container">
    <div class="result-box">
        <h2 class="mb-4">Prediction Result</h2>
        <p><strong>Diagnosis:</strong> <?php echo htmlspecialchars($prediction); ?></p>
        <p><strong>Uploaded Image:</strong></p>
        <img src="<?php echo htmlspecialchars($target_file); ?>" alt="Uploaded Image">
        <br><br>
        <a href="home.php" class="btn btn-primary mt-3">Back to Home</a>
    </div>
</div>

</body>
</html>
<?php
}
?>
