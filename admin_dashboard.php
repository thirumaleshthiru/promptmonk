<?php
session_start();

// Check if the user is logged in and is an admin
if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'admin') {
    header("Location: login.php");
    exit();
}

include 'db_connect.php';
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link href="https://maxcdn.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css" rel="stylesheet">
    <title>Admin Dashboard</title>
    <style>
        body {
            background-color: #F7EFE5;
            color: #674188;
        }
        .dashboard-container {
            max-width: 800px;
            margin: 50px auto;
            padding: 20px;
            border: 1px solid #ddd;
            border-radius: 5px;
            box-shadow: 0 0 10px rgba(0, 0, 0, 0.1);
            background-color: #FFFFFF;
        }
        .dashboard-links a {
            display: block;
            margin-bottom: 10px;
            padding: 10px;
            text-align: center;
            text-decoration: none;
            color: #FFFFFF;
            background-color: #674188;
            border-radius: 5px;
        }
        .dashboard-links a:hover {
            background-color: #5a367d;
        }
    </style>
</head>
<body>
    <?php include 'navbar.php'; ?>

    <div class="container dashboard-container">
        <h2 class="text-center">Admin Dashboard</h2><br>
        <div class="dashboard-links">
            <a href="add_category.php">Add Category</a>
            <a href="manage_categories.php">Manage Categories</a>
            <a href="manage_prompts.php">Manage Prompts</a>
        </div>
    </div>

    <script src="https://code.jquery.com/jquery-3.5.1.slim.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/@popperjs/core@2.10.2/dist/umd/popper.min.js"></script>
    <script src="https://maxcdn.bootstrapcdn.com/bootstrap/4.5.2/js/bootstrap.min.js"></script>
</body>
</html>
