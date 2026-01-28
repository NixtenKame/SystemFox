<?php
define('ROOT_PATH', realpath(__DIR__ . '/../..'));

include_once ROOT_PATH . '/connections/config.php';

header('Content-Type: application/json');

if (!isset($_GET['comment_id']) || !is_numeric($_GET['comment_id']) || !isset($_GET['action']) || !in_array($_GET['action'], ['like', 'dislike'])) {
    echo json_encode(['success' => false, 'message' => 'Invalid input']);
    exit;
}

$commentId = intval($_GET['comment_id']);
$action = $_GET['action'];
$userId = $_SESSION['user_id'];

$checkQuery = $db->prepare("SELECT id FROM comment_likes WHERE comment_id = ? AND user_id = ?");
$checkQuery->bind_param("ii", $commentId, $userId);
$checkQuery->execute();
$checkResult = $checkQuery->get_result();

if ($checkResult->num_rows > 0) {
    $updateQuery = $db->prepare("UPDATE comment_likes SET action = ? WHERE comment_id = ? AND user_id = ?");
    $updateQuery->bind_param("sii", $action, $commentId, $userId);
    $updateQuery->execute();
} else {
    $insertQuery = $db->prepare("INSERT INTO comment_likes (comment_id, user_id, action) VALUES (?, ?, ?)");
    $insertQuery->bind_param("iis", $commentId, $userId, $action);
    $insertQuery->execute();
}

$likesQuery = $db->prepare("SELECT COUNT(*) FROM comment_likes WHERE comment_id = ? AND action = 'like'");
$likesQuery->bind_param("i", $commentId);
$likesQuery->execute();
$likesCount = $likesQuery->get_result()->fetch_row()[0];

$dislikesQuery = $db->prepare("SELECT COUNT(*) FROM comment_likes WHERE comment_id = ? AND action = 'dislike'");
$dislikesQuery->bind_param("i", $commentId);
$dislikesQuery->execute();
$dislikesCount = $dislikesQuery->get_result()->fetch_row()[0];

echo json_encode(['success' => true, 'likes' => $likesCount, 'dislikes' => $dislikesCount]);
?>
