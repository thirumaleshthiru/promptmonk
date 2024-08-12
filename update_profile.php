<?php
session_start();
include 'db_connect.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    echo "You need to log in first.";
    exit();
}

$user_id = $_SESSION['user_id'];
$message = '';

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $new_name = $_POST['username'];
    $current_image_path = $_POST['current_image'];

    // Update name
    if (!empty($new_name)) {
        $query = "UPDATE users SET username = ? WHERE id = ?";
        $stmt = $conn->prepare($query);
        if (!$stmt) {
            $message = "Error preparing statement: " . $conn->error;
        } else {
            $stmt->bind_param("si", $new_name, $user_id);
            if ($stmt->execute()) {
                $message = "Profile updated successfully!";
            } else {
                $message = "Error updating profile name: " . $stmt->error;
            }
            $stmt->close();
        }
    }

    // Handle profile image upload
    if (isset($_FILES['profile_image']) && $_FILES['profile_image']['error'] == 0) {
        // Remove existing profile image
        if ($current_image_path && file_exists($current_image_path)) {
            unlink($current_image_path);
        }

        $target_dir = "uploads/";
        $target_file = $target_dir . basename($_FILES["profile_image"]["name"]);
        $uploadOk = 1;
        $imageFileType = strtolower(pathinfo($target_file, PATHINFO_EXTENSION));

        // Check if image file is a actual image
        $check = getimagesize($_FILES["profile_image"]["tmp_name"]);
        if ($check === false) {
            $uploadOk = 0;
            $message = "File is not an image.";
        }

        // Check file size
        if ($_FILES["profile_image"]["size"] > 500000) {
            $uploadOk = 0;
            $message = "Sorry, your file is too large.";
        }

        // Allow certain file formats
        if ($imageFileType != "jpg" && $imageFileType != "png" && $imageFileType != "jpeg" && $imageFileType != "gif") {
            $uploadOk = 0;
            $message = "Sorry, only JPG, JPEG, PNG & GIF files are allowed.";
        }

        if ($uploadOk == 0) {
            $message = "Sorry, your file was not uploaded.";
        } else {
            if (move_uploaded_file($_FILES["profile_image"]["tmp_name"], $target_file)) {
                $query = "UPDATE users SET profile_image = ? WHERE id = ?";
                $stmt = $conn->prepare($query);
                if (!$stmt) {
                    $message = "Error preparing statement: " . $conn->error;
                } else {
                    $stmt->bind_param("si", $target_file, $user_id);
                    if ($stmt->execute()) {
                        $message = "Profile updated successfully!";
                    } else {
                        $message = "Error updating profile image: " . $stmt->error;
                    }
                    $stmt->close();
                }
            } else {
                $message = "Sorry, there was an error uploading your file.";
            }
        }
    }
}

// Fetch current user details
$query = "SELECT username, profile_image FROM users WHERE id = ?";
$stmt = $conn->prepare($query);
if (!$stmt) {
    die("Prepare failed: " . $conn->error);
}
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();
$user = $result->fetch_assoc();
$stmt->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link href="https://maxcdn.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css" rel="stylesheet">
    <title>Update Profile</title>
    <style>
        body {
            background-color: #F7EFE5;
            color: #333;
            
        }
        .update-profile-container {
            max-width: 600px;
            margin: 50px auto;
            padding: 20px;
            background-color: #fff;
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
        }
        .form-group {
            margin-bottom: 1.5rem;
        }
        .form-control {
            border-radius: 0.25rem;
        }
        .btn-primary {
            background-color: #674188;
            border-color: #674188;
        }
        .btn-primary:hover {
            background-color: #C8A1E0;
            border-color: #C8A1E0;
        }
        .alert {
            margin-bottom: 1rem;
            color: #674188;
            background-color: #E2BFD9;
            border-color: #E2BFD9;
        }
        .alert-info {
            background-color: #E2BFD9;
            border-color: #E2BFD9;
        }
    </style>
</head>
<body>
    <?php include 'navbar.php'; ?>

    <div class="container update-profile-container">
        <h2>Update Profile</h2>

        <?php if ($message): ?>
            <div class="alert alert-info">
                <?php echo htmlspecialchars($message); ?>
            </div>
        <?php endif; ?>

        <form action="" method="post" enctype="multipart/form-data">
            <div class="form-group">
                <label for="username">Name</label>
                <input type="text" id="username" name="username" class="form-control" value="<?php echo htmlspecialchars($user['username']); ?>" required>
            </div>
            <div class="form-group">
                <label for="profile_image">Profile Image</label>
                <input type="file" id="profile_image" name="profile_image" class="form-control-file">
                <input type="hidden" name="current_image" value="<?php echo htmlspecialchars($user['profile_image']); ?>">
            </div>
            <button type="submit" class="btn btn-primary">Update Profile</button>
        </form>
    </div>

    <script src="https://code.jquery.com/jquery-3.5.1.slim.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/@popperjs/core@2.10.2/dist/umd/popper.min.js"></script>
    <script src="https://maxcdn.bootstrapcdn.com/bootstrap/4.5.2/js/bootstrap.min.js"></script>
</body>
</html>
