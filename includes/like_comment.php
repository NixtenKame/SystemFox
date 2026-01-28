<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

define('ROOT_PATH', realpath(__DIR__ . '/../..'));

include_once ROOT_PATH . '/connections/config.php';
header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Not logged in']);
    exit;
}

if (!isset($_POST['comment_id'], $_POST['action'])) {
    echo json_encode(['success' => false, 'message' => 'Missing parameters']);
    exit;
}

$user_id = $_SESSION['user_id'];
$comment_id = intval($_POST['comment_id']);
$action = $_POST['action'] === 'like' ? 'like' : 'dislike';

// Remove previous like/dislike by this user for this comment
$stmt = $db->prepare("DELETE FROM comment_likes WHERE user_id = ? AND comment_id = ?");
$stmt->bind_param("ii", $user_id, $comment_id);
$stmt->execute();
$stmt->close();

// Add new like/dislike
$stmt = $db->prepare("INSERT INTO comment_likes (user_id, comment_id, action) VALUES (?, ?, ?)");
$stmt->bind_param("iis", $user_id, $comment_id, $action);
$stmt->execute();
$stmt->close();

// Get updated counts
$stmt = $db->prepare("SELECT COUNT(*) FROM comment_likes WHERE comment_id = ? AND action = 'like'");
$stmt->bind_param("i", $comment_id);
$stmt->execute();
$stmt->bind_result($likes);
$stmt->fetch();
$stmt->close();

$stmt = $db->prepare("SELECT COUNT(*) FROM comment_likes WHERE comment_id = ? AND action = 'dislike'");
$stmt->bind_param("i", $comment_id);
$stmt->execute();
$stmt->bind_result($dislikes);
$stmt->fetch();
$stmt->close();

// Get user's current action
$stmt = $db->prepare("SELECT action FROM comment_likes WHERE user_id = ? AND comment_id = ?");
$stmt->bind_param("ii", $user_id, $comment_id);
$stmt->execute();
$stmt->bind_result($userAction);
$stmt->fetch();
$stmt->close();

echo json_encode([
    'success' => true,
    'likes' => $likes,
    'dislikes' => $dislikes,
    'userAction' => $userAction
]);