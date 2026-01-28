<?php

define('ROOT_PATH', realpath(__DIR__ . '/../..'));

include_once ROOT_PATH . '/connections/config.php';

// Set JSON header for all responses
header('Content-Type: application/json');

// Check login, required POST parameters and CSRF token
if (!isset($_SESSION['user_id'], $_POST['notification_id'], $_POST['csrf_token'])) {
    http_response_code(403);
    echo json_encode(["success" => false, "message" => "Invalid request"]);
    exit;
}

// Validate CSRF token safely
if (!hash_equals($_SESSION['csrf_token'], $_POST['csrf_token'])) {
    http_response_code(403);
    echo json_encode(["success" => false, "message" => "Invalid CSRF token"]);
    exit;
}

$user_id = $_SESSION['user_id'];
$notification_id = intval($_POST['notification_id']);

if ($notification_id > 0) {
    $query = "UPDATE notifications SET dismissed = 0 WHERE id = ? AND user_id = ?";
    $stmt = $db->prepare($query);

    if (!$stmt) {
        // Prepare failed
        http_response_code(500);
        echo json_encode(['success' => false, 'message' => 'Database error preparing statement']);
        exit;
    }

    $stmt->bind_param("ii", $notification_id, $user_id);

    if ($stmt->execute()) {
        echo json_encode(['success' => true]);
    } else {
        http_response_code(500);
        echo json_encode(['success' => false, 'message' => 'Failed to mark notification as unread.']);
    }

    $stmt->close();
} else {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Invalid notification ID.']);
}

$db->close();
