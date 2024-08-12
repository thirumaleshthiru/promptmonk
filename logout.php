<?php
session_start();

// Clear all session variables
$_SESSION = array();

// If it's desired to destroy the session cookie, you can delete it here
if (ini_get("session.use_cookies")) {
    $params = session_get_cookie_params();
    setcookie(session_name(), '', time() - 42000,
        $params["path"], $params["domain"], $params["secure"], $params["httponly"]);
}

// Destroy the session
session_destroy();

// Redirect to the login page
header("Location: login.php");
exit();
?>
