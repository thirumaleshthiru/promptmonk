<?php
session_start();
include 'db_connect.php';

// Function to get relative time
function time_ago($timestamp) {
    $time_ago = strtotime($timestamp);
    $current_time = time();
    $time_difference = $current_time - $time_ago;
    $seconds = $time_difference;
    
    $minutes      = round($seconds / 60);
    $hours        = round($seconds / 3600);
    $days         = round($seconds / 86400);
    $weeks        = round($seconds / 604800);
    
    if ($seconds <= 60) {
        return "Just now";
    } else if ($minutes <= 60) {
        return "$minutes minutes ago";
    } else if ($hours <= 24) {
        return "$hours hours ago";
    } else if ($days <= 7) {
        return "$days days ago";
    } else if ($weeks <= 4.3) {
        return "$weeks weeks ago";
    } else {
        return date('M d, Y', $time_ago);
    }
}

// Function to truncate HTML content
function truncate_html($html, $max_length = 300) {
    $dom = new DOMDocument();
    @$dom->loadHTML(mb_convert_encoding($html, 'HTML-ENTITIES', 'UTF-8'));
    
    $text = $dom->textContent;
    if (strlen($text) <= $max_length) {
        return $html;
    }
    
    $truncated_text = substr($text, 0, $max_length);
    $truncated_html = substr($html, 0, strlen($html) - strlen($text) + strlen($truncated_text));
    
    $truncated_html = preg_replace('/<[^>]*>$/', '', $truncated_html); // Remove incomplete tags
    return $truncated_html . '...';
}

// Get the username from the URL
$username = $_GET['username'] ?? '';
$username = $conn->real_escape_string($username);

// Fetch user details
$query = "SELECT id, username, email, profile_image, streak FROM users WHERE username = ?";
$stmt = $conn->prepare($query);

if (!$stmt) {
    die("Prepare failed: " . $conn->error);
}

$stmt->bind_param("s", $username);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    echo "User not found.";
    exit();
}

$user = $result->fetch_assoc();
$stmt->close();

// Fetch user prompts
$query = "SELECT id, title, description, creating_date FROM prompts WHERE user_id = ? ORDER BY creating_date DESC";
$stmt = $conn->prepare($query);

if (!$stmt) {
    die("Prepare failed: " . $conn->error);
}

$stmt->bind_param("i", $user['id']);
$stmt->execute();
$prompts_result = $stmt->get_result();
$stmt->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link href="https://maxcdn.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css" rel="stylesheet">
    <title>Profile</title>
    <style>
        body {
            background-color: #F7EFE5;
        }
        .profile-container {
            max-width: 900px;
            margin: 50px auto;
            padding: 20px;
            background-color: #fff;
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
        }
        .profile-header {
            display: flex;
            align-items: center;
            border-bottom: 2px solid #C8A1E0;
            padding-bottom: 20px;
            margin-bottom: 30px;
        }
        .profile-header img {
            border-radius: 50%;
            width: 150px;
            height: 150px;
            object-fit: cover;
            margin-right: 20px;
        }
        .profile-header h2 {
            font-size: 30px;
            font-weight: 600;
            color: #674188;
            margin-bottom: 10px;
        }
        .profile-header p {
            font-size: 16px;
            color: #674188;
            margin: 5px 0;
        }
        .btn-update {
            margin-top: 10px;
            background-color: #C8A1E0;
            border-color: #C8A1E0;
            color: #fff;
        }
        .btn-update:hover {
            background-color: #E2BFD9;
            border-color: #E2BFD9;
        }
        .profile-prompts {
            margin-top: 30px;
        }
        .profile-prompts h3 {
            font-size: 24px;
            margin-bottom: 20px;
            color: #674188;
        }
        .profile-prompts .card {
            margin-bottom: 15px;
            border: 1px solid #ddd;
            border-radius: 10px;
            overflow: hidden;
            transition: box-shadow 0.3s ease;
        }
        .profile-prompts .card:hover {
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.2);
        }
        .profile-prompts .card-body {
            padding: 20px;
        }
        .card-title {
            font-size: 20px;
            font-weight: 500;
            color: #674188;
        }
        .card-text {
            font-size: 16px;
            color: #666;
        }
        .btn-view {
            background-color: #C8A1E0;
            border-color: #C8A1E0;
            color: #fff;
        }
        .btn-view:hover {
            background-color: #E2BFD9;
            border-color: #E2BFD9;
        }
        @media (max-width: 767px) {
            .profile-header {
                flex-direction: column;
                align-items: flex-start;
            }
            .profile-header img {
                margin-bottom: 20px;
            }
            .profile-header h2 {
                font-size: 24px;
            }
        }
    </style>
</head>
<body>
    <?php include 'navbar.php'; ?>

    <div class="container profile-container">
        <div class="profile-header">
            <img src="<?php echo htmlspecialchars($user['profile_image']); ?>" alt="Profile Image">
            <div>
                <h2><?php echo htmlspecialchars($user['username']); ?></h2>
                <p>Email: <?php echo htmlspecialchars($user['email']); ?></p>
                 <?php if (isset($_SESSION['user_id']) && $_SESSION['user_id'] == $user['id']): ?>
                    <a href="update_profile.php" class="btn btn-primary btn-update">Update Profile</a>
                <?php endif; ?>
            </div>
        </div>
        <div class="profile-prompts">
            <h3>Prompts</h3>
            <?php if ($prompts_result->num_rows > 0): ?>
                <?php while ($prompt = $prompts_result->fetch_assoc()): ?>
                    <div class="card">
                        <div class="card-body">
                            <h5 class="card-title"><?php echo htmlspecialchars($prompt['title']); ?></h5>
                            <p class="card-text"><?php echo truncate_html($prompt['description'], 300); ?></p>
                            <p class="card-text"><small class="text-muted"><?php echo time_ago($prompt['creating_date']); ?></small></p>
                            <a href="prompt.php?id=<?php echo urlencode(base64_encode($prompt['id'])); ?>" class="btn btn-primary btn-small btn-view">View</a>
                        </div>
                    </div>
                <?php endwhile; ?>
            <?php else: ?>
                <p>No prompts available.</p>
            <?php endif; ?>
        </div>
    </div>

    <script src="https://code.jquery.com/jquery-3.5.1.slim.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/@popperjs/core@2.10.2/dist/umd/popper.min.js"></script>
    <script src="https://maxcdn.bootstrapcdn.com/bootstrap/4.5.2/js/bootstrap.min.js"></script>
</body>
</html>
