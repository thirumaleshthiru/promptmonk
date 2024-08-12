<?php
session_start();
include 'db_connect.php';

// Check if user is logged in
if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'user') {
header("Location: login.php");
exit();
}

// Fetch user details
$user_id = $_SESSION['user_id'];
$query = "SELECT username FROM users WHERE id = ?";
$stmt = $conn->prepare($query);

if (!$stmt) {
die("Prepare failed: " . $conn->error);
}

$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();

// Check if user was found
if ($result->num_rows === 0) {
echo "User not found.";
exit();
}

// Fetch the username
$user = $result->fetch_assoc();
$username = htmlspecialchars($user['username']);
$stmt->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<link href="https://maxcdn.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css" rel="stylesheet">
<title>Dashboard</title>
<style>
body {
    background-color: #F7EFE5;
    color: #674188;
}
.dashboard-container {
    max-width: 800px;
    margin: 50px auto;
    padding: 20px;
    background-color: rgba(255, 255, 255, 0.1);
    border-radius: 15px;
    box-shadow: 0 0 15px rgba(0, 0, 0, 0.3);
}
.welcome-message {
    margin-bottom: 30px;
    font-size: 24px;
    font-weight: 300;
    text-align: center;
    color: #674188;
}
.dashboard-actions {
    display: flex;
    justify-content: space-between;
    flex-wrap: wrap;
}
.dashboard-actions .card {
    width: 48%;
    margin-bottom: 20px;
    border: none;
    background: rgba(255, 255, 255, 0.15);
    backdrop-filter: blur(10px);
    border-radius: 15px;
    transition: transform 0.3s ease-in-out;
}
.dashboard-actions .card:hover {
    transform: scale(1.05);
}
.dashboard-actions .card-body {
    padding: 20px;
    text-align: center;
    font-size: 18px;
    font-weight: 500;
    color: #674188;
}
.dashboard-actions .btn {
    font-size: 16px;
    padding: 10px 20px;
    border-radius: 50px;
    color: #fff;
    transition: background-color 0.3s ease-in-out;
}
.btn-primary {
    background-color: #674188;
    border-color: #674188;
}
.btn-primary:hover {
    background-color: #5a367d;
    border-color: #5a367d;
}
.btn-secondary {
    background-color: #C8A1E0;
    border-color: #C8A1E0;
}
.btn-secondary:hover {
    background-color: #a68bcb;
    border-color: #a68bcb;
}
.btn-info {
    background-color: #E2BFD9;
    border-color: #E2BFD9;
}
.btn-info:hover {
    background-color: #d09ac4;
    border-color: #d09ac4;
}

@media (max-width: 767px) {
    .dashboard-actions {
        flex-direction: column;
    }
    .dashboard-actions .card {
        width: 100%;
    }
}
</style>
</head>
<body>
<?php include 'navbar.php'; ?>

<div class="container dashboard-container">
    <div class="dashboard-actions">
        <div class="card">
            <div class="card-body">
                <a href="my_prompts.php" class="btn btn-primary">Manage Prompts</a>
            </div>
        </div>
        <div class="card">
            <div class="card-body">
                <a href="add_prompt.php" class="btn btn-secondary">Add New Prompt</a>
            </div>
        </div>
        <div class="card">
            <div class="card-body">
                <a href="<?php echo "http://localhost/promptmonk/" . $username; ?>" class="btn btn-info">View Profile</a>
            </div>
        </div>
    </div>
</div>

<script src="https://code.jquery.com/jquery-3.5.1.slim.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/@popperjs/core@2.10.2/dist/umd/popper.min.js"></script>
<script src="https://maxcdn.bootstrapcdn.com/bootstrap/4.5.2/js/bootstrap.min.js"></script>
</body>
</html>

