<?php

header("Content-Type: application/json");
define('ROOT_PATH', realpath(__DIR__ . '/../..'));

include_once ROOT_PATH . '/connections/config.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(["error" => "Invalid request method."]);
    exit;
}

if (!isset($_POST['action'])) {
    echo json_encode(["error" => "Invalid request - Missing action parameter."]);
    exit;
}

$action = $_POST['action'];
$tag = $_POST['tag'] ?? '';

if (!$tag) {
    echo json_encode(["error" => "Invalid request - Tag is empty."]);
    exit;
}

$user_id = $_SESSION['user_id'] ?? 0;
if (!$user_id) {
    echo json_encode(["error" => "Unauthorized access."]);
    exit;
}

if ($action === "add") {
    $stmt = $db->prepare("INSERT INTO user_blacklist (user_id, tag) VALUES (?, ?) ON DUPLICATE KEY UPDATE tag=tag");
    $stmt->bind_param("is", $user_id, $tag);
    if ($stmt->execute()) {
        echo json_encode(["success" => "Tag added successfully!"]);
    } else {
        echo json_encode(["error" => "Failed to add tag."]);
    }
    exit;
}

if ($action === "remove") {
    $stmt = $db->prepare("DELETE FROM user_blacklist WHERE user_id = ? AND tag = ?");
    $stmt->bind_param("is", $user_id, $tag);
    if ($stmt->execute()) {
        echo json_encode(["success" => "Tag removed successfully!"]);
    } else {
        echo json_encode(["error" => "Failed to remove tag."]);
    }
    exit;
}


echo json_encode(["error" => "Invalid action."]);
exit;
?>
