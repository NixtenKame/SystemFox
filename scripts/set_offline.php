<?php
define('ROOT_PATH', realpath(__DIR__ . '/../..'));

include_once ROOT_PATH . '/connections/config.php';


// Log file path
$logFile = '../../logs/set_offline.log';

if (isset($_SESSION['user_id'])) {
    $user_id = $_SESSION['user_id'];
    $stmt = $db->prepare("UPDATE users SET online_status = 'offline' WHERE id = ?");
    $stmt->bind_param("i", $user_id);
    if ($stmt->execute()) {
        echo "Status set to offline";
        file_put_contents($logFile, "Status set to offline for user_id: $user_id\n", FILE_APPEND);
    } else {
        echo "Error updating status: " . $stmt->error;
        file_put_contents($logFile, "Error updating status for user_id: $user_id - " . $stmt->error . "\n", FILE_APPEND);
    }
    $stmt->close();
} else {
    echo "User not logged in";
    file_put_contents($logFile, "User not logged in\n", FILE_APPEND);
}
?>