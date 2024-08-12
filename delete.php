<?php
session_start();
include 'db_connect.php';

$message = "";

// Ensure user is logged in and has the correct role
if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'user') {
    header("Location: login.php");
    exit();
}

// Handle deletion request
if (isset($_GET['id'])) {
    $prompt_id = $_GET['id'];

    // Fetch prompt details
    $query = "SELECT result_path FROM prompts WHERE id = ? AND user_id = ?";
    $stmt = $conn->prepare($query);
    $stmt->bind_param("ii", $prompt_id, $_SESSION['user_id']);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows > 0) {
        $prompt = $result->fetch_assoc();
        $result_path = $prompt['result_path'];

        // Delete associated file(s) if any
        if (!empty($result_path)) {
            $files = explode(',', $result_path);

            foreach ($files as $file) {
                if (file_exists($file)) {
                    unlink($file);
                }
            }
        }

        // Delete prompt from the database
        $delete_query = "DELETE FROM prompts WHERE id = ? AND user_id = ?";
        $delete_stmt = $conn->prepare($delete_query);
        $delete_stmt->bind_param("ii", $prompt_id, $_SESSION['user_id']);

        if ($delete_stmt->execute()) {
            $message = "Prompt and associated files deleted successfully.";
        } else {
            $message = "Error deleting prompt: " . $delete_stmt->error;
        }

        $delete_stmt->close();
    } else {
        $message = "Prompt not found or not authorized to delete.";
    }

    $stmt->close();
} else {
    $message = "Invalid request.";
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Delete Prompt</title>
    <link href="https://maxcdn.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body {
            background-color:  #F7EFE5;
            color: #333;
        }
        .message {
            margin: 20px;
            padding: 10px;
            border: 1px solid transparent;
            border-radius: 4px;
            color: #721c24;
            background-color: #f8d7da;
            border-color: #f5c6cb;
        }
        .btn-primary {
            background-color: #DCA47C;
            border-color: #DCA47C;
        }
        .btn-primary:hover {
            background-color: #b6925f;
            border-color: #b6925f;
        }
    </style>
</head>
<body>
    <?php include 'navbar.php'; ?>

    <div class="container">
        <?php if (!empty($message)): ?>
            <div class="message text-align-center"><?php echo htmlspecialchars($message); ?></div>
        <?php endif; ?>
        <a href="my_prompts.php" class="btn btn-primary">Back to My Prompts</a>
    </div>
</body>
</html>
