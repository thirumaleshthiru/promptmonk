<?php
session_start();

// Check if the user is logged in and is an admin
if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'admin') {
    header("Location: login.php");
    exit();
}

include 'db_connect.php'; // Ensure this file path is correct

// Handle delete request
if (isset($_GET['delete'])) {
    $category_id = intval($_GET['delete']);
    if ($stmt = $conn->prepare("DELETE FROM categories WHERE id = ?")) {
        $stmt->bind_param("i", $category_id);
        $stmt->execute();
        $stmt->close();
    }
    header("Location: manage_categories.php");
    exit();
}

// Fetch categories from database
if ($result = $conn->query("SELECT * FROM categories")) {
    $categories = $result->fetch_all(MYSQLI_ASSOC);
    $result->free();
} else {
    die("Error fetching categories: " . $conn->error);
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link href="https://maxcdn.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css" rel="stylesheet">
    <title>Manage Categories</title>
    <style>
          body {
            background-color: #F7EFE5;
            color: #674188;
        }
        .categories-container {
            max-width: 800px;
            margin: 50px auto;
            padding: 20px;
            border: 1px solid #ddd;
            border-radius: 5px;
            box-shadow: 0 0 10px rgba(0, 0, 0, 0.1);
        }
        .category-table th, .category-table td {
            text-align: center;
        }
        .btn-delete {
            color: #fff;
            background-color: #dc3545;
            border-color: #dc3545;
        }
        .btn-delete:hover {
            background-color: #c82333;
            border-color: #bd2130;
        }
    </style>
</head>
<body>
    <?php include 'navbar.php'; ?>

    <div class="container categories-container">
        <h2 class="text-center">Manage Categories</h2>
        <table class="table table-striped category-table">
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Category Name</th>
                    <th>Created At</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($categories as $category): ?>
                <tr>
                    <td><?php echo htmlspecialchars($category['id']); ?></td>
                    <td><?php echo htmlspecialchars($category['category_name']); ?></td>
                    <td><?php echo htmlspecialchars($category['created_at']); ?></td>
                    <td>
                        <a href="?delete=<?php echo $category['id']; ?>" class="btn btn-delete" onclick="return confirm('Are you sure you want to delete this category?');">Delete</a>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>

    <script src="https://code.jquery.com/jquery-3.5.1.slim.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/@popperjs/core@2.10.2/dist/umd/popper.min.js"></script>
    <script src="https://maxcdn.bootstrapcdn.com/bootstrap/4.5.2/js/bootstrap.min.js"></script>
</body>
</html>
