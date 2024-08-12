<?php
include 'db_connect.php';
session_start();

// Fetch categories for filtering
$categoryQuery = "SELECT id, category_name FROM categories";
$categoryStmt = $conn->prepare($categoryQuery);
$categoryStmt->execute();
$categories = $categoryStmt->get_result()->fetch_all(MYSQLI_ASSOC);

// Fetch prompts based on selected category (if any)
$selectedCategory = isset($_GET['category']) ? $_GET['category'] : '';
$categoryFilter = $selectedCategory ? "WHERE p.category_id = ?" : '';
$sql = "SELECT p.id, p.title, p.prompt, p.description, p.result_type, 
               COALESCE(COUNT(pl.id), 0) AS likes 
        FROM prompts p 
        LEFT JOIN prompt_likes pl ON p.id = pl.prompt_id AND pl.like_dislike = 'like' 
        $categoryFilter 
        GROUP BY p.id, p.title, p.prompt, p.description, p.result_type";

$stmt = $conn->prepare($sql);

if ($selectedCategory) {
    $stmt->bind_param("i", $selectedCategory);
}

$stmt->execute();
$prompts = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

function truncateText($text, $length = 100) {
    return strlen($text) > $length ? substr($text, 0, $length) . '...' : $text;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Prompt Community</title>
<link href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css" rel="stylesheet">
<style>
body {
    background-color: #F7EFE5;
    color: #674188;
}
.card-container {
    margin-bottom: 20px;
    width: 100%;
}
.card {
    width: 100%;
    border: 1px solid #C8A1E0;
    border-radius: 5px;
    box-shadow: 0 0 10px rgba(103, 65, 136, 0.1);
    overflow: hidden;
    display: flex;
    flex-direction: column;
    background-color: #E2BFD9;
}
.card-body {
    padding: 20px;
    display: flex;
    flex-direction: column;
    height: 100%;
}
.card-title {
    margin-bottom: 10px;
    color: #674188;
}
.card-text {
    flex: 1;
    overflow: hidden;
    text-overflow: ellipsis;
    display: -webkit-box;
    -webkit-line-clamp: 3;
    -webkit-box-orient: vertical;
    color: #674188;
}
.btn-small {
    font-size: 12px;
    padding: 5px 10px;
    background-color: #674188;
    border-color: #674188;
    color: #F7EFE5;
}
.btn-small:hover {
    background-color: #C8A1E0;
    border-color: #C8A1E0;
    color: #674188;
}
.like-icon {
    font-size: 16px;
    color: #674188;
    margin-right: 5px;
}
.category-scroll {
    white-space: nowrap;
    overflow-x: auto;
    -webkit-overflow-scrolling: touch;
    padding: 10px 0;
    border-bottom: 1px solid #C8A1E0;
    margin-bottom: 20px;
}
.category-scroll a {
    display: inline-block;
    margin-right: 10px;
    padding: 8px 15px;
    border-radius: 20px;
    background-color: #E2BFD9;
    text-decoration: none;
    color: #674188;
    border: 1px solid #C8A1E0;
    font-size: 14px;
}
.category-scroll a:hover, .category-scroll a.active {
    background-color: #674188;
    color: #F7EFE5;
    border-color: #674188;
}
 
.category-scroll .scrollbar-arrow {
    display: none;  
}


/* Webkit browsers (Chrome, Safari) */
::-webkit-scrollbar {
    width: 12px;
}

::-webkit-scrollbar-track {
    background: #F7EFE5;
    border-radius: 10px;
}

::-webkit-scrollbar-thumb {
    background: #674188;
    border-radius: 10px;
}

::-webkit-scrollbar-thumb:hover {
    background: #C8A1E0;
}
::-webkit-scrollbar-arrow {
    display: none;
}
/* Firefox */
* {
    scrollbar-width: thin;
    scrollbar-color: #674188 #F7EFE5;
}

/* Edge */
::-ms-scrollbar {
    width: 12px;
}

::-ms-scrollbar-track {
    background: #F7EFE5;
}

::-ms-scrollbar-thumb {
    background: #674188;
}

::-ms-scrollbar-thumb:hover {
    background: #C8A1E0;
}
.btn{
    width:150px;
}
</style>
</head>
<body>
<?php include 'navbar.php'; ?>
<br>
<div class="container">
<div class="category-scroll">
<a href="index.php" class="<?php echo !$selectedCategory ? 'active' : ''; ?>">All Categories</a>
<?php foreach ($categories as $category): ?>
<a href="index.php?category=<?php echo $category['id']; ?>" class="<?php echo $selectedCategory == $category['id'] ? 'active' : ''; ?>">
<?php echo htmlspecialchars($category['category_name']); ?>
</a>
<?php endforeach; ?>
</div>

<div class="row">
<?php foreach ($prompts as $prompt): ?>
<div class="col-md-12">
<div class="card card-container">
<div class="card-body">
<h5 class="card-title"><?php echo htmlspecialchars($prompt['title']); ?></h5>
<p class="card-text"><?php echo htmlspecialchars($prompt['prompt']); ?></p>
<p class="card-text">
<span class="like-icon">&#10084;</span> 
<?php echo htmlspecialchars($prompt['likes']); ?>
</p>
<a href="prompt.php?id=<?php echo urlencode(base64_encode($prompt['id'])); ?>" class="btn btn-primary btn-small">View</a>
</div>
</div>
</div>
<?php endforeach; ?>
</div>
</div>

<script src="https://code.jquery.com/jquery-3.5.1.slim.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/@popperjs/core@2.9.3/dist/umd/popper.min.js"></script>
<script src="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/js/bootstrap.min.js"></script>
</body>
</html>
