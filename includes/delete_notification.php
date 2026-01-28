<?php

define('ROOT_PATH', realpath(__DIR__ . '/../..'));

include_once ROOT_PATH . '/connections/config.php';

if (!isset($_SESSION['user_id'])) {
    die(json_encode(['success' => false, 'message' => 'Unauthorized access.']));
}

$user_id = $_SESSION['user_id'];
$notification_id = $_POST['notification_id'] ?? null;

if ($notification_id) {
    $query = "DELETE FROM notifications WHERE id = ? AND user_id = ?";
    $stmt = $db->prepare($query);
    $stmt->bind_param("ii", $notification_id, $user_id);
    if ($stmt->execute()) {
        echo json_encode(['success' => true]);
    } else {
        echo json_encode(['success' => false, 'message' => 'Failed to delete notification.']);
    }
    $stmt->close();
} else {
    echo json_encode(['success' => false, 'message' => 'Invalid notification ID.']);
}

$db->close();
?>