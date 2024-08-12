<?php
session_start();
include 'db_connect.php';

$message = "";

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $entered_otp = $_POST['otp'];
    $stored_otp = $_SESSION['otp'];
    $otp_expiration = $_SESSION['otp_expiration'];

    if (time() > $otp_expiration) {
        $message = "OTP has expired. Please request a new OTP.";
    } elseif ($entered_otp == $stored_otp) {
        // OTP is correct, proceed with registration
        $username = $_SESSION['username'];
        $password = $_SESSION['password'];
        $email = $_SESSION['email'];
        $profile_pic_path = $_SESSION['profile_pic_path'];

        // Insert user data into the database
        $insert_query = "INSERT INTO users (username, email, password, profile_image, type) VALUES (?, ?, ?, ?, 'user')";
        $stmt = $conn->prepare($insert_query);
        $stmt->bind_param("ssss", $username, $email, $password, $profile_pic_path);
        $stmt->execute();
        $stmt->close();

        // Clear session data
        unset($_SESSION['otp']);
        unset($_SESSION['otp_expiration']);
        unset($_SESSION['username']);
        unset($_SESSION['password']);
        unset($_SESSION['email']);
        unset($_SESSION['profile_pic_path']);

        $message = "Registration successful! You can now log in.";
        header("Location: login.php");
        exit();
    } else {
        $message = "Invalid OTP. Please try again.";
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link href="https://maxcdn.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css" rel="stylesheet">
    <title>Verify OTP</title>
    <style>
        .otp-container {
            max-width: 600px;
            margin: 50px auto;
            padding: 20px;
            border: 1px solid #ddd;
            border-radius: 5px;
            box-shadow: 0 0 10px rgba(0, 0, 0, 0.1);
        }
        .message {
            margin-bottom: 20px;
            padding: 10px;
            border: 1px solid transparent;
            border-radius: 4px;
            color: #721c24;
            background-color: #f8d7da;
            border-color: #f5c6cb;
        }
    </style>
</head>
<body>
    <?php include 'navbar.php'; ?>

    <div class="container otp-container">
        <h2 class="text-center">Verify OTP</h2>
        <?php if (!empty($message)): ?>
            <div class="message"><?php echo $message; ?></div>
        <?php endif; ?>
        <form action="verify_otp.php" method="POST">
            <div class="form-group">
                <label for="otp">Enter OTP:</label>
                <input type="text" id="otp" name="otp" class="form-control" required>
            </div>
            <button type="submit" class="btn btn-primary btn-block">Verify OTP</button>
        </form>
    </div>

    <script src="https://code.jquery.com/jquery-3.5.1.slim.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/@popperjs/core@2.10.2/dist/umd/popper.min.js"></script>
    <script src="https://maxcdn.bootstrapcdn.com/bootstrap/4.5.2/js/bootstrap.min.js"></script>
</body>
</html>
