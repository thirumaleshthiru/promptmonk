<?php
session_start();
include 'db_connect.php';

$message = "";

// Ensure user is logged in and has the correct role
if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'user') {
    header("Location: login.php");
    exit();
}

// Fetch prompt details for editing
if (isset($_GET['id'])) {
    $prompt_id = $_GET['id'];
    $query = "SELECT * FROM prompts WHERE id = ? AND user_id = ?";
    $stmt = $conn->prepare($query);
    $stmt->bind_param("ii", $prompt_id, $_SESSION['user_id']);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows > 0) {
        $prompt = $result->fetch_assoc();
    } else {
        $message = "Prompt not found.";
    }
    $stmt->close();
} else {
    $message = "Invalid request.";
}

// Fetch categories for dropdown
$categories_query = "SELECT * FROM categories";
$categories_result = $conn->query($categories_query);
$categories = $categories_result->fetch_all(MYSQLI_ASSOC);

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $title = $_POST['title'];
    $description = $_POST['description'];
    $prompt_text = $_POST['prompt'];
    $result_type = $_POST['result_type'];
    $category_id = $_POST['category_id'];

    $result_path = $prompt['result_path']; // Start with existing file paths

    // Handle file uploads based on result type
    if ($result_type === 'images') {
        if (isset($_FILES['result_files']) && !empty($_FILES['result_files']['name'][0])) {
            // Remove existing files
            $existing_files = explode(',', $prompt['result_path']);
            foreach ($existing_files as $file) {
                if (file_exists($file)) {
                    unlink($file);
                }
            }

            // Handle new image uploads
            $upload_dir = 'uploads/';
            $result_paths = [];

            foreach ($_FILES['result_files']['name'] as $key => $file_name) {
                if ($_FILES['result_files']['error'][$key] === UPLOAD_ERR_OK) {
                    $tmp_name = $_FILES['result_files']['tmp_name'][$key];
                    $file_type = mime_content_type($tmp_name);
                    $allowed_image_types = ['image/jpeg', 'image/png', 'image/gif'];

                    if (in_array($file_type, $allowed_image_types)) {
                        $upload_file = $upload_dir . basename($file_name);
                        if (move_uploaded_file($tmp_name, $upload_file)) {
                            $result_paths[] = $upload_file;
                        } else {
                            $message = "Image upload failed for file: $file_name.";
                            break;
                        }
                    } else {
                        $message = "Invalid image file type for file: $file_name. Only JPEG, PNG, and GIF are allowed.";
                        break;
                    }
                } else {
                    $message = "Image upload error for file: $file_name.";
                    break;
                }
            }

            $result_path = implode(',', $result_paths);
        } else {
            $message = "Please upload at least one image.";
        }
    } else if (in_array($result_type, ['video', 'pdf', 'docx', 'text'])) {
        if (isset($_FILES['result_file']) && $_FILES['result_file']['error'] === UPLOAD_ERR_OK) {
            // Remove existing file
            if (file_exists($prompt['result_path'])) {
                unlink($prompt['result_path']);
            }

            $upload_dir = 'uploads/';
            $file_name = $_FILES['result_file']['name'];
            $tmp_name = $_FILES['result_file']['tmp_name'];
            $file_type = mime_content_type($tmp_name);
            $allowed_types = [
                'video' => ['video/mp4', 'video/mpeg'],
                'pdf' => ['application/pdf'],
                'docx' => ['application/vnd.openxmlformats-officedocument.wordprocessingml.document'],
                'text' => ['text/plain']
            ];

            if (array_key_exists($result_type, $allowed_types) && in_array($file_type, $allowed_types[$result_type])) {
                $upload_file = $upload_dir . basename($file_name);
                if (move_uploaded_file($tmp_name, $upload_file)) {
                    $result_path = $upload_file;
                } else {
                    $message = "File upload failed.";
                }
            } else {
                $message = "Invalid file type for the selected result type.";
            }
        } else {
            if ($result_type !== 'text') {
                $message = "Please upload a file.";
            }
        }
    }

    // Update prompt in the database
    if (empty($message)) {
        $query = "UPDATE prompts SET title = ?, description = ?, prompt = ?, result_type = ?, result_path = ?, category_id = ? WHERE id = ? AND user_id = ?";
        $stmt = $conn->prepare($query);
        $stmt->bind_param("ssssssii", $title, $description, $prompt_text, $result_type, $result_path, $category_id, $prompt_id, $_SESSION['user_id']);

        if ($stmt->execute()) {
            $message = "Prompt updated successfully.";
        } else {
            $message = "Error updating prompt: " . $stmt->error;
        }

        $stmt->close();
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link href="https://maxcdn.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.quilljs.com/1.3.6/quill.snow.css" rel="stylesheet">
    <title>Update Prompt</title>
    <style>
        body {
            background-color: #F7EFE5;
            color: #333;
        }
        .form-container {
            max-width: 800px;
            margin: 50px auto;
            padding: 20px;
            border: 1px solid #ddd;
            border-radius: 5px;
            box-shadow: 0 0 10px rgba(0, 0, 0, 0.1);
            background-color: #ffffff;
        }
        .message {
            margin-bottom: 20px;
            padding: 10px;
            border: 1px solid transparent;
            border-radius: 4px;
            color: #721c24;
            background-color: #E2BFD9;
            border-color: #f5c6cb;
        }
        .ql-container {
            height: 200px;
        }
        .form-group label {
            color: #674188;
        }
        .btn-primary {
            background-color: #C8A1E0;
            border-color: #C8A1E0;
        }
        .btn-primary:hover {
            background-color: #a487c0;
            border-color: #a487c0;
        }
        #result_file{
            padding-bottom:40px;
        }
    </style>
</head>
<body>
    <?php include 'navbar.php'; ?>

    <div class="container form-container">
        <h2 class="text-center">Update Prompt</h2>
        <?php if (!empty($message)): ?>
            <div class="message"><?php echo htmlspecialchars($message); ?></div>
        <?php endif; ?>
        <form action="update_prompt.php?id=<?php echo htmlspecialchars($prompt_id); ?>" method="POST" enctype="multipart/form-data">
            <div class="form-group">
                <label for="title">Title:</label>
                <input type="text" id="title" name="title" class="form-control" value="<?php echo htmlspecialchars($prompt['title']); ?>" required>
            </div>
            <div class="form-group">
                <label for="description">Description:</label>
                <div id="editor"><?php echo  $prompt['description'] ; ?></div>
                <input type="hidden" id="description" name="description">
            </div>
            <div class="form-group">
                <label for="prompt">Prompt:</label>
                <textarea id="prompt" name="prompt" class="form-control" rows="3" required><?php echo htmlspecialchars($prompt['prompt']); ?></textarea>
            </div>
            <div class="form-group">
                <label for="result_type">Result Type:</label>
                <select id="result_type" name="result_type" class="form-control" required>
                    <option value="text" <?php echo $prompt['result_type'] === 'text' ? 'selected' : ''; ?>>Text</option>
                    <option value="images" <?php echo $prompt['result_type'] === 'images' ? 'selected' : ''; ?>>Images</option>
                    <option value="video" <?php echo $prompt['result_type'] === 'video' ? 'selected' : ''; ?>>Video</option>
                    <option value="pdf" <?php echo $prompt['result_type'] === 'pdf' ? 'selected' : ''; ?>>PDF</option>
                    <option value="docx" <?php echo $prompt['result_type'] === 'docx' ? 'selected' : ''; ?>>DOCX</option>
                </select>
            </div>
            <div class="form-group" id="file_upload" style="display: <?php echo $prompt['result_type'] === 'images' ? 'block' : 'none'; ?>;">
                <label for="result_files">Upload Images:</label>
                <input type="file" id="result_files" name="result_files[]" class="form-control" multiple>
            </div>
            <div class="form-group" id="file_upload_single" style="display: <?php echo in_array($prompt['result_type'], ['video', 'pdf', 'docx', 'text']) ? 'block' : 'none'; ?>;">
                <label for="result_file">Upload File:</label>
                <input type="file" id="result_file" name="result_file" class="form-control">
            </div>
            <div class="form-group">
                <label for="category_id">Category:</label>
                <select id="category_id" name="category_id" class="form-control" required>
                    <?php foreach ($categories as $category): ?>
                        <option value="<?php echo htmlspecialchars($category['id']); ?>" <?php echo $category['id'] == $prompt['category_id'] ? 'selected' : ''; ?>>
                            <?php echo htmlspecialchars($category['category_name']); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <button type="submit" class="btn btn-primary">Update Prompt</button>
        </form>
    </div>

    <script src="https://code.jquery.com/jquery-3.5.1.slim.min.js"></script>
    <script src="https://cdn.quilljs.com/1.3.6/quill.js"></script>
    <script>
        var quill = new Quill('#editor', {
            theme: 'snow',
            modules: {
                toolbar: [
                    [{ 'header': '1' }, { 'header': '2' }],
                    ['bold', 'italic', 'underline'],
                    ['link'],
                    [{ 'list': 'ordered' }, { 'list': 'bullet' }],
                    [{ 'align': [] }],
                    ['clean']
                ]
            }
        });

        document.querySelector('form').addEventListener('submit', function() {
            var description = document.querySelector('#description');
            description.value = quill.root.innerHTML;
        });

        document.getElementById('result_type').addEventListener('change', function() {
            var type = this.value;
            document.getElementById('file_upload').style.display = type === 'images' ? 'block' : 'none';
            document.getElementById('file_upload_single').style.display = ['video', 'pdf', 'docx', 'text'].includes(type) ? 'block' : 'none';
        });
    </script>
</body>
</html>
