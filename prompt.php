<?php
session_start();
include 'db_connect.php';

// Decode the prompt ID from the query string
$encoded_id = isset($_GET['id']) ? $_GET['id'] : '';
$prompt_id = intval(base64_decode($encoded_id)); // Decode and convert to integer

// Check if prompt ID is valid
if ($prompt_id <= 0) {
    echo "Prompt not found.";
    exit();
}

// Fetch prompt details from the database
$query = "SELECT prompts.*, users.username FROM prompts 
          JOIN users ON prompts.user_id = users.id 
          WHERE prompts.id = ?";
$stmt = $conn->prepare($query);
$stmt->bind_param("i", $prompt_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    echo "Prompt not found.";
    exit();
}

$prompt = $result->fetch_assoc();
$result_type = $prompt['result_type'];
$result_path = $prompt['result_path'];
$username = $prompt['username']; // Fetch username

// Handle like and dislike actions
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_SESSION['user_id'])) {
    $user_id = $_SESSION['user_id'];
    $action = $_POST['action'];

    if ($action === 'like' || $action === 'dislike') {
        $stmt = $conn->prepare("INSERT INTO prompt_likes (prompt_id, user_id, like_dislike) VALUES (?, ?, ?) ON DUPLICATE KEY UPDATE like_dislike = VALUES(like_dislike)");
        $stmt->bind_param("iis", $prompt_id, $user_id, $action);
        $stmt->execute();
        $stmt->close();
    }
}

// Fetch like and dislike counts
$query = "SELECT like_dislike, COUNT(*) as count FROM prompt_likes WHERE prompt_id = ? GROUP BY like_dislike";
$stmt = $conn->prepare($query);
$stmt->bind_param("i", $prompt_id);
$stmt->execute();
$likes_dislikes = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt->close();

$likes_count = 0;
$dislikes_count = 0;
foreach ($likes_dislikes as $ld) {
    if ($ld['like_dislike'] === 'like') {
        $likes_count = $ld['count'];
    } else {
        $dislikes_count = $ld['count'];
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link href="https://maxcdn.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.3/css/all.min.css" rel="stylesheet"> <!-- Font Awesome -->
    <title><?php echo htmlspecialchars($prompt['title']); ?></title>
    <style>
        body {
            background-color: #F7EFE5; /* Lightest background color */
        }
        .container {
            background-color: #FFFFFF; /* White background for content */
            padding: 20px;
            border-radius: 8px;
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
        }
        h2 {
            color: #674188; /* Primary dark color */
        }
        .link {
            color: #982B1C;
            text-style:italic;  
        }
        .link:hover {
            color: #800000;  
        }
        blockquote {
            border-left: 5px solid #674188; /* Primary dark color for the border */
            padding-left: 15px;
            margin: 20px 0;
            font-style: italic;
            color: #333;
            background-color: #E2BFD9; /* Light pink background for blockquote */
            padding: 10px 20px;
        }
        .copy-btn {
            border: none;
            background: none;
            cursor: pointer;
            color: #674188; /* Primary dark color for the button */
            font-size: 1.2em;
            margin-left: 10px;
        }
        .image-container {
            display: flex;
            flex-wrap: wrap;
            gap: 10px; /* Gap between images */
            justify-content: flex-start; /* Align images to the left */
        }
        .image-thumbnail {
            cursor: pointer;
            max-width: 150px; /* Set maximum width for thumbnail */
            height: auto; /* Maintain aspect ratio */
        }
        .modal-body img {
            width: 100%; /* Full width inside modal */
            height: auto; /* Maintain aspect ratio */
        }
        .btn-success {
            background-color: #C8A1E0; /* Lighter purple for like button */
            border-color: #C8A1E0;
        }
        .btn-success:hover {
            background-color: #674188; /* Darker purple on hover */
            border-color: #674188;
        }
        .btn-danger {
            background-color: #E2BFD9; /* Light pink for dislike button */
            border-color: #E2BFD9;
        }
        .btn-danger:hover {
            background-color: #C8A1E0; /* Lighter purple on hover */
            border-color: #C8A1E0;
        }
        .btn-primary{
            background-color: #674188;
            border:white;
        }
    </style>
</head>
<body>
    <?php include 'navbar.php'; ?>

    <div class="container mt-5">
        <h2><?php echo htmlspecialchars($prompt['title']); ?></h2>
        <div class="mt-3">
            <i>
                <a href="http://localhost/promptmonk/<?php echo urlencode($username); ?>" class="link">
                    by <?php echo htmlspecialchars($username); ?>
                </a>
            </i>
        </div>
        <p><?php echo nl2br($prompt['description']); ?></p>
        
        <div class="d-flex align-items-center">
            <blockquote class="flex-grow-1">
                <?php echo nl2br(htmlspecialchars($prompt['prompt'])); ?>
            </blockquote>
            <button class="copy-btn" title="Copy prompt to clipboard" onclick="copyToClipboard()">
                <i class="fas fa-copy"></i>
            </button>
        </div>

        <div class="mt-4">
            <h5>Prompt Result</h5>
            <?php
            if ($result_type === 'text' || $result_type === 'pdf' || $result_type === 'docx') {
                $files = explode(',', $result_path);
                foreach ($files as $file) {
                    $file_name = basename($file);
                    echo "

                    
                    <div class='mb-3'>
                            <a href='$file' class='btn btn-primary' download='$file_name'>$file_name</a>
                          </div>";
                }
                echo " 
                      <div class='mt-3'>
                        <form method='post' action='prompt_monk_chatbot.php'>
                            <textarea name='prompt' class='form-control' rows='3'  >" . htmlspecialchars($prompt['prompt']) . "</textarea>
                            <button type='submit' class='btn btn-primary mt-2'>Send to Gemini AI</button>
                        </form>
                      </div>";
            } elseif ($result_type === 'video') {
                echo "<div class='embed-responsive embed-responsive-16by9'>
                        <video controls class='embed-responsive-item'>
                            <source src='$result_path' type='video/mp4'>
                            Your browser does not support the video tag.
                        </video>
                      </div>";
            } elseif ($result_type === 'images') {
                $images = explode(',', $result_path);
                if (count($images) > 0) {
                    echo "<div class='image-container'>";
                    foreach ($images as $index => $image) {
                        echo "<img src='$image' alt='Image' class='image-thumbnail' data-toggle='modal' data-target='#imageModal' data-src='$image'>";
                    }
                    echo "</div>";
                } else {
                    echo "No images found.";
                }
            } else {
                echo "Unsupported file type.";
            }
            ?>
        </div>

        <div class="mt-4">
            <form method="post">
                <button type="submit" name="action" value="like" class="btn btn-success">
                    <i class="fas fa-thumbs-up"></i> Like <span class="badge badge-light"><?php echo $likes_count; ?></span>
                </button>
                <button type="submit" name="action" value="dislike" class="btn btn-danger">
                    <i class="fas fa-thumbs-down"></i> Dislike <span class="badge badge-light"><?php echo $dislikes_count; ?></span>
                </button>
            </form>
        </div>
    </div>

    <div class="modal fade" id="imageModal" tabindex="-1" role="dialog" aria-labelledby="imageModalLabel" aria-hidden="true">
        <div class="modal-dialog" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="imageModalLabel">Image</h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <div class="modal-body">
                    <img src="" alt="Full-size image">
                </div>
            </div>
        </div>
    </div>

    <script src="https://code.jquery.com/jquery-3.5.1.slim.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/@popperjs/core@2.9.3/dist/umd/popper.min.js"></script>
    <script src="https://maxcdn.bootstrapcdn.com/bootstrap/4.5.2/js/bootstrap.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.3/js/all.min.js"></script> <!-- Font Awesome -->
    <script>
        function copyToClipboard() {
            var promptText = document.querySelector('blockquote').innerText;
            navigator.clipboard.writeText(promptText).then(function() {
                alert('Prompt copied to clipboard!');
            }, function(err) {
                alert('Failed to copy prompt: ', err);
            });
        }

        $(document).ready(function() {
            $('.image-thumbnail').on('click', function() {
                var imgSrc = $(this).data('src');
                $('#imageModal .modal-body img').attr('src', imgSrc);
            });
        });
    </script>
</body>
</html>
