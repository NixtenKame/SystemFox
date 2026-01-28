<?php
// Include database configuration
define('ROOT_PATH', realpath(__DIR__ . '/../..'));

include_once ROOT_PATH . '/connections/config.php';

if (isset($_SESSION['user_id'])) {
    $user_id = $_SESSION['user_id'];
    $stmt = $db->prepare("UPDATE users SET online_status = 'offline' WHERE id = ?");
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
}

// Check if the "Remember Me" cookie is set
if (isset($_COOKIE['remember_me_token'])) {
    $token = $_COOKIE['remember_me_token'];

    // Prepare and execute the SQL statement to delete the token
    $stmt = $db->prepare("DELETE FROM remember_me WHERE token = ?");
    $stmt->bind_param("s", $token);
    $stmt->execute();
    $stmt->close();

    // Unset the "Remember Me" cookie
    setcookie('remember_me_token', '', time() - 3600, "/"); // Expire the cookie
}

// Unset all session variables
session_unset();

// Destroy the session
session_destroy();

// Redirect to the login page
header("Location: /login");
exit;
?>