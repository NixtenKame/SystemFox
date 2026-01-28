<?php
define('ROOT_PATH', realpath(__DIR__ . '/../..'));

include_once ROOT_PATH . '/connections/config.php';

header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized access.']);
    exit;
}

$user_id = $_SESSION['user_id'];
$image_id = $_POST['image_id'] ?? null;
$action = $_POST['action'] ?? null;

if (!$image_id || !$action || !in_array($action, ['like', 'dislike'])) {
    echo json_encode(['success' => false, 'message' => 'Invalid request.']);
    exit;
}

$query = "SELECT action FROM image_likes WHERE user_id = ? AND image_id = ?";
$stmt = $db->prepare($query);
$stmt->bind_param("ii", $user_id, $image_id);
$stmt->execute();
$result = $stmt->get_result();
$existing = $result->fetch_assoc();
$stmt->close();

if ($existing) {
    if ($existing['action'] === $action) {
        $deleteQuery = $db->prepare("DELETE FROM image_likes WHERE user_id = ? AND image_id = ?");
        $deleteQuery->bind_param("ii", $user_id, $image_id);
        $deleteQuery->execute();
        $deleteQuery->close();
        $userAction = 'none';
    } else {
        $updateQuery = $db->prepare("UPDATE image_likes SET action = ? WHERE user_id = ? AND image_id = ?");
        $updateQuery->bind_param("sii", $action, $user_id, $image_id);
        $updateQuery->execute();
        $updateQuery->close();
        $userAction = $action;
    }
} else {
    $insertQuery = $db->prepare("INSERT INTO image_likes (user_id, image_id, action) VALUES (?, ?, ?)");
    $insertQuery->bind_param("iis", $user_id, $image_id, $action);
    $insertQuery->execute();
    $insertQuery->close();
    $userAction = $action;
}

$likesQuery = $db->prepare("SELECT COUNT(*) FROM image_likes WHERE image_id = ? AND action = 'like'");
$likesQuery->bind_param("i", $image_id);
$likesQuery->execute();
$likesResult = $likesQuery->get_result();
$likes = $likesResult->fetch_row()[0];
$likesQuery->close();

$dislikesQuery = $db->prepare("SELECT COUNT(*) FROM image_likes WHERE image_id = ? AND action = 'dislike'");
$dislikesQuery->bind_param("i", $image_id);
$dislikesQuery->execute();
$dislikesResult = $dislikesQuery->get_result();
$dislikes = $dislikesResult->fetch_row()[0];
$dislikesQuery->close();

echo json_encode([
    'success' => true,
    'likes' => $likes,
    'dislikes' => $dislikes,
    'userAction' => $userAction
]);

$db->close();
?>