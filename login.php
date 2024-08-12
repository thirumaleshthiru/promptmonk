<?php
session_start();
include 'db_connect.php';


$message = "";
if (isset($_SESSION['user_id'])) {
    $user_role = $_SESSION['user_role'];
    header("Location: " . ($user_role === 'admin' ? 'admin_dashboard.php' : 'dashboard.php'));
    exit();
}

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $username_or_email = $_POST['username_or_email'];
    $password = $_POST['password'];

    // Check if the user exists
    $query = "SELECT * FROM users WHERE (username = ? OR email = ?) AND pending_verification = 1";
    $stmt = $conn->prepare($query);
    $stmt->bind_param("ss", $username_or_email, $username_or_email);
    $stmt->execute();
    $result = $stmt->get_result();
    $stmt->close();

    if ($result->num_rows === 1) {
        $user = $result->fetch_assoc();

        // Verify the password
        if (password_verify($password, $user['password'])) {
            // Set session variables
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['username'] = $user['username'];
            $_SESSION['email'] = $user['email'];
            $_SESSION['user_role'] = $user['type']; // Store user role

            // Redirect to appropriate dashboard
            header("Location: " . ($user['type'] === 'admin' ? 'admin_dashboard.php' : 'dashboard.php'));
            exit();
        } else {
            $message = "Invalid password. Please try again.";
        }
    } else {
        $message = "No account found with that username or email, or the account is not verified.";
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link href="https://maxcdn.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css" rel="stylesheet">
    <title>Login</title>
    <style>
       body {
            background-color: #F7EFE5;
            color: #674188;
        }
        .login-container {
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

    <div class="container login-container">
        <h2 class="text-center">Login</h2>
        <?php if (!empty($message)): ?>
            <div class="message"><?php echo $message; ?></div>
        <?php endif; ?>
        <form action="login.php" method="POST">
            <div class="form-group">
                <label for="username_or_email">Username or Email:</label>
                <input type="text" id="username_or_email" name="username_or_email" class="form-control" required>
            </div>
            <div class="form-group">
                <label for="password">Password:</label>
                <input type="password" id="password" name="password" class="form-control" required>
            </div>
            <button type="submit" class="btn btn-primary btn-block">Login</button>
        </form>
    </div>

    <script src="https://code.jquery.com/jquery-3.5.1.slim.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/@popperjs/core@2.10.2/dist/umd/popper.min.js"></script>
    <script src="https://maxcdn.bootstrapcdn.com/bootstrap/4.5.2/js/bootstrap.min.js"></script>
</body>
</html>
