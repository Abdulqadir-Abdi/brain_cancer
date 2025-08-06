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
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Invalid Image | Brain Scan Analysis</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        :root {
            --error-color: #ff4444;
            --warning-color: #ffbb33;
            --light-bg: #f8f9fa;
        }
        
        body {
            background: linear-gradient(135deg, #f5f7fa 0%, #dfe3e8 100%);
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        
        .error-card {
            border: none;
            border-radius: 12px;
            box-shadow: 0 8px 20px rgba(0, 0, 0, 0.1);
            overflow: hidden;
            max-width: 450px;
            transition: transform 0.3s ease;
            text-align: center;
            border-top: 4px solid var(--error-color);
        }
        
        .error-card:hover {
            transform: translateY(-5px);
        }
        
        .error-icon {
            font-size: 3.5rem;
            color: var(--error-color);
            margin-bottom: 1rem;
            animation: pulse 1.5s infinite;
        }
        
        @keyframes pulse {
            0% { transform: scale(1); }
            50% { transform: scale(1.1); }
            100% { transform: scale(1); }
        }
        
        .btn-home {
            background: var(--warning-color);
            color: #333;
            border: none;
            padding: 0.5rem 1.75rem;
            border-radius: 50px;
            font-weight: 600;
            transition: all 0.3s;
            box-shadow: 0 4px 8px rgba(255, 187, 51, 0.2);
        }
        
        .btn-home:hover {
            background: #ffff00ff;
            transform: translateY(-2px);
            box-shadow: 0 6px 12px rgba(255, 187, 51, 0.3);
            color: #333;
        }
        
        .error-details {
            background: rgba(254, 96, 11, 0.05);
            border-radius: 8px;
            padding: 1rem;
            margin: 1.5rem 0;
        }
    </style>
</head>
<body>
    <div class="error-card p-4">
        <div class="error-icon">
            <i class="fas fa-exclamation-triangle"></i>
        </div>
        <h3 class="mb-3">Invalid Image Detected</h3>
        
        <div class="error-details">
            <p class="mb-2">The uploaded Mri could not be recognized as a valid</p>
            <p class="fw-bold mb-0">Brain MRI Scan</p>
        </div>
        
        <p class="text-muted small mb-4">
            Please upload a clear MRI scan image in JPG, PNG,
        </p>
        
        <a href="predict.php" class="btn btn-home">
            <i class="fas fa-arrow-left me-2"></i>Return to prediction Form
        </a>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
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
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Diagnosis Report</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        body {
            background: #f8f9fa;
            padding: 20px;
            font-family: 'Segoe UI', sans-serif;
        }
        .diagnosis-card {
            max-width: 400px;
            margin: 0 auto;
            border-radius: 12px;
            overflow: hidden;
            box-shadow: 0 4px 15px rgba(0,0,0,0.1);
            border: none;
        }
        .card-header {
            background: #4361ee;
            color: white;
            padding: 15px;
            text-align: center;
            position: relative;
        }
        .card-header::after {
            content: "";
            position: absolute;
            bottom: -20px;
            right: -20px;
            width: 60px;
            height: 60px;
            background: rgba(255,255,255,0.1);
            border-radius: 50%;
        }
        .diagnosis-badge {
            padding: 6px 15px;
            border-radius: 20px;
            font-weight: 600;
            font-size: 0.9rem;
            display: inline-block;
            margin: 8px 0;
        }
        .tumor { background: #f72585; }
        .no-tumor { background: #4cc9f0; }
        .scan-img {
            max-width: 100%;
            border-radius: 8px;
            border: 2px solid #eee;
            margin: 10px 0;
        }
        .patient-info {
            background: #f1f3f5;
            border-radius: 8px;
            padding: 12px;
            margin: 12px 0;
        }
        .info-item {
            margin-bottom: 6px;
            font-size: 0.9rem;
        }
        .info-icon {
            width: 20px;
            color: #4361ee;
            margin-right: 8px;
            text-align: center;
        }
        .btn-back {
            background: #4361ee;
            border: none;
            padding: 8px 20px;
            border-radius: 20px;
            font-size: 0.9rem;
        }
        .timestamp {
            font-size: 0.8rem;
            color: #6c757d;
        }
    </style>
</head>
<body>
    <div class="diagnosis-card">
        <div class="card-header">
            <h5 class="mb-2"><i class="fas fa-diagnosis me-2"></i>MRI Diagnosis</h5>
            <div class="diagnosis-badge <?php echo (strpos(strtolower($prediction), 'no tumor') === false) ? 'tumor' : 'no-tumor'; ?>">
                <?php echo htmlspecialchars($prediction); ?>
            </div>
        </div>
        
        <div class="card-body p-3">
            <div class="text-center mb-3">
                <img src="<?php echo htmlspecialchars($target_file); ?>" alt="MRI Scan" class="scan-img">
                <p class="text-muted mb-2 small">Uploaded MRI Scan</p>
            </div>
            
            <!-- <div class="patient-info">
                <h6 class="mb-2"><i class="fas fa-user-circle me-2"></i>Patient Details</h6>
                <div class="info-item d-flex">
                    <div class="info-icon"><i class="fas fa-user"></i></div>
                    <div><?php echo htmlspecialchars($fullname); ?></div>
                </div>
                <div class="info-item d-flex">
                    <div class="info-icon"><i class="fas fa-venus-mars"></i></div>
                    <div><?php echo htmlspecialchars($sex); ?>, <?php echo htmlspecialchars($age); ?></div>
                </div>
                <div class="info-item d-flex">
                    <div class="info-icon"><i class="fas fa-phone"></i></div>
                    <div><?php echo htmlspecialchars($phone); ?></div>
                </div>
            </div> -->
            
            <div class="text-center mt-3">
                <a href="dashboard.php" class="btn btn-back btn-sm text-white">
                    <i class="fas fa-arrow-left me-1"></i>Back to Dashboard
                </a>
                <p class="timestamp mt-2">
                    <i class="fas fa-clock me-1"></i><?php echo date('M j, Y g:i A'); ?>
                </p>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
<?php
}
?>
