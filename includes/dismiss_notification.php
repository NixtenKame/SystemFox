<?php

header('Content-Type: application/json');
define('ROOT_PATH', realpath(__DIR__ . '/../../'));

include_once ROOT_PATH . '/connections/config.php';

// Check login, required POST parameters and CSRF token
if (!isset($_SESSION['user_id']) || !isset($_POST['notification_id']) || !isset($_POST['csrf_token'])) {
    http_response_code(403);
    echo json_encode(["success" => false, "message" => "Invalid request"]);
    exit;
}

// Validate CSRF token
if (!hash_equals($_SESSION['csrf_token'], $_POST['csrf_token'])) {
    http_response_code(403);
    echo json_encode(["success" => false, "message" => "Invalid CSRF token"]);
    exit;
}

$user_id = $_SESSION['user_id'];
$notification_id = intval($_POST['notification_id']);

// Make sure your config.php defines $db (or adjust accordingly)
$stmt = $db->prepare("UPDATE notifications SET dismissed = 1 WHERE id = ? AND user_id = ?");
$stmt->bind_param("ii", $notification_id, $user_id);

if ($stmt->execute()) {
    echo json_encode(["success" => true]);
} else {
    echo json_encode(["success" => false, "message" => "Failed to dismiss notification"]);
}

$stmt->close();
$db->close();
