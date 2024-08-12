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
    $prompt_id = intval($_GET['delete']);

    // Fetch the result_path for the prompt to delete associated files
    if ($stmt = $conn->prepare("SELECT result_path FROM prompts WHERE id = ?")) {
        $stmt->bind_param("i", $prompt_id);
        $stmt->execute();
        $stmt->bind_result($result_path);
        $stmt->fetch();
        $stmt->close();

        // Delete the prompt
        if ($stmt = $conn->prepare("DELETE FROM prompts WHERE id = ?")) {
            $stmt->bind_param("i", $prompt_id);
            $stmt->execute();
            $stmt->close();
        }

        // Delete associated files if they exist
        if ($result_path) {
            $files = explode(',', $result_path);
            foreach ($files as $file) {
                $file = trim($file); // Remove any extra spaces
                if (file_exists($file)) {
                    unlink($file); // Delete the file
                }
            }
        }

        header("Location: manage_prompts.php");
        exit();
    }
}

// Fetch prompts from database
$query = "
    SELECT p.id, p.title, u.username
    FROM prompts p
    JOIN users u ON p.user_id = u.id
";
if ($result = $conn->query($query)) {
    $prompts = $result->fetch_all(MYSQLI_ASSOC);
    $result->free();
} else {
    die("Error fetching prompts: " . $conn->error);
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link href="https://maxcdn.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css" rel="stylesheet">
    <title>Manage Prompts</title>
    <style>
          body {
            background-color: #F7EFE5;
            color: #674188;
        }
        .prompts-container {
            max-width: 800px;
            margin: 50px auto;
            padding: 20px;
            border: 1px solid #ddd;
            border-radius: 5px;
            box-shadow: 0 0 10px rgba(0, 0, 0, 0.1);
        }
        .prompt-table th, .prompt-table td {
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

    <div class="container prompts-container">
        <h2 class="text-center">Manage Prompts</h2>
        <table class="table table-striped prompt-table">
            <thead>
                <tr>
                    <th>Title</th>
                    <th>User</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($prompts as $prompt): ?>
                <tr>
                    <td><?php echo htmlspecialchars($prompt['title']); ?></td>
                    <td><?php echo htmlspecialchars($prompt['username']); ?></td>
                    <td>
                        <a href="?delete=<?php echo $prompt['id']; ?>" class="btn btn-delete" onclick="return confirm('Are you sure you want to delete this prompt?');">Delete</a>
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
