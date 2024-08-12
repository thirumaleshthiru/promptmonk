<?php
session_start();

// Check if the user is logged in and is an admin
if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'admin') {
    header("Location: login.php");
    exit();
}

include 'db_connect.php';

$message = "";

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $category_name = $_POST['category_name'];

    // Validate input
    if (empty($category_name)) {
        $message = "Category name is required.";
    } else {
        // Check if category already exists
        $check_query = "SELECT * FROM categories WHERE category_name = ?";
        $stmt = $conn->prepare($check_query);

        if ($stmt === false) {
            $message = "Failed to prepare statement: " . $conn->error;
        } else {
            $stmt->bind_param("s", $category_name);
            $stmt->execute();
            $result = $stmt->get_result();
            $stmt->close();

            if ($result->num_rows > 0) {
                $message = "Category already exists.";
            } else {
                // Insert new category into the database
                $insert_query = "INSERT INTO categories (category_name) VALUES (?)";
                $stmt = $conn->prepare($insert_query);

                if ($stmt === false) {
                    $message = "Failed to prepare statement: " . $conn->error;
                } else {
                    $stmt->bind_param("s", $category_name);
                    $stmt->execute();
                    $stmt->close();

                    $message = "Category added successfully.";
                }
            }
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link href="https://maxcdn.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css" rel="stylesheet">
    <title>Add Category</title>
    <style>
        body {
            background-color: #F7EFE5;
            color: #674188;
        }
        .form-container {
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
            color: #721c24;
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

    <div class="container form-container">
        <h2 class="text-center">Add Category</h2>
        <?php if (!empty($message)): ?>
            <div class="message"><?php echo $message; ?></div>
        <?php endif; ?>
        <form action="add_category.php" method="POST">
            <div class="form-group">
                <label for="category_name">Category Name:</label>
                <input type="text" id="category_name" name="category_name" class="form-control" required>
            </div>
            <button type="submit" class="btn btn-primary btn-block">Add Category</button>
        </form>
    </div>

    <script src="https://code.jquery.com/jquery-3.5.1.slim.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/@popperjs/core@2.10.2/dist/umd/popper.min.js"></script>
    <script src="https://maxcdn.bootstrapcdn.com/bootstrap/4.5.2/js/bootstrap.min.js"></script>
</body>
</html>
