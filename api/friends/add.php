<?php
define('ROOT_PATH', realpath(__DIR__ . '/../../..'));

include_once ROOT_PATH . '/connections/config.php';
require '../../actions/friends.php';

$currentUserId = $_SESSION['user_id'] ?? 0;

// Username of the target user
$targetUsername = $_POST['target_username'] ?? '';

if (!$currentUserId || !$targetUsername) {
    exit("Invalid request");
}

// Convert username → user_id
$stmt = $db->prepare("SELECT id FROM users WHERE username = ? LIMIT 1");
$stmt->bind_param("s", $targetUsername);
$stmt->execute();
$result = $stmt->get_result();
$targetData = $result->fetch_assoc();
$stmt->close();

if (!$targetData) {
    exit("User not found.");
}

$targetId = $targetData['id'];

// Send request using IDs internally
sendFriendRequest($db, $currentUserId, $targetId);

// Redirect back to profile
header("Location: /user/" . urlencode($targetUsername));
exit;
