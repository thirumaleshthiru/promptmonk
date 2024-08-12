<?php

include 'db_connect.php';  

$profile_pic_url = null;

if (isset($_SESSION['user_id'])) {
    $user_id = $_SESSION['user_id'];

    $query = "SELECT profile_image FROM users WHERE id = ?";

    if ($stmt = $conn->prepare($query)) {
        $stmt->bind_param("i", $user_id);
        $stmt->execute();
        $stmt->bind_result($profile_image);
        if ($stmt->fetch()) {
            if ($profile_image) {
                $profile_pic_url = htmlspecialchars($profile_image);
            }
        }
        $stmt->close();
    } else {
        $error = $conn->error;
        echo "Error preparing statement: $error";
    }
}
?>

<nav class="navbar navbar-expand-lg navbar-light" style="background-color: #F7EFE5;">
    <a class="navbar-brand" href="index.php" style="color: #674188;">Prompt Monk</a>
    <button class="navbar-toggler" type="button" data-toggle="collapse" data-target="#navbarNav" aria-controls="navbarNav" aria-expanded="false" aria-label="Toggle navigation">
        <span class="navbar-toggler-icon" style="color: #674188;"></span>
    </button>
    <div class="collapse navbar-collapse" id="navbarNav">
        <ul class="navbar-nav ml-auto d-flex align-items-center">
            <?php if (isset($_SESSION['user_id'])): ?>
                <?php if ($_SESSION['user_role'] === 'admin'): ?>
                    <li class="nav-item mx-2">
                        <a class="nav-link" href="admin_dashboard.php" style="color: #674188;">Admin Dashboard</a>
                    </li>
                    <li class="nav-item mx-2">
                        <a class="nav-link" href="manage_prompts.php" style="color: #674188;">Manage Prompts</a>
                    </li>
                <?php else: ?>
                    <li class="nav-item mx-2">
                        <a class="nav-link" href="dashboard.php" style="color: #674188;">Dashboard</a>
                    </li>
                    <li class="nav-item mx-2">
                        <a class="nav-link" href="my_prompts.php" style="color: #674188;">My Prompts</a>
                    </li>
                <?php endif; ?>
                <li class="nav-item mx-2">
                    <a class="nav-link" href="logout.php" style="color: #674188;">Logout</a>
                </li>
                <?php if ($profile_pic_url): ?>
                    <li class="nav-item d-none d-md-block mx-2">
                        <img src="<?php echo $profile_pic_url; ?>" alt="Profile Picture" class="rounded-circle" style="width: 30px; height: 30px; border: 2px solid #674188;">
                    </li>
                <?php endif; ?>
            <?php else: ?>
                <li class="nav-item mx-2">
                    <a class="nav-link" href="login.php" style="color: #674188;">Login</a>
                </li>
                <li class="nav-item mx-2">
                    <a class="nav-link" href="register.php" style="color: #674188;">Register</a>
                </li>
            <?php endif; ?>
        </ul>
    </div>
</nav>
