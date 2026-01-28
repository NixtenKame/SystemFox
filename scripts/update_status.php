<?php
// Add this at the top of your PHP files for debugging
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

define('ROOT_PATH', realpath(__DIR__ . '/../..'));

include_once ROOT_PATH . '/connections/config.php';


$timeout = 3; // Set timeout (seconds) to determine offline status

// Query to mark users as offline if they haven't been active within the timeout period
$stmt = $db->prepare("UPDATE users SET online_status = 'offline' WHERE last_activity < NOW() - INTERVAL ? SECOND");
$stmt->bind_param("i", $timeout);
$stmt->execute();
$stmt->close();

// Update the last_activity timestamp for the logged-in user
if (isset($_SESSION['user_id'])) {
    $user_id = $_SESSION['user_id'];
    $stmt = $db->prepare("UPDATE users SET online_status = 'online', last_activity = NOW() WHERE id = ?");
    $stmt->bind_param("i", $user_id);
    if ($stmt->execute()) {
        echo "Status updated";
    } else {
        echo "Error updating status: " . $stmt->error;
    }
    $stmt->close();
} else {
    echo "User not logged in";
}

$db->close();
?>