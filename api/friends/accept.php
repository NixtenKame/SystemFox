<?php
define('ROOT_PATH', realpath(__DIR__ . '/../../..'));

include_once ROOT_PATH . '/connections/config.php';
require '../../actions/friends.php';

$currentUserId = $_SESSION['user_id'] ?? 0;
$senderUsername = $_POST['from_username'] ?? '';

if (!$currentUserId || !$senderUsername) {
    exit("Invalid request");
}

// Convert username → user_id
$stmt = $db->prepare("SELECT id FROM users WHERE username = ? LIMIT 1");
$stmt->bind_param("s", $senderUsername);
$stmt->execute();
$result = $stmt->get_result();
$senderData = $result->fetch_assoc();
$stmt->close();

if (!$senderData) {
    exit("User not found.");
}

$senderId = $senderData['id'];

acceptFriendRequest($db, $currentUserId, $senderId);

header("Location: /user/" . urlencode($senderUsername));
exit;
