<?php
session_start();
include 'db_connect.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$user_id = $_SESSION['user_id'];

// Fetch user's prompts
$query = "SELECT * FROM prompts WHERE user_id = ?";
$stmt = $conn->prepare($query);

if (!$stmt) {
    die("Prepare failed: " . $conn->error);
}

$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();

// Check if prompts exist
if ($result->num_rows === 0) {
    $message = "You have no prompts.";
} else {
    $prompts = $result->fetch_all(MYSQLI_ASSOC);
}

$stmt->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link href="https://maxcdn.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css" rel="stylesheet">
    <title>My Prompts</title>
    <style>
        body {
            background-color: #F7EFE5;
            color: #674188;
        }
        .card-container {
            max-width: 800px;
            margin: 50px auto;
        }
        .card {
            margin-bottom: 20px;
            background-color: #FFFFFF;
            border: 1px solid #E2BFD9;
        }
        .card-body {
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        .card-title {
            margin-bottom: 0;
            color: #674188;
        }
        .card-text {
            color: #939185;
        }
        .btn-group .btn {
            border-radius: 4px;
        }
        .btn-warning {
            background-color: #C8A1E0;
            border-color: #C8A1E0;
        }
        .btn-warning:hover {
            background-color: #a77bb0;
            border-color: #a77bb0;
        }
        .btn-danger {
            background-color: #E2BFD9;
            border-color: #E2BFD9;
        }
        .btn-danger:hover {
            background-color: #d1a3b1;
            border-color: #d1a3b1;
        }
        .alert-info {
            background-color: #C8A1E0;
            color: #674188;
            border-color: #C8A1E0;
        }
    </style>
</head>
<body>
    <?php include 'navbar.php'; ?>

    <div class="container card-container">
        <h2 class="text-center">My Prompts</h2><br>
        <?php if (isset($message)): ?>
            <div class="alert alert-info"><?php echo htmlspecialchars($message); ?></div>
        <?php endif; ?>
        <?php if (!empty($prompts)): ?>
            <?php foreach ($prompts as $prompt): ?>
                <div class="card">
                    <div class="card-body">
                        <div class="card-content">
                            <h5 class="card-title"><?php echo htmlspecialchars($prompt['title']); ?></h5>
                            <p class="card-text"><?php echo htmlspecialchars(substr($prompt['prompt'], 0, 100)) . (strlen($prompt['prompt']) > 100 ? '...' : ''); ?></p>
                        </div>
                        <div class="btn-group">
                            <a href="update_prompt.php?id=<?php echo $prompt['id']; ?>" class="btn btn-warning btn-sm">Update</a>
                            <a href="delete.php?id=<?php echo $prompt['id']; ?>" class="btn btn-danger btn-sm" onclick="return confirm('Are you sure you want to delete this prompt?');">Delete</a>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>

    <script src="https://code.jquery.com/jquery-3.5.1.slim.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/@popperjs/core@2.10.2/dist/umd/popper.min.js"></script>
    <script src="https://maxcdn.bootstrapcdn.com/bootstrap/4.5.2/js/bootstrap.min.js"></script>
</body>
</html>
