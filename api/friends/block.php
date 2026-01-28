<?php
define('ROOT_PATH', realpath(__DIR__ . '/../../..'));

include_once ROOT_PATH . '/connections/config.php';
require '../../actions/friends.php';

$currentUserId = $_SESSION['user_id'] ?? 0;
$targetUsername = $_POST['target_username'] ?? '';

if (!$currentUserId || !$targetUsername) {
    exit("Invalid request.");
}

// Convert username → user_id
$stmt = $db->prepare("SELECT id FROM users WHERE username = ? LIMIT 1");
$stmt->bind_param("s", $targetUsername);
$stmt->execute();
$result = $stmt->get_result();
$userRow = $result->fetch_assoc();
$stmt->close();

if (!$userRow) {
    exit("User not found.");
}

$targetId = $userRow['id'];

// Block the target
blockUser($db, $currentUserId, $targetId);

// Redirect back to their profile
header("Location: /user/" . urlencode($targetUsername));
exit;
