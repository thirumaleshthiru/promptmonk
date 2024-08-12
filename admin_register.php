<?php
session_start();
include 'db_connect.php';

$message = "";

 

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $username = $_POST['username'];
    $password = password_hash($_POST['password'], PASSWORD_BCRYPT);
    $email = $_POST['email'];

    // Handle profile image upload
    $profile_pic_path = null;
    if (isset($_FILES['profile_pic']) && $_FILES['profile_pic']['error'] === UPLOAD_ERR_OK) {
        $profile_pic_name = basename($_FILES['profile_pic']['name']);
        $profile_pic_temp_path = $_FILES['profile_pic']['tmp_name'];
        $profile_pic_path = 'uploads/' . $profile_pic_name;

        // Create 'uploads' directory if not exists
        if (!is_dir('uploads')) {
            mkdir('uploads', 0755, true);
        }

        // Move the uploaded file to the 'uploads' directory
        if (!move_uploaded_file($profile_pic_temp_path, $profile_pic_path)) {
            $message = "Failed to upload profile picture.";
        }
    }

    // Check if username or email already exists
    $check_existing_query = "SELECT * FROM users WHERE username = ? OR email = ?";
    $stmt = $conn->prepare($check_existing_query);
    $stmt->bind_param("ss", $username, $email);
    $stmt->execute();
    $result = $stmt->get_result();
    $stmt->close();

    if ($result->num_rows > 0) {
        $message = "Username or email already registered. Please use a different username or email.";
    } else {
        // Insert user data into the database with pending_verification set to true
        $insert_query = "INSERT INTO users (username, email, password, profile_image, type, pending_verification) VALUES (?, ?, ?, ?, 'admin', TRUE)";
        $stmt = $conn->prepare($insert_query);
        $stmt->bind_param("ssss", $username, $email, $password, $profile_pic_path);
        $stmt->execute();
        $stmt->close();

        $message = "Admin registration successful.";
        header("Location: admin_dashboard.php"); // Redirect to admin dashboard or any other page
        exit();
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link href="https://maxcdn.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css" rel="stylesheet">
    <title>Admin Register</title>
    <style>
       body {
            background-color: #F7EFE5;
            color: #674188;
        }
        .register-container {
            max-width: 600px;
            margin: 50px auto;
            padding: 20px;
            border: 1px solid #ddd;
            border-radius: 5px;
            box-shadow: 0 0 10px rgba(0, 0, 0, 0.1);
            background-color: #FFFFFF;
        }
        .message {
            margin-bottom: 20px;
            padding: 10px;
            border: 1px solid transparent;
            border-radius: 4px;
            color: #674188;
            background-color: #E2BFD9;
            border-color: #E2BFD9;
        }
        .btn-primary {
            background-color: #674188;
            border-color: #674188;
        }
        .btn-primary:hover {
            background-color: #5a367d;
            border-color: #5a367d;
        }
    </style>
</head>
<body>
    <?php include 'navbar.php'; ?>

    <div class="container register-container">
        <h2 class="text-center">Admin Register</h2>
        <?php if (!empty($message)): ?>
            <div class="message"><?php echo $message; ?></div>
        <?php endif; ?>
        <form action="admin_register.php" method="POST" enctype="multipart/form-data">
            <div class="form-group">
                <label for="username">Username:</label>
                <input type="text" id="username" name="username" class="form-control" required>
            </div>
            <div class="form-group">
                <label for="password">Password:</label>
                <input type="password" id="password" name="password" class="form-control" required>
            </div>
            <div class="form-group">
                <label for="email">Email:</label>
                <input type="email" id="email" name="email" class="form-control" required>
            </div>
            <div class="form-group">
                <label for="profile_pic">Profile Image:</label>
                <input type="file" id="profile_pic" name="profile_pic" class="form-control-file">
            </div>
            <button type="submit" class="btn btn-primary btn-block">Register</button>
        </form>
    </div>

    <script src="https://code.jquery.com/jquery-3.5.1.slim.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/@popperjs/core@2.10.2/dist/umd/popper.min.js"></script>
    <script src="https://maxcdn.bootstrapcdn.com/bootstrap/4.5.2/js/bootstrap.min.js"></script>
</body>
</html>
