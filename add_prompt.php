<?php
session_start();
include 'db_connect.php';

$message = "";

// Check if user is logged in
if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'user') {
    header("Location: login.php");
    exit();
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $title = $_POST['title'];
    $description = $_POST['description']; // Quill editor's content
    $prompt = $_POST['prompt'];
    $result_type = $_POST['result_type'];
    $category_id = $_POST['category_id'];
    $tags = $_POST['tags']; // Tags as comma-separated values

    $result_path = '';

    if ($result_type === 'images') {
        if (isset($_FILES['result_files']) && $_FILES['result_files']['error'][0] === UPLOAD_ERR_OK) {
            $upload_dir = 'uploads/';
            if (!is_dir($upload_dir) && !mkdir($upload_dir, 0755, true)) {
                $message = "Unable to create upload directory.";
            } else {
                $result_paths = [];

                foreach ($_FILES['result_files']['name'] as $key => $file_name) {
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
                }
                $result_path = implode(',', $result_paths);
            }
        } else {
            $message = "Please upload at least one image.";
        }
    } else {
        $upload_dir = 'uploads/';
        if (!is_dir($upload_dir) && !mkdir($upload_dir, 0755, true)) {
            $message = "Unable to create upload directory.";
        } else {
            $file_name = $_FILES['result_file']['name'] ?? '';
            $tmp_name = $_FILES['result_file']['tmp_name'] ?? '';
            $file_type = mime_content_type($tmp_name);

            $allowed_types = [
                'video' => ['video/mp4', 'video/mpeg'],
                'pdf' => ['application/pdf'],
                'docx' => ['application/vnd.openxmlformats-officedocument.wordprocessingml.document'],
                'text' => ['text/plain']
            ];

            if (array_key_exists($result_type, $allowed_types) && isset($_FILES['result_file']) && $_FILES['result_file']['error'] === UPLOAD_ERR_OK) {
                if (in_array($file_type, $allowed_types[$result_type])) {
                    $upload_file = $upload_dir . basename($file_name);
                    if (move_uploaded_file($tmp_name, $upload_file)) {
                        $result_path = $upload_file;
                    } else {
                        $message = ucfirst($result_type) . " upload failed.";
                    }
                } else {
                    $message = "Invalid file type for the selected result type.";
                }
            } else {
                $message = "Please upload a valid file.";
            }
        }
    }

    // Insert prompt into database
    if (empty($message)) {
        $tags_arr = array_map('trim', explode(',', $tags));
        $tag_ids = [];

        foreach ($tags_arr as $tag_name) {
            $tag_query = "SELECT id FROM tags WHERE tag_name = ?";
            $tag_stmt = $conn->prepare($tag_query);
            $tag_stmt->bind_param("s", $tag_name);
            $tag_stmt->execute();
            $tag_result = $tag_stmt->get_result();

            if ($tag_result->num_rows > 0) {
                $tag = $tag_result->fetch_assoc();
                $tag_ids[] = $tag['id'];
            } else {
                $insert_tag_query = "INSERT INTO tags (tag_name) VALUES (?)";
                $insert_tag_stmt = $conn->prepare($insert_tag_query);
                $insert_tag_stmt->bind_param("s", $tag_name);
                if ($insert_tag_stmt->execute()) {
                    $tag_ids[] = $insert_tag_stmt->insert_id;
                } else {
                    $message = "Error inserting tag: " . $insert_tag_stmt->error;
                }
                $insert_tag_stmt->close();
            }
            $tag_stmt->close();
        }

        $user_id = $_SESSION['user_id'];
        $tag_ids_str = implode(',', $tag_ids);
        $query = "INSERT INTO prompts (user_id, title, description, prompt, result_type, result_path, category_id, tags_id) VALUES (?, ?, ?, ?, ?, ?, ?, ?)";
        $stmt = $conn->prepare($query);
        $stmt->bind_param("isssssii", $user_id, $title, $description, $prompt, $result_type, $result_path, $category_id, $tag_ids_str);

        if ($stmt->execute()) {
            $message = "Prompt added successfully.";
        } else {
            $message = "Error adding prompt: " . $stmt->error;
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
    <title>Add Prompt</title>
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
        <h2 class="text-center">Add New Prompt</h2>
        <?php if (!empty($message)): ?>
            <div class="message"><?php echo htmlspecialchars($message); ?></div>
        <?php endif; ?>
        <form action="add_prompt.php" method="POST" enctype="multipart/form-data">
            <div class="form-group">
                <label for="title">Title:</label>
                <input type="text" id="title" name="title" class="form-control" required>
            </div>
            <div class="form-group">
                <label for="description">Description:</label>
                <div id="editor-container"></div>
                <input type="hidden" id="description" name="description">
            </div>
            <div class="form-group">
                <label for="prompt">Prompt:</label>
                <textarea id="prompt" name="prompt" class="form-control" required></textarea>
            </div>
            <div class="form-group">
                <label for="result_type">Result Type:</label>
                <select id="result_type" name="result_type" class="form-control" required>
                <option value="">Select</option>                    
                    <option value="text">Text File</option>
                    <option value="images">Images</option>
                    <option value="video">Video</option>
                    <option value="pdf">PDF</option>
                    <option value="docx">DOCX</option>
                </select>
            </div>
            <div class="form-group" id="file-upload">
                <label id="file-upload-label">Upload Files:</label>
                <input type="file" id="result_files" name="result_files[]" class="form-control" multiple style="display: none;">
                <input type="file" id="result_file" name="result_file" class="form-control" style="display: none;">
            </div>
            <div class="form-group">
                <label for="category_id">Category:</label>
                <select id="category_id" name="category_id" class="form-control" required>
                    <?php
                    $category_query = "SELECT id, category_name FROM categories";
                    $category_result = $conn->query($category_query);
                    while ($category = $category_result->fetch_assoc()) {
                        echo "<option value='{$category['id']}'>{$category['category_name']}</option>";
                    }
                    ?>
                </select>
            </div>
            <div class="form-group">
                <label for="tags">Tags (comma-separated):</label>
                <input type="text" id="tags" name="tags" class="form-control" required>
            </div>
            <button type="submit" class="btn btn-primary">Submit</button>
        </form>
    </div>

    <script src="https://code.jquery.com/jquery-3.5.1.slim.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/@popperjs/core@2.11.6/dist/umd/popper.min.js"></script>
    <script src="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/js/bootstrap.min.js"></script>
    <script src="https://cdn.quilljs.com/1.3.6/quill.js"></script>
    <script>
        var quill = new Quill('#editor-container', {
            theme: 'snow',
            modules: {
                toolbar: [
                    [{ 'header': '1' }, { 'header': '2' }, { 'font': [] }],
                    [{ 'list': 'ordered' }, { 'list': 'bullet' }],
                    ['bold', 'italic', 'underline'],
                    [{ 'align': [] }],
                    ['link'],
                    [{ 'color': [] }, { 'background': [] }],
                    ['clean']
                ]
            }
        });

        document.querySelector('form').addEventListener('submit', function() {
            var description = document.querySelector('#editor-container .ql-editor').innerHTML;
            document.querySelector('#description').value = description;
        });

        document.getElementById('result_type').addEventListener('change', function() {
            var type = this.value;
            document.getElementById('result_files').style.display = (type === 'images') ? 'block' : 'none';
            document.getElementById('result_file').style.display = ['video', 'pdf', 'docx', 'text'].includes(type) ? 'block' : 'none';
        });
    </script>
</body>
</html>
