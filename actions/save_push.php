<?php
define('ROOT_PATH', realpath(__DIR__ . '/../..'));
include_once ROOT_PATH . '/connections/config.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(["error" => "Not logged in"]);
    exit;
}

// Get JSON POST data
$input = file_get_contents("php://input");
$data = json_decode($input, true);

if (!$data || !isset($data['endpoint'])) {
    http_response_code(400);
    echo json_encode(["error" => "No subscription received"]);
    exit;
}

// Save subscription
$stmt = $db->prepare("UPDATE users SET push_subscription=? WHERE id=?");
$stmt->execute([json_encode($data), $_SESSION['user_id']]);

echo json_encode(["success" => true]);
exit;
